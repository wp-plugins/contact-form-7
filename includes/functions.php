<?php

function wpcf7_default_message( $status ) {
	switch ( $status ) {
		case 'mail_sent_ok':
			return __( 'Your message was sent successfully. Thanks.', 'wpcf7' );
		case 'mail_sent_ng':
			return __( 'Failed to send your message. Please try later or contact administrator by other way.', 'wpcf7' );
		case 'akismet_says_spam':
			return __( 'Failed to send your message. Please try later or contact administrator by other way.', 'wpcf7' );
		case 'validation_error':
			return __( 'Validation errors occurred. Please confirm the fields and submit it again.', 'wpcf7' );
		case 'accept_terms':
			return __( 'Please accept the terms to proceed.', 'wpcf7' );
		case 'invalid_email':
			return __( 'Email address seems invalid.', 'wpcf7' );
		case 'invalid_required':
			return __( 'Please fill the required field.', 'wpcf7' );
		case 'captcha_not_match':
			return __( 'Your entered code is incorrect.', 'wpcf7' );
		case 'quiz_answer_not_correct':
			return __( 'Your answer is not correct.', 'wpcf7' );
		case 'upload_failed':
			return __( 'Failed to upload file.', 'wpcf7' );
		case 'upload_file_type_invalid':
			return __( 'This file type is not allowed.', 'wpcf7' );
		case 'upload_file_too_large':
			return __( 'This file is too large.', 'wpcf7' );
	}
}

function wpcf7_default_form_template() {
	$template .= '<p>' . __( 'Your Name', 'wpcf7' ) . ' ' . __( '(required)', 'wpcf7' ) . '<br />' . "\n";
	$template .= '    [text* your-name] </p>' . "\n\n";
	$template .= '<p>' . __( 'Your Email', 'wpcf7' ) . ' ' . __( '(required)', 'wpcf7' ) . '<br />' . "\n";
	$template .= '    [email* your-email] </p>' . "\n\n";
	$template .= '<p>' . __( 'Subject', 'wpcf7' ) . '<br />' . "\n";
	$template .= '    [text your-subject] </p>' . "\n\n";
	$template .= '<p>' . __( 'Your Message', 'wpcf7' ) . '<br />' . "\n";
	$template .= '    [textarea your-message] </p>' . "\n\n";
	$template .= '<p>[submit "' . __( 'Send', 'wpcf7' ) . '"]</p>';
	return $template;
}

function wpcf7_default_mail_template() {
	$subject = '[your-subject]';
	$sender = '[your-name] <[your-email]>';
	$body = '[your-message]';
	$recipient = get_option( 'admin_email' );
	return compact( 'subject', 'sender', 'body', 'recipient' );
}

function wpcf7_default_mail_2_template() {
	$active = false;
	$subject = '[your-subject]';
	$sender = '[your-name] <[your-email]>';
	$body = '[your-message]';
	$recipient = '[your-email]';
	return compact( 'active', 'subject', 'sender', 'body', 'recipient' );
}

function wpcf7_default_messages_template() {
	$mail_sent_ok = wpcf7_default_message( 'mail_sent_ok' );
	$mail_sent_ng = wpcf7_default_message( 'mail_sent_ng' );
	$akismet_says_spam = wpcf7_default_message( 'akismet_says_spam' );
	$validation_error = wpcf7_default_message( 'validation_error' );
	$accept_terms = wpcf7_default_message( 'accept_terms' );
	$invalid_email = wpcf7_default_message( 'invalid_email' );
	$invalid_required = wpcf7_default_message( 'invalid_required' );
	$quiz_answer_not_correct = wpcf7_default_message( 'quiz_answer_not_correct' );
	$captcha_not_match = wpcf7_default_message( 'captcha_not_match' );
	$upload_failed = wpcf7_default_message( 'upload_failed' );
	$upload_file_type_invalid = wpcf7_default_message( 'upload_file_type_invalid' );
	$upload_file_too_large = wpcf7_default_message( 'upload_file_too_large' );

	return compact( 'mail_sent_ok', 'mail_sent_ng', 'akismet_says_spam',
		'validation_error', 'accept_terms', 'invalid_email', 'invalid_required', 'quiz_answer_not_correct',
		'captcha_not_match', 'upload_failed', 'upload_file_type_invalid', 'upload_file_too_large' );
}

function wpcf7_upload_dir( $type = false ) {
	$siteurl = get_option( 'siteurl' );
	$upload_path = trim( get_option( 'upload_path' ) );
	if ( empty( $upload_path ) )
		$dir = WP_CONTENT_DIR . '/uploads';
	else
		$dir = $upload_path;

	$dir = path_join( ABSPATH, $dir );

	if ( ! $url = get_option( 'upload_url_path' ) ) {
		if ( empty( $upload_path ) || $upload_path == $dir )
			$url = WP_CONTENT_URL . '/uploads';
		else
			$url = trailingslashit( $siteurl ) . $upload_path;
	}

	if ( defined( 'UPLOADS' ) ) {
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	if ( 'dir' == $type )
		return $dir;
	if ( 'url' == $type )
		return $url;
	return array( 'dir' => $dir, 'url' => $url );
}

function wpcf7_captcha_tmp_dir() {
	if ( defined( 'WPCF7_CAPTCHA_TMP_DIR' ) )
		return WPCF7_CAPTCHA_TMP_DIR;
	else
		return wpcf7_upload_dir( 'dir' ) . '/wpcf7_captcha';
}

function wpcf7_captcha_tmp_url() {
	if ( defined( 'WPCF7_CAPTCHA_TMP_URL' ) )
		return WPCF7_CAPTCHA_TMP_URL;
	else
		return wpcf7_upload_dir( 'url' ) . '/wpcf7_captcha';
}

function wpcf7_upload_tmp_dir() {
	if ( defined( 'WPCF7_UPLOADS_TMP_DIR' ) )
		return WPCF7_UPLOADS_TMP_DIR;
	else
		return wpcf7_upload_dir( 'dir' ) . '/wpcf7_uploads';
}

function wpcf7_json( $items ) {
	if ( is_array( $items ) ) {
		if ( empty( $items ) )
			return 'null';

		$keys = array_keys( $items );
		$all_int = true;
		foreach ( $keys as $key ) {
			if ( ! is_int( $key ) ) {
				$all_int = false;
				break;
			}
		}

		if ( $all_int ) {
			$children = array();
			foreach ( $items as $item ) {
				$children[] = wpcf7_json( $item );
			}
			return '[' . join( ', ', $children ) . ']';
		} else { // Object
			$children = array();
			foreach ( $items as $key => $item ) {
				$key = esc_js( (string) $key );
				if ( preg_match( '/[^a-zA-Z]/', $key ) )
					$key = '"' . $key . '"';

				$children[] = $key . ': ' . wpcf7_json( $item );
			}
			return '{ ' . join( ', ', $children ) . ' }';
		}
	} elseif ( is_numeric( $items ) ) {
		return (string) $items;
	} elseif ( is_bool( $items ) ) {
		return $items ? '1' : '0';
	} elseif ( is_null( $items ) ) {
		return 'null';
	} else {
		return '"' . esc_js( (string) $items ) . '"';
	}
}

?>