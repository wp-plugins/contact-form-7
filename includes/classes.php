<?php

class WPCF7_ContactForm {

	const post_type = 'wpcf7_contact_form';

	public static $found_items = 0;

	var $initial = false;

	var $id;
	var $title;

	var $unit_tag;

	var $responses_count = 0;
	var $scanned_form_tags;

	var $posted_data;
	var $uploaded_files = array();

	var $skip_mail = false;

	public static function register_post_type() {
		register_post_type( self::post_type, array(
			'labels' => array(
				'name' => __( 'Contact Forms', 'wpcf7' ),
				'singular_name' => __( 'Contact Form', 'wpcf7' ) ) ) );
	}

	public static function find( $args = '' ) {
		$defaults = array(
			'post_status' => 'any',
			'posts_per_page' => -1,
			'offset' => 0,
			'orderby' => 'ID',
			'order' => 'ASC' );

		$args = wp_parse_args( $args, $defaults );

		$args['post_type'] = self::post_type;

		$q = new WP_Query();
		$posts = $q->query( $args );

		self::$found_items = $q->found_posts;

		$objs = array();

		foreach ( (array) $posts as $post )
			$objs[] = new self( $post );

		return $objs;
	}

	public function __construct( $post = null ) {
		$this->initial = true;

		$post = get_post( $post );

		if ( $post && self::post_type == get_post_type( $post ) ) {
			$this->initial = false;
			$this->id = $post->ID;
			$this->title = $post->post_title;

			$this->form = get_post_meta( $post->ID, 'form', true );
			$this->mail = get_post_meta( $post->ID, 'mail', true );
			$this->mail_2 = get_post_meta( $post->ID, 'mail_2', true );
			$this->messages = get_post_meta( $post->ID, 'messages', true );
			$this->additional_settings = get_post_meta( $post->ID, 'additional_settings', true );

			$this->upgrade();
		}

		do_action_ref_array( 'wpcf7_contact_form', array( &$this ) );
	}

	// Return true if this form is the same one as currently POSTed.
	function is_posted() {
		if ( ! isset( $_POST['_wpcf7_unit_tag'] ) || empty( $_POST['_wpcf7_unit_tag'] ) )
			return false;

		if ( $this->unit_tag == $_POST['_wpcf7_unit_tag'] )
			return true;

		return false;
	}

	function clear_post() {
		$fes = $this->form_scan_shortcode();

		foreach ( $fes as $fe ) {
			if ( ! isset( $fe['name'] ) || empty( $fe['name'] ) )
				continue;

			$name = $fe['name'];

			if ( isset( $_POST[$name] ) )
				unset( $_POST[$name] );
		}
	}

	/* Generating Form HTML */

	function form_html() {
		$form = '<div class="wpcf7" id="' . $this->unit_tag . '">';

		$url = wpcf7_get_request_uri();

		if ( $frag = strstr( $url, '#' ) )
			$url = substr( $url, 0, -strlen( $frag ) );

		$url .= '#' . $this->unit_tag;

		$url = apply_filters( 'wpcf7_form_action_url', $url );

		$class = 'wpcf7-form';

		if ( $this->is_posted() ) {
			if ( ! empty( $_POST['_wpcf7_validation_errors'] ) ) {
				$class .= ' invalid';
			} elseif ( ! empty( $_POST['_wpcf7_mail_sent'] ) ) {
				if ( ! empty( $_POST['_wpcf7_mail_sent']['spam'] ) )
					$class .= ' spam';
				elseif ( ! empty( $_POST['_wpcf7_mail_sent']['ok'] ) )
					$class .= ' sent';
				else
					$class .= ' failed';
			}
		}

		$class = apply_filters( 'wpcf7_form_class_attr', $class );

		$enctype = apply_filters( 'wpcf7_form_enctype', '' );

		$form .= '<form action="' . esc_url_raw( $url ) . '" method="post"'
			. ' class="' . esc_attr( $class ) . '"' . $enctype . '>' . "\n";

		$form .= $this->form_hidden_fields();

		$form .= $this->form_elements();

		if ( ! $this->responses_count )
			$form .= $this->form_response_output();

		$form .= '</form>';

		$form .= '</div>';

		return $form;
	}

