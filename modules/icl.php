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

function icl_wpcf7_translate( $text ) {
	if ( ! function_exists( 'icl_t' ) )
		return $text;

	return icl_t( 'Contact Form 7 - Form Text', $text );
}

function icl_wpcf7_collect_strings( &$contact_form ) {
	$scanned = wpcf7_scan_shortcode( $contact_form->form, 'icl' );

	foreach ( $scanned as $tag ) {
		if ( ! is_array( $tag ) )
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