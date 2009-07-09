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