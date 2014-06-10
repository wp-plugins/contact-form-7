<?php

class WPCF7_Submission {

	private static $instance;

	private $contact_form;

	private $result = array(
		'status' => 'init',
		'valid' => true,
		'invalid_reasons' => array(),
		'invalid_fields' => array(),
		'spam' => false,
		'message' => '',
		'mail_sent' => false );

	private $posted_data = array();
	private $skip_mail = false;

	private function __construct() {}

	public static function get_instance( WPCF7_ContactForm $contact_form ) {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
			self::$instance->contact_form = $contact_form;

			if ( $contact_form->in_demo_mode() ) {
				self::$instance->skip_mail = true;
			}
		}

		return self::$instance;
	}

	public function submit() {
		$this->setup_posted_data();
		$validation = $this->validate();

		$result = &$this->result;
		$contact_form = $this->contact_form;

		if ( ! $validation['valid'] ) { // Validation error occured
			$result['status'] = 'validation_failed';
			$result['valid'] = false;
			$result['invalid_reasons'] = $validation['reason'];
			$result['invalid_fields'] = $validation['idref'];
			$result['message'] = $contact_form->message( 'validation_error' );

		} elseif ( ! $this->accepted() ) { // Not accepted terms
			$result['status'] = 'acceptance_missing';
			$result['message'] = $contact_form->message( 'accept_terms' );

		} elseif ( $this->spam() ) { // Spam!
			$result['status'] = 'spam';
			$result['message'] = $contact_form->message( 'spam' );
			$result['spam'] = true;

		} elseif ( $this->mail() ) {
			$result['status'] = 'mail_sent';
			$result['mail_sent'] = true;
			$result['message'] = $contact_form->message( 'mail_sent_ok' );

			do_action( 'wpcf7_mail_sent', $contact_form );

		} else {
			$result['status'] = 'mail_failed';
			$result['message'] = $contact_form->message( 'mail_sent_ng' );

			do_action( 'wpcf7_mail_failed', $contact_form );
		}

		return $result;
	}

	public function get_posted_data() {
		return $this->posted_data;
	}

	private function setup_posted_data() {
		$posted_data = (array) $_POST;

		$tags = $this->contact_form->form_scan_shortcode();

		foreach ( (array) $tags as $tag ) {
			if ( empty( $tag['name'] ) ) {
				continue;
			}

			$name = $tag['name'];
			$value = '';

			if ( isset( $posted_data[$name] ) ) {
				$value = $posted_data[$name];
			}

			$pipes = $tag['pipes'];

			if ( WPCF7_USE_PIPE
			&& is_a( $pipes, 'WPCF7_Pipes' )
			&& ! $pipes->zero() ) {
				if ( is_array( $value) ) {
					$new_value = array();

					foreach ( $value as $v ) {
						$new_value[] = $pipes->do_pipe( wp_unslash( $v ) );
					}

					$value = $new_value;
				} else {
					$value = $pipes->do_pipe( wp_unslash( $value ) );
				}
			}

			$posted_data[$name] = $value;
		}

		$this->posted_data = apply_filters( 'wpcf7_posted_data', $posted_data );

		return $this->posted_data;
	}

	private function validate() {
		$tags = $this->contact_form->form_scan_shortcode();

		$result = array(
			'valid' => true,
			'reason' => array(),
			'idref' => array() );

		foreach ( $tags as $tag ) {
			$result = apply_filters( 'wpcf7_validate_' . $tag['type'],
				$result, $tag );
		}

		return apply_filters( 'wpcf7_validate', $result );
	}

	private function accepted() {
		return apply_filters( 'wpcf7_acceptance', true );
	}

	private function spam() {
		$spam = false;

		if ( WPCF7_VERIFY_NONCE && ! $this->verify_nonce() ) {
			$spam = true;
		}

		if ( $this->blacklist_check() ) {
			$spam = true;
		}

		return apply_filters( 'wpcf7_spam', $spam );
	}

	private function verify_nonce() {
		return wpcf7_verify_nonce( $_POST['_wpnonce'], $this->contact_form->id );
	}

	private function blacklist_check() {
		$target = wpcf7_array_flatten( $this->posted_data );
		$target[] = $_SERVER['REMOTE_ADDR'];
		$target[] = $_SERVER['HTTP_USER_AGENT'];

		$target = implode( "\n", $target );

		return wpcf7_blacklist_check( $target );
	}

	/* Mail */

	private function mail() {
		$contact_form = $this->contact_form;

		do_action( 'wpcf7_before_send_mail', $contact_form );

		if ( $this->skip_mail || ! empty( $contact_form->skip_mail ) ) {
			return true;
		}

		$result = $this->compose_mail(
			$this->setup_mail_template( $contact_form->mail, 'mail' ) );

		if ( $result ) {
			$additional_mail = array();

			if ( $contact_form->mail_2['active'] ) {
				$additional_mail[] = $this->setup_mail_template(
					$contact_form->mail_2, 'mail_2' );
			}

			$additional_mail = apply_filters( 'wpcf7_additional_mail',
				$additional_mail, $contact_form );

			foreach ( $additional_mail as $mail ) {
				$this->compose_mail( $mail );
			}

			return true;
		}

		return false;
	}

	private function setup_mail_template( $mail_template, $name = '' ) {
		$defaults = array(
			'subject' => '', 'sender' => '', 'body' => '',
			'recipient' => '', 'additional_headers' => '',
			'attachments' => '', 'use_html' => false );

		$mail_template = wp_parse_args( $mail_template, $defaults );

		$name = trim( $name );

		if ( ! empty( $name ) )
			$mail_template['name'] = $name;

		return $mail_template;
	}

	private function compose_mail( $mail_template, $send = true ) {
		$this->mail_template_in_process = $mail_template;

		$use_html = (bool) $mail_template['use_html'];

		$subject = $this->replace_mail_tags( $mail_template['subject'] );
		$sender = $this->replace_mail_tags( $mail_template['sender'] );
		$recipient = $this->replace_mail_tags( $mail_template['recipient'] );
		$additional_headers = $this->replace_mail_tags(
			$mail_template['additional_headers'] );

		if ( $use_html ) {
			$body = $this->replace_mail_tags( $mail_template['body'], true );
			$body = wpautop( $body );
		} else {
			$body = $this->replace_mail_tags( $mail_template['body'] );
		}

		$attachments = $this->mail_attachments( $mail_template['attachments'] );

		$components = compact( 'subject', 'sender', 'body',
			'recipient', 'additional_headers', 'attachments' );

		$components = apply_filters( 'wpcf7_mail_components',
			$components, $this->contact_form );

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

	private function replace_mail_tags( $content, $html = false ) {
		$regex = '/(\[?)\[[\t ]*'
			. '([a-zA-Z_][0-9a-zA-Z:._-]*)' // [2] = name
			. '((?:[\t ]+"[^"]*"|[\t ]+\'[^\']*\')*)' // [3] = values
			. '[\t ]*\](\]?)/';

		if ( $html ) {
			$callback = array( $this, 'mail_callback_html' );
		} else {
			$callback = array( $this, 'mail_callback' );
		}

		return preg_replace_callback( $regex, $callback, $content );
	}

	private function mail_callback_html( $matches ) {
		return $this->mail_callback( $matches, true );
	}

	private function mail_callback( $matches, $html = false ) {
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

		if ( isset( $this->posted_data[$tagname] ) ) {

			if ( $do_not_heat ) {
				$submitted = isset( $_POST[$tagname] ) ? $_POST[$tagname] : '';
			} else {
				$submitted = $this->posted_data[$tagname];
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

	private function format( $original, $format ) {
		$original = (array) $original;

		foreach ( $original as $key => $value ) {
			if ( preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value ) ) {
				$original[$key] = mysql2date( $format, $value );
			}
		}

		return $original;
	}

	private function mail_attachments( $template ) {
		$attachments = array();

		$uploaded_files = $this->contact_form->uploaded_files;

		foreach ( (array) $uploaded_files as $name => $path ) {
			if ( false !== strpos( $template, "[${name}]" ) && ! empty( $path ) ) {
				$attachments[] = $path;
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

?>