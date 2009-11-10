<?php
/**
** A base module for [submit]
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'submit', 'wpcf7_submit_shortcode_handler' );

function wpcf7_submit_shortcode_handler( $tag ) {
	if ( ! is_array( $tag ) )
		return '';

	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	$atts = '';
	$id_att = '';
	$class_att = '';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	$value = $values[0];
	if ( empty( $value ) )
		$value = __( 'Send', 'wpcf7' );

	$ajax_loader_image_url = wpcf7_plugin_url( 'images/ajax-loader.gif' );

	$html = '<input type="submit" value="' . esc_attr( $value ) . '"' . $atts . ' />';
	$html .= ' <img class="ajax-loader" style="visibility: hidden;" alt="ajax loader" src="' . $ajax_loader_image_url . '" />';

	return $html;
}

?>