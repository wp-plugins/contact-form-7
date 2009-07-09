<?php
/**
** A base module for [textarea] and [textarea*]
**/

function wpcf7_textarea_shortcode_handler( $tag ) {
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

	if ( preg_match( '/[*]$/', $type ) )
		$class_att .= ' wpcf7-validates-as-required';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	// Value
	if ( is_object( $wpcf7_contact_form ) && $wpcf7_contact_form->is_posted() ) {
		if ( isset( $_POST['_wpcf7_mail_sent'] ) && $_POST['_wpcf7_mail_sent']['ok'] )
			$value = '';
		else
			$value = $_POST[$name];
	} else {
		$value = $values[0];
	}

	if ( is_array( $options ) ) {
		$cols_rows_array = preg_grep( '%^[0-9]*[x/][0-9]*$%', $options );
		if ( $cols_rows = array_shift( $cols_rows_array ) ) {
			preg_match( '%^([0-9]*)[x/]([0-9]*)$%', $cols_rows, $cr_matches );
			if ( $cols = (int) $cr_matches[1] )
				$atts .= ' cols="' . $cols . '"';
			else
				$atts .= ' cols="40"';
			if ( $rows = (int) $cr_matches[2] )
				$atts .= ' rows="' . $rows . '"';
			else
				$atts .= ' rows="10"';
		} else {
			$atts .= ' cols="40" rows="10"';
		}
	}

	$html = '<textarea name="' . $name . '"' . $atts . '>' . esc_html( $value ) . '</textarea>';
	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}

wpcf7_add_shortcode( 'textarea', 'wpcf7_textarea_shortcode_handler', true );
wpcf7_add_shortcode( 'textarea*', 'wpcf7_textarea_shortcode_handler', true );

?>