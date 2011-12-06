<?php

class WPCF7_ContactForm {

	var $initial = false;

	var $id;
	var $title;

	var $unit_tag;

	var $responses_count = 0;
	var $scanned_form_tags;

	var $posted_data;
	var $uploaded_files = array();

	var $skip_mail = false;

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
		$enctype = apply_filters( 'wpcf7_form_enctype', '' );
		$class = apply_filters( 'wpcf7_form_class_attr', 'wpcf7-form' );

		$form .= '<form action="' . esc_url_raw( $url ) . '" method="post"'
			. ' class="' . esc_attr( $class ) . '"' . $enctype . '>' . "\n";
		$form .= '<div style="display: none;">' . "\n";
		$form .= '<input type="hidden" name="_wpcf7" value="'
			. esc_attr( $this->id ) . '" />' . "\n";
		$form .= '<input type="hidden" name="_wpcf7_version" value="'
			. esc_attr( WPCF7_VERSION ) . '" />' . "\n";
		$form .= '<input type="hidden" name="_wpcf7_unit_tag" value="'
			. esc_attr( $this->unit_tag ) . '" />' . "\n";
		$form .= '</div>' . "\n";
		$form .= $this->form_elements();

		if ( ! $this->responses_count )
			$form .= $this->form_response_output();

		$form .= '</form>';

		$form .= '</div>';

		return $form;
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

	/* Validate */

	function validate() {
		$fes = $this->form_scan_shortcode();

		$result = array( 'valid' => true, 'reason' => array() );

		foreach ( $fes as $fe ) {
			$result = apply_filters( 'wpcf7_validate_' . $fe['type'], $result, $fe );
		}

		return $result;
	}

	/* Mail */

	function mail() {
		$fes = $this->form_scan_shortcode();

		foreach ( $fes as $fe ) {
			if ( empty( $fe['name'] ) )
				continue;

			$name = $fe['name'];
			$pipes = $fe['pipes'];
			$value = $_POST[$name];

			if ( WPCF7_USE_PIPE && is_a( $pipes, 'WPCF7_Pipes' ) && ! $pipes->zero() ) {
				if ( is_array( $value) ) {
					$new_value = array();
					foreach ( $value as $v ) {
						$new_value[] = $pipes->do_pipe( stripslashes( $v ) );
					}
					$value = $new_value;
				} else {
					$value = $pipes->do_pipe( stripslashes( $value ) );
				}
			}

			$this->posted_data[$name] = $value;
		}

		if ( $this->in_demo_mode() )
			$this->skip_mail = true;

		do_action_ref_array( 'wpcf7_before_send_mail', array( &$this ) );

		if ( $this->skip_mail )
			return true;

		if ( $this->compose_and_send_mail( $this->mail ) ) {
			$additional_mail = array();

			if ( $this->mail_2['active'] )
				$additional_mail[] = $this->mail_2;

			$additional_mail = apply_filters_ref_array( 'wpcf7_additional_mail',
				array( $additional_mail, &$this ) );

			foreach ( $additional_mail as $mail )
				$this->compose_and_send_mail( $mail );

			return true;
		}

		return false;
	}

	function compose_and_send_mail( $mail_template ) {
		$regex = '/\[\s*([a-zA-Z_][0-9a-zA-Z:._-]*)\s*\]/';

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

		extract( apply_filters_ref_array( 'wpcf7_mail_components', array( 
			compact( 'subject', 'sender', 'body', 'recipient', 'additional_headers', 'attachments' ),
			&$this ) ) );

		$headers = "From: $sender\n";

		if ( $use_html )
			$headers .= "Content-Type: text/html\n";

		$headers .= trim( $additional_headers ) . "\n";

		return @wp_mail( $recipient, $subject, $body, $headers, $attachments );
	}

	function mail_callback_html( $matches ) {
		return $this->mail_callback( $matches, true );
	}

	function mail_callback( $matches, $html = false ) {
		if ( isset( $this->posted_data[$matches[1]] ) ) {
			$submitted = $this->posted_data[$matches[1]];

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

		if ( $special = apply_filters( 'wpcf7_special_mail_tags', '', $matches[1] ) )
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
			if ( preg_match('/^([a-zA-Z0-9_]+)\s*:(.*)$/', $setting, $matches ) ) {
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
		$postarr = array(
			'ID' => (int) $this->id,
			'post_type' => 'wpcf7_contact_form',
			'post_status' => 'publish',
			'post_title' => $this->title );

		$post_id = wp_insert_post( $postarr );

		if ( $post_id ) {
			$metas = array( 'form', 'mail', 'mail_2', 'messages', 'additional_settings' );

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

		wp_delete_post( $this->id, true );

		$this->initial = true;
		$this->id = null;
	}
}

function wpcf7_contact_form( $id ) {
	$post = get_post( $id );

	if ( empty( $post ) || 'wpcf7_contact_form' != get_post_type( $post ) )
		return false;

	$contact_form = new WPCF7_ContactForm();
	$contact_form->id = $post->ID;
	$contact_form->title = $post->post_title;

	$contact_form->form = get_post_meta( $post->ID, 'form', true );
	$contact_form->mail = get_post_meta( $post->ID, 'mail', true );
	$contact_form->mail_2 = get_post_meta( $post->ID, 'mail_2', true );
	$contact_form->messages = get_post_meta( $post->ID, 'messages', true );
	$contact_form->additional_settings = get_post_meta( $post->ID, 'additional_settings', true );

	$contact_form->upgrade();

	$contact_form = apply_filters_ref_array( 'wpcf7_contact_form', array( &$contact_form ) );

	return $contact_form;
}

function wpcf7_get_contact_form_by_old_id( $old_id ) {
	global $wpdb;

	$q = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_old_cf7_unit_id'"
		. $wpdb->prepare( " AND meta_value = %d", $old_id );

	if ( $new_id = $wpdb->get_var( $q ) )
		return wpcf7_contact_form( $new_id );
}

function wpcf7_contact_form_default_pack( $locale = null ) {
	// For backward compatibility

	return wpcf7_get_contact_form_default_pack( array( 'locale' => $locale ) );
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

?>