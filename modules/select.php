<?php
/**
** A base module for [select] and [select*]
**/

function wpcf7_select_shortcode_handler( $tag ) {
	global $wpdb, $wpcf7_contact_form;

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	$validation_error = '';
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		$validation_error = $wpcf7_contact_form->validation_error( $name );

	$atts = '';

	$id_array = preg_grep( '%^id:[-0-9a-zA-Z_]+$%', $options );
	if ( $id = array_shift( $id_array ) ) {
		preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $id, $id_matches );
		if ( $id = $id_matches[1] )
			$atts .= ' id="' . $id . '"';
	}

	$class_att = "";
	$class_array = preg_grep( '%^class:[-0-9a-zA-Z_]+$%', $options );
	foreach ( $class_array as $class ) {
		preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $class, $class_matches );
		if ( $class = $class_matches[1] )
			$class_att .= ' ' . $class;
	}

	if ( preg_match( '/[*]$/', $type ) )
		$class_att .= ' wpcf7-validates-as-required';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	$scr_defaults = array_values( preg_grep( '/^default:/', $options ) );
	preg_match( '/^default:([0-9_]+)$/', $scr_defaults[0], $scr_default_matches );
	$scr_default = explode( '_', $scr_default_matches[1] );

	$multiple = ( preg_grep( '%^multiple$%', $options ) ) ? true : false;
	$include_blank = preg_grep( '%^include_blank$%', $options );

	$empty_select = empty( $values );
	if ( $empty_select || $include_blank )
		array_unshift( $values, '---' );

	$html = '';
	foreach ( $values as $key => $value ) {
		$selected = '';
		if ( ! $empty_select && in_array( $key + 1, (array) $scr_default ) )
			$selected = ' selected="selected"';
		if ( is_object( $wpcf7_contact_form ) && $wpcf7_contact_form->is_posted() && (
				$multiple && in_array( $wpdb->escape( $value ), (array) $_POST[$name] ) ||
				! $multiple && $_POST[$name] == $wpdb->escape( $value ) ) )
			$selected = ' selected="selected"';
		$html .= '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
	}

	if ( $multiple )
		$atts .= ' multiple="multiple"';

	$html = '<select name="' . $name . ( $multiple ? '[]' : '' ) . '"' . $atts . '>' . $html . '</select>';
	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}

wpcf7_add_shortcode( 'select', 'wpcf7_select_shortcode_handler', true );
wpcf7_add_shortcode( 'select*', 'wpcf7_select_shortcode_handler', true );

?>