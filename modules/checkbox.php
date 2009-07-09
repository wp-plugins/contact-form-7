<?php
/**
** A base module for [checkbox], [checkbox*], and [radio]
**/

function wpcf7_checkbox_shortcode_handler( $tag ) {
	global $wpdb, $wpcf7_contact_form;

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	if ( is_object( $wpcf7_contact_form ) && $wpcf7_contact_form->is_posted() ) {
		$validation_error = $_POST['_wpcf7_validation_errors']['messages'][$name];
		$validation_error = $validation_error ? '<span class="wpcf7-not-valid-tip-no-ajax">' . esc_html( $validation_error ) . '</span>' : '';
	} else {
		$validation_error = '';
	}

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

	if ( preg_match( '/^checkbox[*]?$/', $type ) )
		$class_att .= ' wpcf7-checkbox';

	if ( 'radio' == $type )
		$class_att .= ' wpcf7-radio';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	$scr_defaults = array_values( preg_grep( '/^default:/', $options ) );
	preg_match( '/^default:([0-9_]+)$/', $scr_defaults[0], $scr_default_matches );
	$scr_default = explode( '_', $scr_default_matches[1] );

	$multiple = preg_match( '/^checkbox[*]?$/', $type ) && ! preg_grep( '%^exclusive$%', $options );
	$html = '';

	if ( preg_match( '/^checkbox[*]?$/', $type ) && ! $multiple )
		$onclick = ' onclick="wpcf7ExclusiveCheckbox(this);"';

	$input_type = rtrim( $type, '*' );

	foreach ( $values as $key => $value ) {
		$checked = '';
		if ( in_array( $key + 1, (array) $scr_default ) )
			$checked = ' checked="checked"';
		if ( is_object( $wpcf7_contact_form ) && $wpcf7_contact_form->is_posted() && (
				$multiple && in_array( $wpdb->escape( $value ), (array) $_POST[$name] ) ||
				! $multiple && $_POST[$name] == $wpdb->escape( $value ) ) )
			$checked = ' checked="checked"';
		if ( preg_grep( '%^label[_-]?first$%', $options ) ) { // put label first, input last
			$item = '<span class="wpcf7-list-item-label">' . $value . '</span>&nbsp;';
			$item .= '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . esc_attr( $value ) . '"' . $checked . $onclick . ' />';
		} else {
			$item = '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . esc_attr( $value ) . '"' . $checked . $onclick . ' />';
			$item .= '&nbsp;<span class="wpcf7-list-item-label">' . $value . '</span>';
		}

		if ( preg_grep( '%^use[_-]?label[_-]?element$%', $options ) )
			$item = '<label>' . $item . '</label>';

		$item = '<span class="wpcf7-list-item">' . $item . '</span>';
		$html .= $item;
	}

	$html = '<span' . $atts . '>' . $html . '</span>';
	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}

wpcf7_add_shortcode( 'checkbox', 'wpcf7_checkbox_shortcode_handler', true );
wpcf7_add_shortcode( 'checkbox*', 'wpcf7_checkbox_shortcode_handler', true );
wpcf7_add_shortcode( 'radio', 'wpcf7_checkbox_shortcode_handler', true );

?>