<?php

function wpcf7_plugin_path( $path = '' ) {
	return path_join( WPCF7_PLUGIN_DIR, trim( $path, '/' ) );
}

function wpcf7_plugin_url( $path = '' ) {
	global $wp_version;

	if ( version_compare( $wp_version, '2.8', '<' ) ) { // Using WordPress 2.7
		$path = path_join( WPCF7_PLUGIN_NAME, $path );
		return plugins_url( $path );
	}

	return plugins_url( $path, WPCF7_PLUGIN_BASENAME );
}

function wpcf7_admin_url( $file, $query = array() ) {
	$file = trim( $file, ' /' );
	if ( 'admin/' != substr( $file, 0, 6 ) )
		$file = 'admin/' . $file;

	$path = 'admin.php';
	$path .= '?page=' . WPCF7_PLUGIN_NAME . '/' . $file;

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

// Pre-2.8 compatibility
if ( ! function_exists( 'esc_js' ) ) {
	function esc_js( $text ) {
		return js_escape( $text );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return wp_specialchars( $text );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return attribute_escape( $text );
	}
}

if ( ! function_exists( 'esc_sql' ) ) {
	function esc_sql( $text ) {
		global $wpdb;
		return $wpdb->escape( $text );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url, $protocols = null ) {
		return clean_url( $url, $protocols, 'display' );
	}
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
	load_plugin_textdomain( 'wpcf7',
		'wp-content/plugins/contact-form-7/languages', 'contact-form-7/languages' );
}

?>