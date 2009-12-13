<?php

function wpcf7_plugin_path( $path = '' ) {
	return path_join( WPCF7_PLUGIN_DIR, trim( $path, '/' ) );
}

function wpcf7_plugin_url( $path = '' ) {
	return plugins_url( $path, WPCF7_PLUGIN_BASENAME );
}

function wpcf7_admin_url( $query = array() ) {
	$path = 'admin.php?page=wpcf7';

	if ( $query = build_query( $query ) )
		$path .= '&' . $query;

	$url = admin_url( $path );

	return sanitize_url( $url );
}

function wpcf7_table_name() {
	global $wpdb;

	return $wpdb->prefix . "contact_form_7";
}

function wpcf7_table_exists() {
	global $wpdb;

	$table_name = wpcf7_table_name();

	return strtolower( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) == strtolower( $table_name );
}

require_once WPCF7_PLUGIN_DIR . '/includes/functions.php';
require_once WPCF7_PLUGIN_DIR . '/includes/formatting.php';
require_once WPCF7_PLUGIN_DIR . '/includes/pipe.php';
require_once WPCF7_PLUGIN_DIR . '/includes/shortcodes.php';
require_once WPCF7_PLUGIN_DIR . '/includes/classes.php';

if ( is_admin() )
	require_once WPCF7_PLUGIN_DIR . '/admin/admin.php';
else
	require_once WPCF7_PLUGIN_DIR . '/includes/controller.php';

function wpcf7_contact_forms() {
	global $wpdb;

	$table_name = wpcf7_table_name();

	return $wpdb->get_results( "SELECT cf7_unit_id as id, title FROM $table_name" );
}

$wpcf7_contact_form = null;
$wpcf7_request_uri = null;
$wpcf7_processing_within = null;
$wpcf7_unit_count = null;
$wpcf7_widget_count = null;

add_action( 'plugins_loaded', 'wpcf7_set_request_uri', 9 );

function wpcf7_set_request_uri() {
	global $wpcf7_request_uri;

	$wpcf7_request_uri = add_query_arg( array() );
}

function wpcf7_get_request_uri() {
	global $wpcf7_request_uri;

	return (string) $wpcf7_request_uri;
}

/* Loading modules */

add_action( 'plugins_loaded', 'wpcf7_load_modules', 1 );

function wpcf7_load_modules() {
	$dir = WPCF7_PLUGIN_MODULES_DIR;

	if ( ! ( is_dir( $dir ) && $dh = opendir( $dir ) ) )
		return false;

	while ( ( $module = readdir( $dh ) ) !== false ) {
		if ( substr( $module, -4 ) == '.php' )
			include_once $dir . '/' . $module;
	}
}

/* L10N */

add_action( 'init', 'wpcf7_load_plugin_textdomain' );

function wpcf7_load_plugin_textdomain() {
	load_plugin_textdomain( 'wpcf7', false, 'contact-form-7/languages' );
}

?>