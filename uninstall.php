<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

function wpcf7_delete_plugin() {
	global $wpdb;

	/* Delete tables */

	$table_name = $wpdb->prefix . "contact_form_7";

	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

	/* Delete directories */

	$upload_path = trim( get_option( 'upload_path' ) );

	if ( empty( $upload_path ) )
		$dir = WP_CONTENT_DIR . '/uploads';
	else
		$dir = $upload_path;

	$dir = path_join( ABSPATH, $dir );

	if ( defined( 'UPLOADS' ) )
		$dir = ABSPATH . UPLOADS;

	$captcha_tmp_dir = defined( 'WPCF7_CAPTCHA_TMP_DIR' )
		? WPCF7_CAPTCHA_TMP_DIR : $dir . '/wpcf7_captcha';

	wpcf7_rmdir( $captcha_tmp_dir );

	$file_tmp_dir = defined( 'WPCF7_UPLOADS_TMP_DIR' )
		? WPCF7_UPLOADS_TMP_DIR : $dir . '/wpcf7_uploads';

	wpcf7_rmdir( $file_tmp_dir );
}

function wpcf7_rmdir( $dir ) {
	$dir = trailingslashit( $dir );

	if ( ! is_dir( $dir ) || ! is_readable( $dir ) || ! is_writable( $dir ) )
		return false;

	if ( ! defined( 'ABSPATH' ) || false === strpos( ABSPATH, $dir ) )
		return false;

	if ( $handle = @opendir( $dir ) ) {
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( $file == "." || $file == ".." )
				continue;

			@unlink( $dir . $file );
		}
		closedir( $handle );
	}

	return @rmdir( $dir );
}

wpcf7_delete_plugin();

?>