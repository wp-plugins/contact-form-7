<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

function wpcf7_delete_plugin() {
	global $wpdb;

	$table_name = $wpdb->prefix . "contact_form_7";

	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

	wpcf7_rmdir( wpcf7_captcha_tmp_dir() );
	wpcf7_rmdir( wpcf7_upload_tmp_dir() );
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