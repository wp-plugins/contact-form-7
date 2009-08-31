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

function wpcf7_table_name() {
	global $wpdb;

	return $wpdb->prefix . "contact_form_7";
}

function wpcf7_table_exists() {
	global $wpdb;

	$table_name = wpcf7_table_name();

	return $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name;
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

require_once WPCF7_PLUGIN_DIR . '/includes/functions.php';
require_once WPCF7_PLUGIN_DIR . '/includes/formatting.php';
require_once WPCF7_PLUGIN_DIR . '/includes/pipe.php';
require_once WPCF7_PLUGIN_DIR . '/includes/shortcodes.php';
require_once WPCF7_PLUGIN_DIR . '/includes/classes.php';

if ( is_admin() )
	require_once WPCF7_PLUGIN_DIR . '/admin/admin.php';

function wpcf7_contact_forms() {
	global $wpdb;

	$table_name = wpcf7_table_name();

	return $wpdb->get_results( "SELECT cf7_unit_id as id, title FROM $table_name" );
}

$wpcf7_contact_form = null;

$wpcf7_processing_within = null;
$wpcf7_unit_count = null;
$wpcf7_widget_count = null;

function wpcf7_ajax_json_echo() {
	global $wpcf7_contact_form;

	$echo = '';

	if ( isset( $_POST['_wpcf7'] ) ) {
		$id = (int) $_POST['_wpcf7'];
		$unit_tag = $_POST['_wpcf7_unit_tag'];

		if ( $wpcf7_contact_form = wpcf7_contact_form( $id ) ) {
			$validation = $wpcf7_contact_form->validate();

			$items = array(
				'mailSent' => false,
				'into' => '#' . $unit_tag,
				'captcha' => null );

			$items = apply_filters( 'wpcf7_ajax_json_echo', $items );

			if ( ! $validation['valid'] ) { // Validation error occured
				$invalids = array();
				foreach ( $validation['reason'] as $name => $reason ) {
					$invalids[] = array(
						'into' => 'span.wpcf7-form-control-wrap.' . $name,
						'message' => $reason );
				}

				$items['message'] = $wpcf7_contact_form->message( 'validation_error' );
				$items['invalids'] = $invalids;

			} elseif ( ! $wpcf7_contact_form->accepted() ) { // Not accepted terms
				$items['message'] = $wpcf7_contact_form->message( 'accept_terms' );

			} elseif ( $wpcf7_contact_form->akismet() ) { // Spam!
				$items['message'] = $wpcf7_contact_form->message( 'akismet_says_spam' );
				$items['spam'] = true;

			} elseif ( $wpcf7_contact_form->mail() ) {
				$items['mailSent'] = true;
				$items['message'] = $wpcf7_contact_form->message( 'mail_sent_ok' );

				$on_sent_ok = $wpcf7_contact_form->additional_setting( 'on_sent_ok', false );
				if ( ! empty( $on_sent_ok ) ) {
					$on_sent_ok = array_map( 'wpcf7_strip_quote', $on_sent_ok );
				} else {
					$on_sent_ok = null;
				}
				$items['onSentOk'] = $on_sent_ok;

				do_action_ref_array( 'wpcf7_mail_sent', array( &$wpcf7_contact_form ) );

			} else {
				$items['message'] = $wpcf7_contact_form->message( 'mail_sent_ng' );
			}

			// remove upload files
			foreach ( (array) $wpcf7_contact_form->uploaded_files as $name => $path ) {
				@unlink( $path );
			}

			$wpcf7_contact_form = null;
		}
	}

	$echo = wpcf7_json( $items );

	if ( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo $echo;
	} else {
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		echo '<textarea>' . $echo . '</textarea>';
	}
}

function wpcf7_process_nonajax_submitting() {
	global $wpcf7_contact_form;

	if ( ! isset($_POST['_wpcf7'] ) )
		return;

	$id = (int) $_POST['_wpcf7'];

	if ( $wpcf7_contact_form = wpcf7_contact_form( $id ) ) {
		$validation = $wpcf7_contact_form->validate();

		if ( ! $validation['valid'] ) {
			$_POST['_wpcf7_validation_errors'] = array( 'id' => $id, 'messages' => $validation['reason'] );
		} elseif ( ! $wpcf7_contact_form->accepted() ) { // Not accepted terms
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => $wpcf7_contact_form->message( 'accept_terms' ) );
		} elseif ( $wpcf7_contact_form->akismet() ) { // Spam!
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => $wpcf7_contact_form->message( 'akismet_says_spam' ), 'spam' => true );
		} elseif ( $wpcf7_contact_form->mail() ) {
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => true, 'message' => $wpcf7_contact_form->message( 'mail_sent_ok' ) );

			do_action_ref_array( 'wpcf7_mail_sent', array( &$wpcf7_contact_form ) );
		} else {
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => $wpcf7_contact_form->message( 'mail_sent_ng' ) );
		}

		// remove upload files
		foreach ( (array) $wpcf7_contact_form->uploaded_files as $name => $path ) {
			@unlink( $path );
		}

		$wpcf7_contact_form = null;
	}
}

