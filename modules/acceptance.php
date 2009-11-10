<?php
/**
** A base module for [acceptance]
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'acceptance', 'wpcf7_acceptance_shortcode_handler', true );

function wpcf7_acceptance_shortcode_handler( $tag ) {
	global $wpcf7_contact_form;

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	if ( empty( $name ) )
		return '';

	$atts = '';
	$id_att = '';
	$class_att = '';

	$class_att .= ' wpcf7-acceptance';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( 'invert' == $option ) {
			$class_att .= ' wpcf7-invert';
		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	$default_on = (bool) preg_grep( '/^default:on$/i', $options );

	if ( WPCF7_LOAD_JS )
		$onclick = ' onclick="wpcf7ToggleSubmit(this.form);"';

	$checked = $default_on ? ' checked="checked"' : '';

	$html = '<input type="checkbox" name="' . $name . '" value="1"' . $atts . $onclick . $checked . ' />';

	return $html;
}


/* Acceptance filter */

add_filter( 'wpcf7_acceptance', 'wpcf7_acceptance_filter' );

function wpcf7_acceptance_filter( $accepted ) {
	global $wpcf7_contact_form;

	$fes = $wpcf7_contact_form->form_scan_shortcode( array( 'type' => 'acceptance' ) );

	foreach ( $fes as $fe ) {
		$name = $fe['name'];
		$options = (array) $fe['options'];

		if ( empty( $name ) )
			continue;

		$value = $_POST[$name] ? 1 : 0;

		$invert = (bool) preg_grep( '%^invert$%', $options );

		if ( $invert && $value || ! $invert && ! $value )
			$accepted = false;
	}

	return $accepted;
}

?>