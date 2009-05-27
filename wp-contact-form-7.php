<?php
/*
Plugin Name: Contact Form 7
Plugin URI: http://ideasilo.wordpress.com/2007/04/30/contact-form-7/
Description: Just another contact form plugin. Simple but flexible.
Author: Takayuki Miyoshi
Version: 1.9.5.1
Author URI: http://ideasilo.wordpress.com/
*/

/*  Copyright 2007-2009 Takayuki Miyoshi (email: takayukister at gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define( 'WPCF7_VERSION', '1.9.5.1' );

if ( ! defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

if ( ! defined( 'WP_CONTENT_URL' ) )
	define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );

if ( ! defined( 'WP_PLUGIN_DIR' ) )
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

if ( ! defined( 'WP_PLUGIN_URL' ) )
	define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );

if ( ! defined( 'WPCF7_PLUGIN_DIR' ) )
	define( 'WPCF7_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) );

if ( ! defined( 'WPCF7_PLUGIN_URL' ) )
	define( 'WPCF7_PLUGIN_URL', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) );

if ( ! defined( 'WPCF7_PLUGIN_BASENAME' ) )
	define( 'WPCF7_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! defined( 'WPCF7_LOAD_JS' ) )
	define( 'WPCF7_LOAD_JS', true );

if ( ! defined( 'WPCF7_AUTOP' ) )
	define( 'WPCF7_AUTOP', true );

if ( ! defined( 'WPCF7_USE_PIPE' ) )
	define( 'WPCF7_USE_PIPE', true );

/* If you or your client hate to see about donation, set this value false. */
if ( ! defined( 'WPCF7_SHOW_DONATION_LINK' ) )
	define( 'WPCF7_SHOW_DONATION_LINK', true );

if ( ! defined( 'WPCF7_ADMIN_READ_CAPABILITY' ) )
	define( 'WPCF7_ADMIN_READ_CAPABILITY', 'edit_posts' );

if ( ! defined( 'WPCF7_ADMIN_READ_WRITE_CAPABILITY' ) )
	define( 'WPCF7_ADMIN_READ_WRITE_CAPABILITY', 'publish_pages' );

require_once WPCF7_PLUGIN_DIR . '/includes/classes.php';
require_once WPCF7_PLUGIN_DIR . '/includes/functions.php';
require_once WPCF7_PLUGIN_DIR . '/includes/formatting.php';
require_once WPCF7_PLUGIN_DIR . '/includes/mail.php';
require_once WPCF7_PLUGIN_DIR . '/includes/pipe.php';
require_once WPCF7_PLUGIN_DIR . '/includes/akismet.php';
require_once WPCF7_PLUGIN_DIR . '/includes/acceptance.php';
require_once WPCF7_PLUGIN_DIR . '/includes/quiz.php';
require_once WPCF7_PLUGIN_DIR . '/includes/captcha.php';
require_once WPCF7_PLUGIN_DIR . '/includes/upload.php';

if ( is_admin() )
	require_once WPCF7_PLUGIN_DIR . '/admin/admin.php';

$wpcf7_contact_forms = null;

function wpcf7_contact_forms() {
	global $wpcf7_contact_forms;

	if ( is_array( $wpcf7_contact_forms ) )
		return $wpcf7_contact_forms;

	$wpcf7 = get_option( 'wpcf7' );
	$wpcf7_contact_forms = $wpcf7['contact_forms'];

	if ( ! is_array( $wpcf7_contact_forms ) )
		$wpcf7_contact_forms = array();

	return $wpcf7_contact_forms;
}

function wpcf7_update_contact_forms( $contact_forms ) {
	$wpcf7 = get_option( 'wpcf7' );
	$wpcf7['contact_forms'] = $contact_forms;

	update_option( 'wpcf7', $wpcf7 );
}

$wpcf7_posted_data = null;

$wpcf7_processing_within = null;
$wpcf7_unit_count = null;
$wpcf7_widget_count = null;

