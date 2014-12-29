<?php
/**
** A base module for [count], Twitter-like character count
**/

/* Shortcode handler */

add_action( 'wpcf7_init', 'wpcf7_add_shortcode_count' );

function wpcf7_add_shortcode_count() {
	wpcf7_add_shortcode( 'count', 'wpcf7_count_shortcode_handler', true );
}

function wpcf7_count_shortcode_handler( $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	if ( empty( $tag->name ) ) {
		return '';
	}

	$target = wpcf7_scan_shortcode( array( 'name' => $tag->name ) );
	$maxlength = $minlength = null;

	if ( $target ) {
		$target = new WPCF7_Shortcode( $target[0] );
		$maxlength = $target->get_maxlength_option();
		$minlength = $target->get_minlength_option();

		if ( $maxlength && $minlength && $maxlength < $minlength ) {
			$maxlength = $minlength = null;
		}
	}

	$atts = array();
	$atts['id'] = $tag->get_id_option();
	$atts['disabled'] = 'disabled';
	$atts['type'] = 'text';
	$atts['name'] = '_wpcf7_character_count_' . $tag->name;

	if ( $tag->has_option( 'down' ) ) {
		$atts['value'] = (int) $maxlength;
		$atts['class'] = $tag->get_class_option(
			wpcf7_form_controls_class( $tag->type, 'down' ) );
	} else {
		$atts['value'] = '0';
		$atts['class'] = $tag->get_class_option(
			wpcf7_form_controls_class( $tag->type, 'up' ) );
	}

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf( '<input %1$s />', $atts );

	return $html;
}

?>