	function form_hidden_fields() {
		$hidden_fields = array(
			'_wpcf7' => $this->id,
			'_wpcf7_version' => WPCF7_VERSION,
			'_wpcf7_unit_tag' => $this->unit_tag );

		if ( WPCF7_VERIFY_NONCE )
			$hidden_fields['_wpnonce'] = wpcf7_create_nonce( $this->unit_tag );

		$content = '';

		foreach ( $hidden_fields as $name => $value ) {
			$content .= '<input type="hidden"'
				. ' name="' . esc_attr( $name ) . '"'
				. ' value="' . esc_attr( $value ) . '" />' . "\n";
		}

		return '<div style="display: none;">' . "\n" . $content . '</div>' . "\n";
	}

	function form_response_output() {
		$class = 'wpcf7-response-output';
		$content = '';

		if ( $this->is_posted() ) { // Post response output for non-AJAX
			if ( isset( $_POST['_wpcf7_mail_sent'] ) && $_POST['_wpcf7_mail_sent']['id'] == $this->id ) {
				if ( $_POST['_wpcf7_mail_sent']['ok'] ) {
					$class .= ' wpcf7-mail-sent-ok';
					$content = $_POST['_wpcf7_mail_sent']['message'];
				} else {
					$class .= ' wpcf7-mail-sent-ng';
					if ( $_POST['_wpcf7_mail_sent']['spam'] )
						$class .= ' wpcf7-spam-blocked';
					$content = $_POST['_wpcf7_mail_sent']['message'];
				}
			} elseif ( isset( $_POST['_wpcf7_validation_errors'] ) && $_POST['_wpcf7_validation_errors']['id'] == $this->id ) {
				$class .= ' wpcf7-validation-errors';
				$content = $this->message( 'validation_error' );
			}
		} else {
			$class .= ' wpcf7-display-none';
		}

		$class = ' class="' . $class . '"';

		return '<div' . $class . '>' . $content . '</div>';
	}

	function validation_error( $name ) {
		if ( ! $this->is_posted() )
			return '';

		if ( ! isset( $_POST['_wpcf7_validation_errors']['messages'][$name] ) )
			return '';

		$ve = trim( $_POST['_wpcf7_validation_errors']['messages'][$name] );

		if ( ! empty( $ve ) ) {
			$ve = '<span class="wpcf7-not-valid-tip-no-ajax">' . esc_html( $ve ) . '</span>';
			return apply_filters( 'wpcf7_validation_error', $ve, $name, $this );
		}

		return '';
	}

	/* Form Elements */

	function form_do_shortcode() {
		global $wpcf7_shortcode_manager;

		$form = $this->form;

		if ( WPCF7_AUTOP ) {
			$form = $wpcf7_shortcode_manager->normalize_shortcode( $form );
			$form = wpcf7_autop( $form );
		}

		$form = $wpcf7_shortcode_manager->do_shortcode( $form );
		$this->scanned_form_tags = $wpcf7_shortcode_manager->scanned_tags;

		return $form;
	}