function wpcf7_ajax_json_echo() {
	$echo = '';

	if ( isset( $_POST['_wpcf7'] ) ) {
		$id = (int) $_POST['_wpcf7'];
		$unit_tag = $_POST['_wpcf7_unit_tag'];
		$contact_forms = wpcf7_contact_forms();

		if ( $cf = wpcf7_contact_form( $contact_forms[$id] ) ) {
			$validation = $cf->validate();

			$handled_uploads = wpcf7_handle_uploads( $cf );
			if ( ! $handled_uploads['validation']['valid'] )
				$validation['valid'] = false;

			$validation['reason'] = array_merge( $validation['reason'], $handled_uploads['validation']['reason'] );

			$captchas = wpcf7_refill_captcha( $cf );

			if ( ! empty( $captchas ) ) {
				$captchas_js = array();
				foreach ( $captchas as $name => $cap ) {
					$captchas_js[] = '"' . $name . '": "' . $cap . '"';
				}
				$captcha = '{ ' . join( ', ', $captchas_js ) . ' }';
			} else {
				$captcha = 'null';
			}

			$quizzes = wpcf7_refill_quiz( $cf );

			if ( ! empty( $quizzes ) ) {
				$quizzes_js = array();
				foreach ( $quizzes as $name => $q ) {
					$quizzes_js[] = '"' . $name . '": [ "' . js_escape( $q[0] ) . '", "' . $q[1] . '" ]';
				}
				$quiz = '{ ' . join( ', ', $quizzes_js ) . ' }';
			} else {
				$quiz = 'null';
			}

			$on_sent_ok_settings = $cf->additional_setting( 'on_sent_ok', false );
			if ( ! empty( $on_sent_ok_settings ) ) {
				$on_sent_ok = '[ ' . implode( ', ', $on_sent_ok_settings ) . ' ]';
			} else {
				$on_sent_ok = 'null';
			}

			if ( ! $validation['valid'] ) { // Validation error occured
				$invalids = array();
				foreach ( $validation['reason'] as $name => $reason ) {
					$invalids[] = '{ into: "span.wpcf7-form-control-wrap.' . $name . '", message: "' . js_escape( $reason ) . '" }';
				}
				$invalids = '[' . join( ', ', $invalids ) . ']';
				$echo = '{ mailSent: 0, message: "' . js_escape( $cf->message( 'validation_error' ) ) . '", into: "#' . $unit_tag . '", invalids: ' . $invalids . ', captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
			} elseif ( ! wpcf7_acceptance( $cf ) ) { // Not accepted terms
				$echo = '{ mailSent: 0, message: "' . js_escape( $cf->message( 'accept_terms' ) ) . '", into: "#' . $unit_tag . '", captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
			} elseif ( wpcf7_akismet( $cf ) ) { // Spam!
				$echo = '{ mailSent: 0, message: "' . js_escape( $cf->message( 'akismet_says_spam' ) ) . '", into: "#' . $unit_tag . '", spam: 1, captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
			} elseif ( wpcf7_mail( $cf, $handled_uploads['files'] ) ) {
				$echo = '{ mailSent: 1, message: "' . js_escape( $cf->message( 'mail_sent_ok' ) ) . '", into: "#' . $unit_tag . '", captcha: ' . $captcha . ', quiz: ' . $quiz . ', onSentOk: ' . $on_sent_ok . ' }';
			} else {
				$echo = '{ mailSent: 0, message: "' . js_escape( $cf->message( 'mail_sent_ng' ) ) . '", into: "#' . $unit_tag . '", captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
			}

			// remove upload files
			foreach ( $handled_uploads['files'] as $name => $path ) {
				@unlink( $path );
			}
		}
	}

	if ( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo $echo;
	} else {
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		echo '<textarea>' . $echo . '</textarea>';
	}
}

function wpcf7_process_nonajax_submitting() {
	if ( ! isset($_POST['_wpcf7'] ) )
		return;

	$id = (int) $_POST['_wpcf7'];
	$contact_forms = wpcf7_contact_forms();
	if ( $cf = wpcf7_contact_form( $contact_forms[$id] ) ) {
		$validation = $cf->validate();

		$handled_uploads = wpcf7_handle_uploads( $cf );

		if ( ! $handled_uploads['validation']['valid'] )
			$validation['valid'] = false;

		$validation['reason'] = array_merge( $validation['reason'], $handled_uploads['validation']['reason'] );

		if ( ! $validation['valid'] ) {
			$_POST['_wpcf7_validation_errors'] = array( 'id' => $id, 'messages' => $validation['reason'] );
		} elseif ( ! wpcf7_acceptance( $cf ) ) { // Not accepted terms
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => $cf->message( 'accept_terms' ) );
		} elseif ( wpcf7_akismet( $cf ) ) { // Spam!
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => $cf->message( 'akismet_says_spam' ), 'spam' => true );
		} elseif ( wpcf7_mail( $cf, $handled_uploads['files'] ) ) {
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => true, 'message' => $cf->message( 'mail_sent_ok' ) );
		} else {
			$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => $cf->message( 'mail_sent_ng' ) );
		}

		// remove upload files
		foreach ( $handled_uploads['files'] as $name => $path ) {
			@unlink( $path );
		}
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
	global $wpcf7_unit_count, $wpcf7_processing_within;

	if ( is_string( $atts ) )
		$atts = explode( ' ', $atts, 2 );

	$atts = (array) $atts;

	$id = (int) array_shift( $atts );

	$contact_forms = wpcf7_contact_forms();

	if ( ! ( $cf = wpcf7_contact_form( $contact_forms[$id] ) ) )
		return '[contact-form 404 "Not Found"]';

	$cf->id = $id;

	$wpcf7_unit_count += 1;

	$unit_tag = 'wpcf7-f' . $id . '-' . $wpcf7_processing_within . '-o' . $wpcf7_unit_count;
	$cf->unit_tag = $unit_tag;

	$form = $cf->form_html();

	return $form;
}

