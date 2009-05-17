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

?>