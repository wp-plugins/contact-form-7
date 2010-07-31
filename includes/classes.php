<?php

class WPCF7_ContactForm {

	var $initial = false;

	var $id;
	var $title;
	var $form;
	var $mail;
	var $mail_2;
	var $messages;
	var $additional_settings;

	var $unit_tag;

	var $responses_count = 0;
	var $scanned_form_tags;

	var $posted_data;
	var $uploaded_files;

	var $skip_mail = false;

	// Return true if this form is the same one as currently POSTed.
	function is_posted() {
		if ( ! isset( $_POST['_wpcf7_unit_tag'] ) || empty( $_POST['_wpcf7_unit_tag'] ) )
			return false;

		if ( $this->unit_tag == $_POST['_wpcf7_unit_tag'] )
			return true;

		return false;
	}

	/* Generating Form HTML */

	function form_html() {
		$form = '<div class="wpcf7" id="' . $this->unit_tag . '">';

		$url = wpcf7_get_request_uri();

		if ( $frag = strstr( $url, '#' ) )
			$url = substr( $url, 0, -strlen( $frag ) );

		$url .= '#' . $this->unit_tag;

		$url = apply_filters( 'wpcf7_form_action_url', $url );
		$url = esc_url_raw( $url );

		$enctype = apply_filters( 'wpcf7_form_enctype', '' );

		$form .= '<form action="' . $url
			. '" method="post" class="wpcf7-form"' . $enctype . '>' . "\n";
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

		if ( ! isset( $_POST['_wpcf7_validation_errors'] ) )
			return '';

		if ( $ve = trim( $_POST['_wpcf7_validation_errors']['messages'][$name] ) ) {
			$ve = '<span class="wpcf7-not-valid-tip-no-ajax">' . esc_html( $ve ) . '</span>';
			return apply_filters( 'wpcf7_validation_error', $ve, $name, $this );
		}

		return '';
	}

	/* Form Elements */