	function form_scan_shortcode( $cond = null ) {
		global $wpcf7_shortcode_manager;

		if ( ! empty( $this->scanned_form_tags ) ) {
			$scanned = $this->scanned_form_tags;
		} else {
			$scanned = $wpcf7_shortcode_manager->scan_shortcode( $this->form );
			$this->scanned_form_tags = $scanned;
		}

		if ( empty( $scanned ) )
			return null;

		if ( ! is_array( $cond ) || empty( $cond ) )
			return $scanned;

		for ( $i = 0, $size = count( $scanned ); $i < $size; $i++ ) {

			if ( isset( $cond['type'] ) ) {
				if ( is_string( $cond['type'] ) && ! empty( $cond['type'] ) ) {
					if ( $scanned[$i]['type'] != $cond['type'] ) {
						unset( $scanned[$i] );
						continue;
					}
				} elseif ( is_array( $cond['type'] ) ) {
					if ( ! in_array( $scanned[$i]['type'], $cond['type'] ) ) {
						unset( $scanned[$i] );
						continue;
					}
				}
			}

			if ( isset( $cond['name'] ) ) {
				if ( is_string( $cond['name'] ) && ! empty( $cond['name'] ) ) {
					if ( $scanned[$i]['name'] != $cond['name'] ) {
						unset ( $scanned[$i] );
						continue;
					}
				} elseif ( is_array( $cond['name'] ) ) {
					if ( ! in_array( $scanned[$i]['name'], $cond['name'] ) ) {
						unset( $scanned[$i] );
						continue;
					}
				}
			}
		}

		return array_values( $scanned );
	}

	function form_elements() {
		return apply_filters( 'wpcf7_form_elements', $this->form_do_shortcode() );
	}

	function setup_posted_data() {
		$posted_data = (array) $_POST;

		$fes = $this->form_scan_shortcode();

		foreach ( $fes as $fe ) {
			if ( empty( $fe['name'] ) )
				continue;

			$name = $fe['name'];
			$value = '';

			if ( isset( $posted_data[$name] ) )
				$value = $posted_data[$name];

			$pipes = $fe['pipes'];

			if ( WPCF7_USE_PIPE && is_a( $pipes, 'WPCF7_Pipes' ) && ! $pipes->zero() ) {
				if ( is_array( $value) ) {
					$new_value = array();

					foreach ( $value as $v )
						$new_value[] = $pipes->do_pipe( stripslashes( $v ) );

					$value = $new_value;
				} else {
					$value = $pipes->do_pipe( stripslashes( $value ) );
				}
			}

			$posted_data[$name] = $value;
		}

		$this->posted_data = apply_filters( 'wpcf7_posted_data', $posted_data );

		return $this->posted_data;
	}

	function submit( $ajax = false ) {
		$result = array(
			'valid' => true,
			'invalid_reasons' => array(),
			'spam' => false,
			'message' => '',
			'mail_sent' => false,
			'scripts_on_sent_ok' => null );

		$this->setup_posted_data();

		$validation = $this->validate();

		if ( ! $validation['valid'] ) { // Validation error occured
			$result['valid'] = false;
			$result['invalid_reasons'] = $validation['reason'];
			$result['message'] = $this->message( 'validation_error' );

		} elseif ( ! $this->accepted() ) { // Not accepted terms
			$result['message'] = $this->message( 'accept_terms' );

		} elseif ( $this->spam() ) { // Spam!
			$result['message'] = $this->message( 'spam' );
			$result['spam'] = true;

		} elseif ( $this->mail() ) {
			$result['mail_sent'] = true;
			$result['message'] = $this->message( 'mail_sent_ok' );

			do_action_ref_array( 'wpcf7_mail_sent', array( &$this ) );

			if ( $ajax ) {
				$on_sent_ok = $this->additional_setting( 'on_sent_ok', false );

				if ( ! empty( $on_sent_ok ) )
					$result['scripts_on_sent_ok'] = array_map( 'wpcf7_strip_quote', $on_sent_ok );
			} else {
				$this->clear_post();
			}

		} else {
			$result['message'] = $this->message( 'mail_sent_ng' );
		}

		// remove upload files
		foreach ( (array) $this->uploaded_files as $name => $path ) {
			@unlink( $path );
		}

		return $result;
	}

	/* Validate */

