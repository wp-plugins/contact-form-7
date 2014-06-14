<?php

class WPCF7_Mail {

	private static $current = null;

	private $name = '';
	private $template = array();

	public static function send( $template, $name = '' ) {
		$instance = new self;
		$instance->name = trim( $name );
		$instance->setup_template( $template );

		self::$current = $instance;

		return $instance->compose();
	}

	private function __construct() {}

	public static function get_current() {
		return self::$current;
	}

	private function setup_template( $template ) {
		$defaults = array(
			'subject' => '', 'sender' => '', 'body' => '',
			'recipient' => '', 'additional_headers' => '',
			'attachments' => '', 'use_html' => false );

		$this->template = wp_parse_args( $template, $defaults );
	}

	private function compose( $send = true ) {
		$template = $this->template;

		$use_html = (bool) $template['use_html'];

		$subject = self::replace_tags( $template['subject'] );
		$sender = self::replace_tags( $template['sender'] );
		$recipient = self::replace_tags( $template['recipient'] );
		$additional_headers = self::replace_tags( $template['additional_headers'] );

		if ( $use_html ) {
			$body = self::replace_tags( $template['body'], true );
			$body = wpautop( $body );
		} else {
			$body = self::replace_tags( $template['body'] );
		}

		$attachments = $this->attachments( $template['attachments'] );

		$components = compact( 'subject', 'sender', 'body',
			'recipient', 'additional_headers', 'attachments' );

		$components = apply_filters( 'wpcf7_mail_components',
			$components, wpcf7_get_current_contact_form() );

		extract( $components );

		$subject = wpcf7_strip_newline( $subject );
		$sender = wpcf7_strip_newline( $sender );
		$recipient = wpcf7_strip_newline( $recipient );

		$headers = "From: $sender\n";

		if ( $use_html ) {
			$headers .= "Content-Type: text/html\n";
		}

		$additional_headers = trim( $additional_headers );

		if ( $additional_headers ) {
			$headers .= $additional_headers . "\n";
		}

		if ( $send ) {
			return @wp_mail( $recipient, $subject, $body, $headers, $attachments );
		}

		$components = compact( 'subject', 'sender', 'body',
			'recipient', 'headers', 'attachments' );

		return $components;
	}

	public static function replace_tags( $content, $html = false ) {
		$content = explode( "\n", $content );

		foreach ( $content as $num => $line ) {
			$content[$num] = WPCF7_Mail_Line::replace_tags( $line );
		}

		$content = implode( "\n", $content );

		return $content;
	}

	private function attachments( $template ) {
		$attachments = array();

		if ( $submission = WPCF7_Submission::get_instance() ) {
			$uploaded_files = $submission->uploaded_files();

			foreach ( (array) $uploaded_files as $name => $path ) {
				if ( false !== strpos( $template, "[${name}]" )
				&& ! empty( $path ) ) {
					$attachments[] = $path;
				}
			}
		}

		foreach ( explode( "\n", $template ) as $line ) {
			$line = trim( $line );

			if ( '[' == substr( $line, 0, 1 ) ) {
				continue;
			}

			$path = path_join( WP_CONTENT_DIR, $line );

			if ( @is_readable( $path ) && @is_file( $path ) ) {
				$attachments[] = $path;
			}
		}

		return $attachments;
	}
}

class WPCF7_Mail_Line {

	public static function replace_tags( $content, $html = false ) {
		$content = str_replace( "\n", '', $content );

		$regex = '/(\[?)\[[\t ]*'
			. '([a-zA-Z_][0-9a-zA-Z:._-]*)' // [2] = name
			. '((?:[\t ]+"[^"]*"|[\t ]+\'[^\']*\')*)' // [3] = values
			. '[\t ]*\](\]?)/';

		$obj = new self;

		if ( $html ) {
			$callback = array( $obj, 'replace_tags_callback_html' );
		} else {
			$callback = array( $obj, 'replace_tags_callback' );
		}

		return preg_replace_callback( $regex, $callback, $content );
	}

	private function __construct() {}

	private function replace_tags_callback_html( $matches ) {
		return $this->replace_tags_callback( $matches, true );
	}

	private function replace_tags_callback( $matches, $html = false ) {
		// allow [[foo]] syntax for escaping a tag
		if ( $matches[1] == '[' && $matches[4] == ']' ) {
			return substr( $matches[0], 1, -1 );
		}

		$tag = $matches[0];
		$tagname = $matches[2];
		$values = $matches[3];

		if ( ! empty( $values ) ) {
			preg_match_all( '/"[^"]*"|\'[^\']*\'/', $values, $matches );
			$values = wpcf7_strip_quote_deep( $matches[0] );
		}

		$do_not_heat = false;

		if ( preg_match( '/^_raw_(.+)$/', $tagname, $matches ) ) {
			$tagname = trim( $matches[1] );
			$do_not_heat = true;
		}

		$format = '';

		if ( preg_match( '/^_format_(.+)$/', $tagname, $matches ) ) {
			$tagname = trim( $matches[1] );
			$format = $values[0];
		}

		$submission = WPCF7_Submission::get_instance();
		$submitted = $submission ? $submission->get_posted_data( $tagname ) : false;

		if ( $submitted ) {

			if ( $do_not_heat ) {
				$submitted = isset( $_POST[$tagname] ) ? $_POST[$tagname] : '';
			}

			$replaced = $submitted;

			if ( ! empty( $format ) ) {
				$replaced = $this->format( $replaced, $format );
			}

			$replaced = wpcf7_flat_join( $replaced );

			if ( $html ) {
				$replaced = esc_html( $replaced );
				$replaced = wptexturize( $replaced );
			}

			$replaced = apply_filters( 'wpcf7_mail_tag_replaced',
				$replaced, $submitted, $html );

			return wp_unslash( $replaced );
		}

		$special = apply_filters( 'wpcf7_special_mail_tags', '', $tagname, $html );

		if ( ! empty( $special ) ) {
			return $special;
		}

		return $tag;
	}

	public function format( $original, $format ) {
		$original = (array) $original;

		foreach ( $original as $key => $value ) {
			if ( preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value ) ) {
				$original[$key] = mysql2date( $format, $value );
			}
		}

		return $original;
	}
}

?>