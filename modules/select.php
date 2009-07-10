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

	$atts = '';
	$id_att = '';
	$class_att = '';

	$defaults = array();

	if ( 'select*' == $type )
		$class_att .= ' wpcf7-validates-as-required';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '/^default:([0-9_]+)$/', $option, $matches ) ) {
			$defaults = explode( '_', $matches[1] );
		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	$multiple = (bool) preg_grep( '%^multiple$%', $options );
	$include_blank = (bool) preg_grep( '%^include_blank$%', $options );

	$empty_select = empty( $values );
	if ( $empty_select || $include_blank )
		array_unshift( $values, '---' );

	$html = '';

	$posted = is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) && $wpcf7_contact_form->is_posted();

	foreach ( $values as $key => $value ) {
		$selected = false;

		if ( ! $empty_select && in_array( $key + 1, (array) $defaults ) )
			$selected = true;

		if ( $posted ) {
			if ( $multiple && in_array( $wpdb->escape( $value ), (array) $_POST[$name] ) )
				$selected = true;
			if ( ! $multiple && $_POST[$name] == $wpdb->escape( $value ) )
				$selected = true;
		}

		$selected = $selected ? ' selected="selected"' : '';

		$html .= '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
	}

	if ( $multiple )
		$atts .= ' multiple="multiple"';

	$html = '<select name="' . $name . ( $multiple ? '[]' : '' ) . '"' . $atts . '>' . $html . '</select>';

	$validation_error = '';
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		$validation_error = $wpcf7_contact_form->validation_error( $name );

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}

wpcf7_add_shortcode( 'select', 'wpcf7_select_shortcode_handler', true );
wpcf7_add_shortcode( 'select*', 'wpcf7_select_shortcode_handler', true );

?>