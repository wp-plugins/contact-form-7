<?php

function wpcf7_message( $contact_form, $status ) {
	if ( ! isset( $contact_form['messages'] ) || ! isset( $contact_form['messages'][$status] ) )
		return wpcf7_default_message( $status );

	return $contact_form['messages'][$status];
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