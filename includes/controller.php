<?php

add_action( 'init', 'wpcf7_ajax_onload', 11 );

function wpcf7_ajax_onload() {
	global $wpcf7_contact_form;

	if ( 'GET' != $_SERVER['REQUEST_METHOD'] || ! isset( $_GET['_wpcf7_is_ajax_call'] ) )
		return;

	$echo = '';

	if ( isset( $_GET['_wpcf7'] ) ) {
		$id = (int) $_GET['_wpcf7'];

		if ( $wpcf7_contact_form = wpcf7_contact_form( $id ) ) {
			$items = apply_filters( 'wpcf7_ajax_onload', array() );
			$wpcf7_contact_form = null;
		}
	}

	$echo = json_encode( $items );

	if ( wpcf7_is_xhr() ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo $echo;
	}

	exit();
}

add_action( 'init', 'wpcf7_ajax_json_echo', 11 );

function wpcf7_ajax_json_echo() {
	global $wpcf7_contact_form;

	if ( 'POST' != $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['_wpcf7_is_ajax_call'] ) )
		return;

	$echo = '';

	if ( isset( $_POST['_wpcf7'] ) ) {
		$id = (int) $_POST['_wpcf7'];
		$unit_tag = wpcf7_sanitize_unit_tag( $_POST['_wpcf7_unit_tag'] );

		if ( $wpcf7_contact_form = wpcf7_contact_form( $id ) ) {

			$items = array(
				'mailSent' => false,
				'into' => '#' . $unit_tag,
				'captcha' => null );

			$result = wpcf7_submit( true );

			if ( ! empty( $result['message'] ) )
				$items['message'] = $result['message'];

			if ( $result['mail_sent'] )
				$items['mailSent'] = true;

			if ( ! $result['valid'] ) {
				$invalids = array();

				foreach ( $result['invalid_reasons'] as $name => $reason ) {
					$invalids[] = array(
						'into' => 'span.wpcf7-form-control-wrap.' . $name,
						'message' => $reason );
				}

				$items['invalids'] = $invalids;
			}

			if ( $result['spam'] )
				$items['spam'] = true;

			if ( ! empty( $result['scripts_on_sent_ok'] ) )
				$items['onSentOk'] = $result['scripts_on_sent_ok'];

			$items = apply_filters( 'wpcf7_ajax_json_echo', $items, $result );

			$wpcf7_contact_form = null;
		}
	}

	$echo = json_encode( $items );

	if ( wpcf7_is_xhr() ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo $echo;
	} else {
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		echo '<textarea>' . $echo . '</textarea>';
	}

	exit();
}

function wpcf7_is_xhr() {
	if ( ! isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) )
		return false;

	return $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
}

add_action( 'init', 'wpcf7_submit_nonajax', 11 );

function wpcf7_submit_nonajax() {
	global $wpcf7_contact_form;

	if ( ! isset( $_POST['_wpcf7'] ) )
		return;

	$id = (int) $_POST['_wpcf7'];

	if ( $wpcf7_contact_form = wpcf7_contact_form( $id ) ) {
		$result = wpcf7_submit();

		if ( ! $result['valid'] ) {
			$_POST['_wpcf7_validation_errors'] = array(
				'id' => $id,
				'messages' => $result['invalid_reasons'] );
		} else {
			$_POST['_wpcf7_mail_sent'] = array(
				'id' => $id,
				'ok' => $result['mail_sent'],
				'message' => $result['message'],
				'spam' => $result['spam'] );
		}

		$wpcf7_contact_form = null;
	}
}

