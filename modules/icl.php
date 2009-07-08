<?php
/**
** ICL module for ICanLocalize translation service
**/

function icl_wpcf7_shortcode_handler( $atts, $content = null ) {

	$subject = '';

	if ( is_array( $atts ) && is_array( $atts['values'] ) ) {
		foreach ( $atts['values'] as $value ) {
			$value = trim( $value );
			$subject .= icl_wpcf7_translate( $value );
		}
	}

	if ( ! empty( $content ) ) {
		$content = trim( $content );
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
	wpcf7_do_shortcode( $contact_form->form );
	$scanned = wpcf7_scanned_shortcodes( 'icl' );

	foreach ( $scanned as $item ) {
		if ( is_array( $item['attr'] ) && is_array( $item['attr']['values'] ) ) {
			foreach ( $item['attr']['values'] as $value ) {
				$value = trim( $value );
				icl_wpcf7_register_string( $value );
			}
		}

		if ( ! empty( $item['content'] ) )
			icl_wpcf7_register_string( $item['content'] );
	}
}

add_action( 'wpcf7_after_save', 'icl_wpcf7_collect_strings' );

function icl_wpcf7_register_string( $value ) {
	if ( ! function_exists( 'icl_register_string' ) )
		return false;

	icl_register_string( 'Contact Form 7 - Form Text', $value, $value );
}

?>