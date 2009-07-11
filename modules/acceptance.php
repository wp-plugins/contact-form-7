<?php
/**
** A base module for [acceptance]
**/

/* Shortcode handler */

function wpcf7_acceptance_shortcode_handler( $tag ) {
	global $wpcf7_contact_form;

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

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

	$onclick = ' onclick="wpcf7ToggleSubmit(this.form);"';
	$checked = $default_on ? ' checked="checked"' : '';
	$html = '<input type="checkbox" name="' . $name . '" value="1"' . $atts . $onclick . $checked . ' />';

	return $html;
}

wpcf7_add_shortcode( 'acceptance', 'wpcf7_acceptance_shortcode_handler', true );


/* Validation filter */

function wpcf7_acceptance_validation_filter( $result, $tag ) {
	$_POST[$name] = $_POST[$name] ? 1 : 0;

	return $result;
}

add_filter( 'wpcf7_validate_acceptance', 'wpcf7_acceptance_validation_filter', 10, 2 );

?>