function wpcf7_submit( $ajax = false ) {
	global $wpcf7_contact_form;

	if ( ! is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		return false;

	$result = array(
		'valid' => true,
		'invalid_reasons' => array(),
		'spam' => false,
		'message' => '',
		'mail_sent' => false,
		'scripts_on_sent_ok' => null );

	$validation = $wpcf7_contact_form->validate();

	if ( ! $validation['valid'] ) { // Validation error occured
		$result['valid'] = false;
		$result['invalid_reasons'] = $validation['reason'];
		$result['message'] = wpcf7_get_message( 'validation_error' );

	} elseif ( ! apply_filters( 'wpcf7_acceptance', true ) ) { // Not accepted terms
		$result['message'] = wpcf7_get_message( 'accept_terms' );

	} elseif ( apply_filters( 'wpcf7_spam', false ) ) { // Spam!
		$result['message'] = wpcf7_get_message( 'spam' );
		$result['spam'] = true;

	} elseif ( $wpcf7_contact_form->mail() ) {
		$result['mail_sent'] = true;
		$result['message'] = wpcf7_get_message( 'mail_sent_ok' );

		do_action_ref_array( 'wpcf7_mail_sent', array( &$wpcf7_contact_form ) );

		if ( $ajax ) {
			$on_sent_ok = $wpcf7_contact_form->additional_setting( 'on_sent_ok', false );

			if ( ! empty( $on_sent_ok ) )
				$result['scripts_on_sent_ok'] = array_map( 'wpcf7_strip_quote', $on_sent_ok );
		} else {
			$wpcf7_contact_form->clear_post();
		}

	} else {
		$result['message'] = wpcf7_get_message( 'mail_sent_ng' );
	}

	// remove upload files
	foreach ( (array) $wpcf7_contact_form->uploaded_files as $name => $path ) {
		@unlink( $path );
	}

	return $result;
}

add_action( 'the_post', 'wpcf7_the_post' );

function wpcf7_the_post() {
	global $wpcf7;

	$wpcf7->processing_within = 'p' . get_the_ID();
	$wpcf7->unit_count = 0;
}

add_action( 'loop_end', 'wpcf7_loop_end' );

function wpcf7_loop_end() {
	global $wpcf7;

	$wpcf7->processing_within = '';
}

add_filter( 'widget_text', 'wpcf7_widget_text_filter', 9 );

function wpcf7_widget_text_filter( $content ) {
	global $wpcf7;

	if ( ! preg_match( '/\[\s*contact-form(-7)?\s.*?\]/', $content ) )
		return $content;

	$wpcf7->widget_count += 1;
	$wpcf7->processing_within = 'w' . $wpcf7->widget_count;
	$wpcf7->unit_count = 0;

	$content = do_shortcode( $content );

	$wpcf7->processing_within = '';

	return $content;
}

/* Shortcodes */

add_shortcode( 'contact-form-7', 'wpcf7_contact_form_tag_func' );
add_shortcode( 'contact-form', 'wpcf7_contact_form_tag_func' );

function wpcf7_contact_form_tag_func( $atts, $content = null, $code = '' ) {
	global $wpcf7, $wpcf7_contact_form;

	if ( is_feed() )
		return '[contact-form-7]';

	if ( 'contact-form-7' == $code ) {
		$atts = shortcode_atts( array( 'id' => 0, 'title' => '' ), $atts );
		$id = (int) $atts['id'];
		$wpcf7_contact_form = wpcf7_contact_form( $id );
	} else {
		if ( is_string( $atts ) )
			$atts = explode( ' ', $atts, 2 );

		$id = (int) array_shift( $atts );
		$wpcf7_contact_form = wpcf7_get_contact_form_by_old_id( $id );
	}

	if ( ! $wpcf7_contact_form )
		return '[contact-form-7 404 "Not Found"]';

	if ( $wpcf7->processing_within ) { // Inside post content or text widget
		$wpcf7->unit_count += 1;
		$unit_count = $wpcf7->unit_count;
		$processing_within = $wpcf7->processing_within;

	} else { // Inside template

		if ( ! isset( $wpcf7->global_unit_count ) )
			$wpcf7->global_unit_count = 0;

		$wpcf7->global_unit_count += 1;
		$unit_count = 1;
		$processing_within = 't' . $wpcf7->global_unit_count;
	}

	$unit_tag = 'wpcf7-f' . $wpcf7_contact_form->id . '-' . $processing_within . '-o' . $unit_count;
	$wpcf7_contact_form->unit_tag = $unit_tag;

	$form = $wpcf7_contact_form->form_html();

	$wpcf7_contact_form = null;

	return $form;
}

add_action( 'wp_head', 'wpcf7_head' );

function wpcf7_head() {
	// Cached?
	if ( wpcf7_script_is() && defined( 'WP_CACHE' ) && WP_CACHE ) :
?>
<script type="text/javascript">
//<![CDATA[
var _wpcf7 = { cached: 1 };
//]]>
</script>
<?php
	endif;
}

if ( WPCF7_LOAD_JS )
	add_action( 'wp_enqueue_scripts', 'wpcf7_enqueue_scripts' );

function wpcf7_enqueue_scripts() {
	// jquery.form.js originally bundled with WordPress is out of date and deprecated
	// so we need to deregister it and re-register the latest one
	wp_deregister_script( 'jquery-form' );
	wp_register_script( 'jquery-form', wpcf7_plugin_url( 'jquery.form.js' ),
		array( 'jquery' ), '2.52', true );

	$in_footer = true;
	if ( 'header' === WPCF7_LOAD_JS )
		$in_footer = false;

	wp_enqueue_script( 'contact-form-7', wpcf7_plugin_url( 'scripts.js' ),
		array( 'jquery', 'jquery-form' ), WPCF7_VERSION, $in_footer );

	do_action( 'wpcf7_enqueue_scripts' );
}

function wpcf7_script_is() {
	return wp_script_is( 'contact-form-7' );
}

if ( WPCF7_LOAD_CSS )
	add_action( 'wp_enqueue_scripts', 'wpcf7_enqueue_styles' );

function wpcf7_enqueue_styles() {
	wp_enqueue_style( 'contact-form-7', wpcf7_plugin_url( 'styles.css' ),
		array(), WPCF7_VERSION, 'all' );

	if ( wpcf7_is_rtl() ) {
		wp_enqueue_style( 'contact-form-7-rtl', wpcf7_plugin_url( 'styles-rtl.css' ),
			array(), WPCF7_VERSION, 'all' );
	}

	do_action( 'wpcf7_enqueue_styles' );
}

function wpcf7_style_is() {
	return wp_style_is( 'contact-form-7' );
}

?>