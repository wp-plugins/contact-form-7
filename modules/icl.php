<?php
/**
** ICL module for ICanLocalize translation service
**/

/* Shortcode handler */

function icl_wpcf7_shortcode_handler( $tag ) {

	if ( ! is_array( $tag ) )
		return '';

	$content = trim( $tag['content'] );
	if ( ! empty( $content ) )
		return icl_wpcf7_translate( $content, $content );

	$values = (array) $tag['values'];
	$value = trim( $values[0] );
	if ( ! empty( $value ) )
		return icl_wpcf7_translate( $value, $value );

	return '';
}

wpcf7_add_shortcode( 'icl', 'icl_wpcf7_shortcode_handler' );


/* Form tag filter */

function icl_wpcf7_form_tag_filter( $tag ) {
	if ( ! is_array( $tag ) )
		return $tag;

	$type = $tag['type'];
	$options = $tag['options'];
	$values = $tag['values'];
	$content = $tag['content'];

	if ( 'icl' != $type && ! in_array( 'icl', $options ) )
		return $tag;

	$new_values = array();
	foreach ( $values as $key => $value ) {
		$new_values[$key] = icl_wpcf7_translate( $value, $value );
	}

	if ( preg_match( '/^(?:text|email|textarea|captchar)[*]?$/', $type ) )
		$tag['values'] = $new_values;
	else
		$tag['labels'] = $new_values;

	$content = icl_wpcf7_translate( $content, $content );
	$tag['content'] = $content;

	return $tag;
}

add_filter( 'wpcf7_form_tag', 'icl_wpcf7_form_tag_filter' );


/* Message dispaly filter */

function icl_wpcf7_display_message_filter( $message ) {
	$shortcode_manager = new WPCF7_ShortcodeManager();
	$shortcode_manager->add_shortcode( 'icl', 'icl_wpcf7_display_message_shortcode_handler' );

	return $shortcode_manager->do_shortcode( $message );
}

function icl_wpcf7_display_message_shortcode_handler( $tag ) {
	if ( ! is_array( $tag ) )
		return '';

	$content = trim( $tag['content'] );
	if ( ! empty( $content ) )
		return icl_wpcf7_translate( $content, $content );

	$values = (array) $tag['values'];
	$value = trim( $values[0] );
	if ( ! empty( $value ) )
		return icl_wpcf7_translate( $value, $value );

	return '';
}

add_filter( 'wpcf7_display_message', 'icl_wpcf7_display_message_filter' );


/* Collecting strings hook after saving */

function icl_wpcf7_collect_strings( &$contact_form ) {
	$scanned = $contact_form->form_scan_shortcode();

	foreach ( $scanned as $tag ) {
		if ( ! is_array( $tag ) )
			continue;

		$type = $tag['type'];
		$options = (array) $tag['options'];
		$values = (array) $tag['values'];
		$content = $tag['content'];

		if ( ! ('icl' == $type || in_array( 'icl', $options ) ) )
			continue;

		if ( ! empty( $content ) ) {
			icl_wpcf7_register_string( $content, $content );

		} elseif ( ! empty( $values ) ) {
			foreach ( $values as $value ) {
				$value = trim( $value );
				icl_wpcf7_register_string( $value, $value );
			}
		}
	}

	/* From messages */

	$messages = (array) $contact_form->messages;

	$shortcode_manager = new WPCF7_ShortcodeManager();
	$shortcode_manager->add_shortcode( 'icl', create_function( '$tag', 'return null;' ) );

	foreach ( $messages as $message ) {
		$tags = $shortcode_manager->scan_shortcode( $message );
		foreach ( $tags as $tag ) {
			foreach ( (array) $tag["values"] as $v ) {
				icl_wpcf7_register_string( $v, $v );
			}
			icl_wpcf7_register_string( $tag["content"], $tag["content"] );
		}
	}
}

add_action( 'wpcf7_after_save', 'icl_wpcf7_collect_strings' );


/* Functions */

function icl_wpcf7_register_string( $name, $value ) {
	if ( ! function_exists( 'icl_register_string' ) )
		return false;

	$context = 'Contact Form 7';

	$value = trim( $value );
	if ( empty( $value ) )
		return false;

	icl_register_string( $context, $name, $value );
}

function icl_wpcf7_translate( $name, $value = '' ) {
	if ( ! function_exists( 'icl_t' ) )
		return $value;

	if ( empty( $name ) )
		return $value;

	$context = 'Contact Form 7';

	return icl_t( $context, $name, $value );
}

?>