	function validate() {
		$fes = $this->form_scan_shortcode();

		$result = array( 'valid' => true, 'reason' => array() );

		foreach ( $fes as $fe ) {
			$result = apply_filters( 'wpcf7_validate_' . $fe['type'], $result, $fe );
		}

		$result = apply_filters( 'wpcf7_validate', $result );

		return $result;
	}

	function accepted() {
		$accepted = true;

		return apply_filters( 'wpcf7_acceptance', $accepted );
	}

	function spam() {
		$spam = false;

		if ( WPCF7_VERIFY_NONCE && ! $this->verify_nonce() )
			$spam = true;

		return apply_filters( 'wpcf7_spam', $spam );
	}

	function verify_nonce() {
		return wpcf7_verify_nonce( $_POST['_wpnonce'], $_POST['_wpcf7_unit_tag'] );
	}

	/* Mail */

	function mail() {
		if ( $this->in_demo_mode() )
			$this->skip_mail = true;

		do_action_ref_array( 'wpcf7_before_send_mail', array( &$this ) );

		if ( $this->skip_mail )
			return true;

		$result = $this->compose_mail( $this->setup_mail_template( $this->mail, 'mail' ) );

		if ( $result ) {
			$additional_mail = array();

			if ( $this->mail_2['active'] )
				$additional_mail[] = $this->setup_mail_template( $this->mail_2, 'mail_2' );

			$additional_mail = apply_filters_ref_array( 'wpcf7_additional_mail',
				array( $additional_mail, &$this ) );

			foreach ( $additional_mail as $mail )
				$this->compose_mail( $mail );

			return true;
		}

		return false;
	}