add_shortcode( 'contact-form', 'wpcf7_contact_form_tag_func' );

function wpcf7_wp_head() {
	$stylesheet_url = WPCF7_PLUGIN_URL . '/stylesheet.css';
	echo '<link rel="stylesheet" href="' . $stylesheet_url . '" type="text/css" />';

	if ( 'rtl' == get_bloginfo( 'text_direction' ) ) {
		$stylesheet_rtl_url = WPCF7_PLUGIN_URL . '/stylesheet-rtl.css';
		echo '<link rel="stylesheet" href="' . $stylesheet_rtl_url . '" type="text/css" />';
	}
}

add_action( 'wp_head', 'wpcf7_wp_head' );

function wpcf7_enqueue_scripts() {
	wp_enqueue_script( 'contact-form-7', WPCF7_PLUGIN_URL . '/contact-form-7.js',
		array('jquery', 'jquery-form'), WPCF7_VERSION, true );
}

if ( ! is_admin() && WPCF7_LOAD_JS )
	add_action( 'init', 'wpcf7_enqueue_scripts' );

function wpcf7_init_switch() {
	if ( 'POST' == $_SERVER['REQUEST_METHOD'] && 1 == (int) $_POST['_wpcf7_is_ajax_call'] ) {
		wpcf7_ajax_json_echo();
		exit();
	} elseif ( ! is_admin() ) {
		wpcf7_process_nonajax_submitting();
		wpcf7_cleanup_captcha_files();
		wpcf7_cleanup_upload_files();
	}
}

add_action( 'init', 'wpcf7_init_switch', 11 );

function wpcf7_load_plugin_textdomain() { // l10n
	global $wp_version;

	if ( version_compare( $wp_version, '2.6', '<' ) ) // Using old WordPress
		load_plugin_textdomain( 'wpcf7', 'wp-content/plugins/contact-form-7/languages' );
	else
		load_plugin_textdomain( 'wpcf7', 'wp-content/plugins/contact-form-7/languages', 'contact-form-7/languages' );
}

add_action( 'init', 'wpcf7_load_plugin_textdomain' );

function wpcf7_set_initial() {
	wpcf7_load_plugin_textdomain();

	$wpcf7 = get_option( 'wpcf7' );
	if ( ! is_array( $wpcf7 ) )
		$wpcf7 = array();

	$contact_forms = $wpcf7['contact_forms'];
	if ( ! is_array( $contact_forms ) )
		$contact_forms = array();

	if ( 0 == count( $contact_forms ) )
		$contact_forms[1] = wpcf7_default_pack( __( 'Contact form', 'wpcf7' ) . ' 1' );

	$wpcf7['contact_forms'] = $contact_forms;
	update_option( 'wpcf7', $wpcf7 );
}

add_action( 'activate_' . WPCF7_PLUGIN_BASENAME, 'wpcf7_set_initial' );

function wpcf7_contact_form( $data ) {
	if ( ! $data )
		return false;

	$data = stripslashes_deep( $data );

	$contact_form = new WPCF7_ContactForm();

	$contact_form->title = $data['title'];
	$contact_form->form = $data['form'];
	$contact_form->mail = $data['mail'];
	$contact_form->mail_2 = $data['mail_2'];
	$contact_form->messages = $data['messages'];
	$contact_form->additional_settings = $data['additional_settings'];
	$contact_form->options = $data['options'];

	$contact_form->upgrade();

	return $contact_form;
}

?>