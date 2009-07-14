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
		return icl_wpcf7_translate( $content );

	$values = (array) $tag['values'];
	$value = trim( $values[0] );
	if ( ! empty( $value ) )
		return icl_wpcf7_translate( $value );

	return '';
}

wpcf7_add_shortcode( 'icl', 'icl_wpcf7_shortcode_handler', true );


/* Form display text filter */

function icl_wpcf7_display_text_filter( $text, $tag ) {
	$options = (array) $tag['options'];
	if ( ! in_array( 'icl', $options ) )
		return $text;

	return icl_wpcf7_translate( trim( $text ) );
}

add_filter( 'wpcf7_display_text', 'icl_wpcf7_display_text_filter', 10, 2 );


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
		return icl_wpcf7_translate( $content, "Message" );

	$values = (array) $tag['values'];
	$value = trim( $values[0] );
	if ( ! empty( $value ) )
		return icl_wpcf7_translate( $value, "Message" );

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
		$name = $tag['name'];
		$options = (array) $tag['options'];
		$values = (array) $tag['values'];
		$content = $tag['content'];

		if ( ! ('icl' == $type || in_array( 'icl', $options ) ) )
			continue;

		if ( ! empty( $content ) ) {
			icl_wpcf7_register_string( $content, $name );

		} elseif ( ! empty( $values ) ) {
			foreach ( $values as $key => $value ) {
				$value = trim( $value );
				$s_name = ( ! empty( $name ) ) ? $name . '_#' . $key : '';
				icl_wpcf7_register_string( $value, $s_name );
			}
		}
	}

	/* From messages */

	$messages = (array) $contact_form->messages;

	$shortcode_manager = new WPCF7_ShortcodeManager();
	$shortcode_manager->add_shortcode( 'icl', create_function( '$tag', 'return null;' ), true );

	foreach ( $messages as $message ) {
		$tags = $shortcode_manager->scan_shortcode( $message );
		foreach ( $tags as $tag ) {
			$s_name = ( ! empty( $tag['name'] ) ) ? $tag['name'] : '';
			if ( ! empty( $tag["content"] ) ) {
				icl_wpcf7_register_string( $tag["content"], $s_name, "Message" );
			} elseif ( is_array( $tag["values"] ) && ! empty( $tag["values"] ) ) {
				icl_wpcf7_register_string( $tag["values"][0], $s_name, "Message" );
			}
		}
	}
}

add_action( 'wpcf7_after_save', 'icl_wpcf7_collect_strings' );


/* Functions */

function icl_wpcf7_register_string( $value, $name, $section = "Form" ) {
	if ( ! function_exists( 'icl_register_string' ) )
		return false;

	$value = trim( $value );
	if ( empty( $value ) )
		return false;

	$name = trim( $name );
	if ( empty( $name ) )
		$name = $value;

	icl_register_string( 'Contact Form 7 - ' . $section, $name, $value );
}

function icl_wpcf7_translate( $text, $section = "Form" ) {
	if ( ! function_exists( 'icl_t' ) || empty( $text ) )
		return $text;

	return icl_t( 'Contact Form 7 - ' . $section, $text );
}

?>