	function form_do_shortcode() {
		global $wpcf7_shortcode_manager;

		$form = $this->form;

		if ( WPCF7_AUTOP )
			$form = wpcf7_autop( $form );

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

	/* Acceptance */

	function accepted() {
		$accepted = true;

		return apply_filters( 'wpcf7_acceptance', $accepted );
	}

	/* Akismet */

	function akismet() {
		global $akismet_api_host, $akismet_api_port;

		if ( ! function_exists( 'akismet_http_post' ) ||
			! ( get_option( 'wordpress_api_key' ) || $wpcom_api_key ) )
			return false;

		$akismet_ready = false;
		$author = $author_email = $author_url = $content = '';
		$fes = $this->form_scan_shortcode();

		foreach ( $fes as $fe ) {
			if ( ! is_array( $fe['options'] ) ) continue;

			if ( preg_grep( '%^akismet:author$%', $fe['options'] ) && '' == $author ) {
				$author = $_POST[$fe['name']];
				$akismet_ready = true;
			}

			if ( preg_grep( '%^akismet:author_email$%', $fe['options'] ) && '' == $author_email ) {
				$author_email = $_POST[$fe['name']];
				$akismet_ready = true;
			}

			if ( preg_grep( '%^akismet:author_url$%', $fe['options'] ) && '' == $author_url ) {
				$author_url = $_POST[$fe['name']];
				$akismet_ready = true;
			}

			if ( '' != $content )
				$content .= "\n\n";

			$content .= $_POST[$fe['name']];
		}

		if ( ! $akismet_ready )
			return false;

		$c['blog'] = get_option( 'home' );
		$c['user_ip'] = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
		$c['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		$c['referrer'] = $_SERVER['HTTP_REFERER'];
		$c['comment_type'] = 'contactform7';
		if ( $permalink = get_permalink() )
			$c['permalink'] = $permalink;
		if ( '' != $author )
			$c['comment_author'] = $author;
		if ( '' != $author_email )
			$c['comment_author_email'] = $author_email;
		if ( '' != $author_url )
			$c['comment_author_url'] = $author_url;
		if ( '' != $content )
			$c['comment_content'] = $content;

		$ignore = array( 'HTTP_COOKIE' );

		foreach ( $_SERVER as $key => $value )
			if ( ! in_array( $key, (array) $ignore ) )
				$c["$key"] = $value;

		$query_string = '';
		foreach ( $c as $key => $data )
			$query_string .= $key . '=' . urlencode( stripslashes( (string) $data ) ) . '&';

		$response = akismet_http_post( $query_string, $akismet_api_host,
			'/1.1/comment-check', $akismet_api_port );
		if ( 'true' == $response[1] )
			return true;
		else
			return false;
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
			if ( $this->mail_2['active'] )
				$this->compose_and_send_mail( $this->mail_2 );

			return true;
		}

		return false;
	}

	function compose_and_send_mail( $mail_template ) {
		$regex = '/\[\s*([a-zA-Z_][0-9a-zA-Z:._-]*)\s*\]/';

		$use_html = (bool) $mail_template['use_html'];

		if ( $use_html )
			$callback = array( &$this, 'mail_callback_html' );
		else
			$callback = array( &$this, 'mail_callback' );

		$subject = preg_replace_callback( $regex, $callback, $mail_template['subject'] );
		$sender = preg_replace_callback( $regex, $callback, $mail_template['sender'] );
		$recipient = preg_replace_callback( $regex, $callback, $mail_template['recipient'] );
		$additional_headers =
			preg_replace_callback( $regex, $callback, $mail_template['additional_headers'] );
		$body = preg_replace_callback( $regex, $callback, $mail_template['body'] );

		if ( $use_html )
			$body = wpautop( $body );

		extract( apply_filters( 'wpcf7_mail_components',
			compact( 'subject', 'sender', 'body', 'recipient', 'additional_headers' ) ) );

		$headers = "From: $sender\n";

		if ( $use_html )
			$headers .= "Content-Type: text/html\n";

		$headers .= trim( $additional_headers ) . "\n";

		if ( $this->uploaded_files ) {
			$for_this_mail = array();
			foreach ( $this->uploaded_files as $name => $path ) {
				if ( false === strpos( $mail_template['attachments'], "[${name}]" ) )
					continue;
				$for_this_mail[] = $path;
			}

			return @wp_mail( $recipient, $subject, $body, $headers, $for_this_mail );
		} else {
			return @wp_mail( $recipient, $subject, $body, $headers );
		}
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
		$message = $messages[$status];

		return apply_filters( 'wpcf7_display_message', $message );
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
		global $wpdb, $wpcf7;

		$fields = array(
			'title' => maybe_serialize( stripslashes_deep( $this->title ) ),
			'form' => maybe_serialize( stripslashes_deep( $this->form ) ),
			'mail' => maybe_serialize( stripslashes_deep( $this->mail ) ),
			'mail_2' => maybe_serialize ( stripslashes_deep( $this->mail_2 ) ),
			'messages' => maybe_serialize( stripslashes_deep( $this->messages ) ),
			'additional_settings' =>
				maybe_serialize( stripslashes_deep( $this->additional_settings ) ) );

		if ( $this->initial ) {
			$result = $wpdb->insert( $wpcf7->contactforms, $fields );

			if ( $result ) {
				$this->initial = false;
				$this->id = $wpdb->insert_id;

				do_action_ref_array( 'wpcf7_after_create', array( &$this ) );
			} else {
				return false; // Failed to save
			}

		} else { // Update
			if ( ! (int) $this->id )
				return false; // Missing ID

			$result = $wpdb->update( $wpcf7->contactforms, $fields,
				array( 'cf7_unit_id' => absint( $this->id ) ) );

			if ( false !== $result ) {
				do_action_ref_array( 'wpcf7_after_update', array( &$this ) );
			} else {
				return false; // Failed to save
			}
		}

		do_action_ref_array( 'wpcf7_after_save', array( &$this ) );
		return true; // Succeeded to save
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

		return $new;
	}

	function delete() {
		global $wpdb, $wpcf7;

		if ( $this->initial )
			return;

		$query = $wpdb->prepare(
			"DELETE FROM $wpcf7->contactforms WHERE cf7_unit_id = %d LIMIT 1",
			absint( $this->id ) );

		$wpdb->query( $query );

		$this->initial = true;
		$this->id = null;
	}
}

function wpcf7_contact_form( $id ) {
	global $wpdb, $wpcf7;

	$query = $wpdb->prepare( "SELECT * FROM $wpcf7->contactforms WHERE cf7_unit_id = %d", $id );

	if ( ! $row = $wpdb->get_row( $query ) )
		return false; // No data

	$contact_form = new WPCF7_ContactForm();
	$contact_form->id = $row->cf7_unit_id;
	$contact_form->title = maybe_unserialize( $row->title );
	$contact_form->form = maybe_unserialize( $row->form );
	$contact_form->mail = maybe_unserialize( $row->mail );
	$contact_form->mail_2 = maybe_unserialize( $row->mail_2 );
	$contact_form->messages = maybe_unserialize( $row->messages );
	$contact_form->additional_settings = maybe_unserialize( $row->additional_settings );

	$contact_form->upgrade();

	return $contact_form;
}

function wpcf7_contact_form_default_pack( $locale = null ) {
	global $l10n;

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

	$contact_form->title = __( 'Untitled', 'wpcf7' );
	$contact_form->form = wpcf7_default_form_template();
	$contact_form->mail = wpcf7_default_mail_template();
	$contact_form->mail_2 = wpcf7_default_mail_2_template();
	$contact_form->messages = wpcf7_default_messages_template();

	if ( isset( $mo_orig ) )
		$l10n['wpcf7'] = $mo_orig;

	return $contact_form;
}

?>