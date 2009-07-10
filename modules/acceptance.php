<?php
/**
** A base module for [acceptance]
**/

function wpcf7_acceptance_shortcode_handler( $tag ) {
	global $wpcf7_contact_form;

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

	$class_att .= ' wpcf7-acceptance';
	if ( preg_grep( '%^invert$%', $options ) )
		$class_att .= ' wpcf7-invert';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	$default = (bool) preg_grep( '%^default:on$%', $options );

	$onclick = ' onclick="wpcf7ToggleSubmit(this.form);"';
	$checked = $default ? ' checked="checked"' : '';
	$html = '<input type="checkbox" name="' . $name . '" value="1"' . $atts . $onclick . $checked . ' />';

	return $html;
}

wpcf7_add_shortcode( 'acceptance', 'wpcf7_acceptance_shortcode_handler', true );

?>