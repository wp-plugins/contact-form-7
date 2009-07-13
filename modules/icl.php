<?php
/**
** ICL module for ICanLocalize translation service
**/

function icl_wpcf7_shortcode_handler( $tag ) {

	$subject = '';

	if ( ! is_array( $tag ) )
		return $subject;

	if ( is_array( $tag['values'] ) ) {
		foreach ( $tag['values'] as $value ) {
			$value = trim( $value );
			$subject .= icl_wpcf7_translate( $value );
		}
	}

	if ( ! empty( $tag['content'] ) ) {
		$content = trim( $tag['content'] );
		$subject .= icl_wpcf7_translate( $content );
	}

	return $subject;
}

wpcf7_add_shortcode( 'icl', 'icl_wpcf7_shortcode_handler' );

function icl_wpcf7_display_text_filter( $text ) {
	$text = trim( $text );
	return icl_wpcf7_translate( $text );
}

add_filter( 'wpcf7_display_text', 'icl_wpcf7_display_text_filter' );

function icl_wpcf7_translate( $text ) {
	if ( ! function_exists( 'icl_t' ) )
		return $text;

	return icl_t( 'Contact Form 7 - Form Text', $text );
}

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

		if ( is_array( $tag['values'] ) ) {
			foreach ( $tag['values'] as $value ) {
				$value = trim( $value );
				icl_wpcf7_register_string( $value );
			}
		}

		if ( ! empty( $tag['content'] ) )
			icl_wpcf7_register_string( $tag['content'] );
	}
}

add_action( 'wpcf7_after_save', 'icl_wpcf7_collect_strings' );

function icl_wpcf7_register_string( $value ) {
	if ( ! function_exists( 'icl_register_string' ) )
		return false;

	icl_register_string( 'Contact Form 7 - Form Text', $value, $value );
}

?>