function wpcf7_the_content_filter( $content ) {
	global $wpcf7_processing_within, $wpcf7_unit_count;

	$wpcf7_processing_within = 'p' . get_the_ID();
	$wpcf7_unit_count = 0;

	return $content;
}

add_filter( 'the_content', 'wpcf7_the_content_filter', 9 );

function wpcf7_widget_text_filter( $content ) {
	global $wpcf7_widget_count, $wpcf7_processing_within, $wpcf7_unit_count;

	$wpcf7_widget_count += 1;
	$wpcf7_processing_within = 'w' . $wpcf7_widget_count;
	$wpcf7_unit_count = 0;

	$regex = '/\[\s*contact-form\s+(\d+(?:\s+.*)?)\]/';
	return preg_replace_callback( $regex, 'wpcf7_widget_text_filter_callback', $content );
}

add_filter( 'widget_text', 'wpcf7_widget_text_filter', 9 );

function wpcf7_widget_text_filter_callback( $matches ) {
	return do_shortcode( $matches[0] );
}

function wpcf7_contact_form_tag_func( $atts ) {
	global $wpcf7_contact_form, $wpcf7_unit_count, $wpcf7_processing_within;

	if ( is_string( $atts ) )
		$atts = explode( ' ', $atts, 2 );

	$atts = (array) $atts;

	$id = (int) array_shift( $atts );

	if ( ! ( $wpcf7_contact_form = wpcf7_contact_form( $id ) ) )
		return '[contact-form 404 "Not Found"]';

	$wpcf7_unit_count += 1;

	$unit_tag = 'wpcf7-f' . $id . '-' . $wpcf7_processing_within . '-o' . $wpcf7_unit_count;
	$wpcf7_contact_form->unit_tag = $unit_tag;

	$form = $wpcf7_contact_form->form_html();

	$wpcf7_contact_form = null;

	return $form;
}

add_shortcode( 'contact-form', 'wpcf7_contact_form_tag_func' );

function wpcf7_wp_head() {
	$stylesheet_url = wpcf7_plugin_url( 'stylesheet.css' );
	echo '<link rel="stylesheet" href="' . $stylesheet_url . '" type="text/css" />';

	if ( 'rtl' == get_bloginfo( 'text_direction' ) ) {
		$stylesheet_rtl_url = wpcf7_plugin_url( 'stylesheet-rtl.css' );
		echo '<link rel="stylesheet" href="' . $stylesheet_rtl_url . '" type="text/css" />';
	}
}

if ( WPCF7_LOAD_CSS )
	add_action( 'wp_head', 'wpcf7_wp_head' );

/* Loading modules */

function wpcf7_load_modules() {
	$dir = WPCF7_PLUGIN_MODULES_DIR;

	if ( ! ( is_dir( $dir ) && $dh = opendir( $dir ) ) )
		return false;

	while ( ( $module = readdir( $dh ) ) !== false ) {
		if ( substr( $module, -4 ) == '.php' )
			include_once $dir . '/' . $module;
	}
}

add_action( 'init', 'wpcf7_load_modules' );

function wpcf7_enqueue_scripts() {
	$in_footer = true;
	if ( 'header' === WPCF7_LOAD_JS )
		$in_footer = false;

	wp_enqueue_script( 'contact-form-7', wpcf7_plugin_url( 'contact-form-7.js' ),
		array('jquery', 'jquery-form'), WPCF7_VERSION, $in_footer );
}

if ( ! is_admin() && WPCF7_LOAD_JS )
	add_action( 'init', 'wpcf7_enqueue_scripts' );

function wpcf7_load_plugin_textdomain() { // l10n
	load_plugin_textdomain( 'wpcf7',
		'wp-content/plugins/contact-form-7/languages', 'contact-form-7/languages' );
}

add_action( 'init', 'wpcf7_load_plugin_textdomain' );

function wpcf7_init_switch() {
	if ( 'POST' == $_SERVER['REQUEST_METHOD'] && 1 == (int) $_POST['_wpcf7_is_ajax_call'] ) {
		wpcf7_ajax_json_echo();
		exit();
	} elseif ( ! is_admin() ) {
		wpcf7_process_nonajax_submitting();
	}
}

add_action( 'init', 'wpcf7_init_switch', 11 );

?>