	function setup_mail_template( $mail_template, $name = '' ) {
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

	function compose_mail( $mail_template, $send = true ) {
		$this->mail_template_in_process = $mail_template;

		$regex = '/(\[?)\[\s*([a-zA-Z_][0-9a-zA-Z:._-]*)\s*\](\]?)/';

		$use_html = (bool) $mail_template['use_html'];

		$callback = array( &$this, 'mail_callback' );
		$callback_html = array( &$this, 'mail_callback_html' );

		$subject = preg_replace_callback( $regex, $callback, $mail_template['subject'] );
		$sender = preg_replace_callback( $regex, $callback, $mail_template['sender'] );
		$recipient = preg_replace_callback( $regex, $callback, $mail_template['recipient'] );
		$additional_headers =
			preg_replace_callback( $regex, $callback, $mail_template['additional_headers'] );

		if ( $use_html ) {
			$body = preg_replace_callback( $regex, $callback_html, $mail_template['body'] );
			$body = wpautop( $body );
		} else {
			$body = preg_replace_callback( $regex, $callback, $mail_template['body'] );
		}

		$attachments = array();

		foreach ( (array) $this->uploaded_files as $name => $path ) {
			if ( false === strpos( $mail_template['attachments'], "[${name}]" ) || empty( $path ) )
				continue;

			$attachments[] = $path;
		}

		$components = compact(
			'subject', 'sender', 'body', 'recipient', 'additional_headers', 'attachments' );

		$components = apply_filters_ref_array( 'wpcf7_mail_components',
			array( $components, &$this ) );

		extract( $components );

		$headers = "From: $sender\n";

		if ( $use_html )
			$headers .= "Content-Type: text/html\n";

		$headers .= trim( $additional_headers ) . "\n";

		if ( $send )
			return @wp_mail( $recipient, $subject, $body, $headers, $attachments );

		return compact( 'subject', 'sender', 'body', 'recipient', 'headers', 'attachments' );
	}

	function mail_callback_html( $matches ) {
		return $this->mail_callback( $matches, true );
	}

	function mail_callback( $matches, $html = false ) {
		// allow [[foo]] syntax for escaping a tag
		if ( $matches[1] == '[' && $matches[3] == ']' )
			return substr( $matches[0], 1, -1 );

		if ( isset( $this->posted_data[$matches[2]] ) ) {
			$submitted = $this->posted_data[$matches[2]];

			if ( is_array( $submitted ) )
				$replaced = join( ', ', $submitted );
			else
				$replaced = $submitted;

			if ( $html ) {
				$replaced = strip_tags( $replaced );
				$replaced = wptexturize( $replaced );
			}

			$replaced = apply_filters( 'wpcf7_mail_tag_replaced', $replaced, $submitted );

			return stripslashes( $replaced );
		}

		if ( $special = apply_filters( 'wpcf7_special_mail_tags', '', $matches[2] ) )
			return $special;

		return $matches[0];
	}

	/* Message */

	function message( $status ) {
		$messages = $this->messages;
		$message = isset( $messages[$status] ) ? $messages[$status] : '';

		return apply_filters( 'wpcf7_display_message', $message, $status );
	}

	/* Additional settings */

	function additional_setting( $name, $max = 1 ) {
		$tmp_settings = (array) explode( "\n", $this->additional_settings );

		$count = 0;
		$values = array();

		foreach ( $tmp_settings as $setting ) {
			if ( preg_match('/^([a-zA-Z0-9_]+)[\t ]*:(.*)$/', $setting, $matches ) ) {
				if ( $matches[1] != $name )
					continue;

				if ( ! $max || $count < (int) $max ) {
					$values[] = trim( $matches[2] );
					$count += 1;
				}
			}
		}

		return $values;
	}

	function in_demo_mode() {
		$settings = $this->additional_setting( 'demo_mode', false );

		foreach ( $settings as $setting ) {
			if ( in_array( $setting, array( 'on', 'true', '1' ) ) )
				return true;
		}

		return false;
	}

	/* Upgrade */

	function upgrade() {
		if ( ! isset( $this->mail['recipient'] ) )
			$this->mail['recipient'] = get_option( 'admin_email' );


		if ( ! is_array( $this->messages ) )
			$this->messages = array();


		foreach ( wpcf7_messages() as $key => $arr ) {
			if ( ! isset( $this->messages[$key] ) )
				$this->messages[$key] = $arr['default'];
		}
	}

	/* Save */

	function save() {
		$metas = array( 'form', 'mail', 'mail_2', 'messages', 'additional_settings' );

		$post_content = '';

		foreach ( $metas as $meta ) {
			$props = (array) $this->{$meta};

			foreach ( $props as $prop )
				$post_content .= "\n" . trim( $prop );
		}

		if ( $this->initial ) {
			$post_id = wp_insert_post( array(
				'post_type' => self::post_type,
				'post_status' => 'publish',
				'post_title' => $this->title,
				'post_content' => trim( $post_content ) ) );
		} else {
			$post_id = wp_update_post( array(
				'ID' => (int) $this->id,
				'post_status' => 'publish',
				'post_title' => $this->title,
				'post_content' => trim( $post_content ) ) );
		}

		if ( $post_id ) {
			foreach ( $metas as $meta )
				update_post_meta( $post_id, $meta, wpcf7_normalize_newline_deep( $this->{$meta} ) );

			if ( $this->initial ) {
				$this->initial = false;
				$this->id = $post_id;
				do_action_ref_array( 'wpcf7_after_create', array( &$this ) );
			} else {
				do_action_ref_array( 'wpcf7_after_update', array( &$this ) );
			}

			do_action_ref_array( 'wpcf7_after_save', array( &$this ) );
		}

		return $post_id;
	}

	function copy() {
		$new = new WPCF7_ContactForm();
		$new->initial = true;
		$new->title = $this->title . '_copy';

		$new->form = $this->form;
		$new->mail = $this->mail;
		$new->mail_2 = $this->mail_2;
		$new->messages = $this->messages;
		$new->additional_settings = $this->additional_settings;

		$new = apply_filters_ref_array( 'wpcf7_copy', array( &$new, &$this ) );

		return $new;
	}

	function delete() {
		if ( $this->initial )
			return;

		if ( wp_delete_post( $this->id, true ) ) {
			$this->initial = true;
			$this->id = null;
			return true;
		}

		return false;
	}
}

function wpcf7_contact_form( $id ) {
	$contact_form = new WPCF7_ContactForm( $id );

	if ( $contact_form->initial )
		return false;

	return $contact_form;
}

function wpcf7_get_contact_form_by_old_id( $old_id ) {
	global $wpdb;

	$q = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_old_cf7_unit_id'"
		. $wpdb->prepare( " AND meta_value = %d", $old_id );

	if ( $new_id = $wpdb->get_var( $q ) )
		return wpcf7_contact_form( $new_id );
}

function wpcf7_get_contact_form_by_title( $title ) {
	$page = get_page_by_title( $title, OBJECT, WPCF7_ContactForm::post_type );

	if ( $page )
		return wpcf7_contact_form( $page->ID );

	return null;
}

function wpcf7_get_contact_form_default_pack( $args = '' ) {
	global $l10n;

	$defaults = array( 'locale' => null, 'title' => '' );
	$args = wp_parse_args( $args, $defaults );

	$locale = $args['locale'];
	$title = $args['title'];

	if ( $locale && $locale != get_locale() ) {
		$mo_orig = $l10n['wpcf7'];
		unset( $l10n['wpcf7'] );

		if ( 'en_US' != $locale ) {
			$mofile = wpcf7_plugin_path( 'languages/wpcf7-' . $locale . '.mo' );
			if ( ! load_textdomain( 'wpcf7', $mofile ) ) {
				$l10n['wpcf7'] = $mo_orig;
				unset( $mo_orig );
			}
		}
	}

	$contact_form = new WPCF7_ContactForm();
	$contact_form->initial = true;

	$contact_form->title = ( $title ? $title : __( 'Untitled', 'wpcf7' ) );

	$contact_form->form = wpcf7_get_default_template( 'form' );
	$contact_form->mail = wpcf7_get_default_template( 'mail' );
	$contact_form->mail_2 = wpcf7_get_default_template( 'mail_2' );
	$contact_form->messages = wpcf7_get_default_template( 'messages' );
	$contact_form->additional_settings = wpcf7_get_default_template( 'additional_settings' );

	if ( isset( $mo_orig ) )
		$l10n['wpcf7'] = $mo_orig;

	$contact_form = apply_filters_ref_array( 'wpcf7_contact_form_default_pack',
		array( &$contact_form, $args ) );

	return $contact_form;
}

function wpcf7_get_current_contact_form() {
	global $wpcf7_contact_form;

	if ( ! is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		return null;

	return $wpcf7_contact_form;
}

function wpcf7_is_posted() {
	if ( ! $contact_form = wpcf7_get_current_contact_form() )
		return false;

	return $contact_form->is_posted();
}

function wpcf7_get_validation_error( $name ) {
	if ( ! $contact_form = wpcf7_get_current_contact_form() )
		return '';

	return $contact_form->validation_error( $name );
}

function wpcf7_get_message( $status ) {
	if ( ! $contact_form = wpcf7_get_current_contact_form() )
		return '';

	return $contact_form->message( $status );
}

function wpcf7_scan_shortcode( $cond = null ) {
	if ( ! $contact_form = wpcf7_get_current_contact_form() )
		return null;

	return $contact_form->form_scan_shortcode( $cond );
}

function wpcf7_form_controls_class( $type, $default = '' ) {
	$type = trim( $type );
	$default = explode( ' ', $default );

	$classes = array_merge( array( 'wpcf7-form-control' ), $default );

	$typebase = rtrim( $type, '*' );
	$required = ( '*' == substr( $type, -1 ) );

	$classes[] = 'wpcf7-' . $typebase;

	if ( $required )
		$classes[] = 'wpcf7-validates-as-required';

	$classes = array_unique( $classes );

	return implode( ' ', $classes );
}

?>