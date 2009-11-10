<?php
/**
** A base module for [text], [text*], [email], and [email*]
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'text', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'text*', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'email', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'email*', 'wpcf7_text_shortcode_handler', true );

function wpcf7_text_shortcode_handler( $tag ) {
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
	$size_att = '';
	$maxlength_att = '';

	if ( 'email' == $type || 'email*' == $type )
		$class_att .= ' wpcf7-validates-as-email';

	if ( 'text*' == $type || 'email*' == $type )
		$class_att .= ' wpcf7-validates-as-required';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^([0-9]*)[/x]([0-9]*)$%', $option, $matches ) ) {
			$size_att = (int) $matches[1];
			$maxlength_att = (int) $matches[2];
		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	if ( $size_att )
		$atts .= ' size="' . $size_att . '"';
	else
		$atts .= ' size="40"'; // default size

	if ( $maxlength_att )
		$atts .= ' maxlength="' . $maxlength_att . '"';

	// Value
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) && $wpcf7_contact_form->is_posted() ) {
		if ( isset( $_POST['_wpcf7_mail_sent'] ) && $_POST['_wpcf7_mail_sent']['ok'] )
			$value = '';
		else
			$value = $_POST[$name];
	} else {
		$value = $values[0];
	}

	$html = '<input type="text" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $atts . ' />';

	$validation_error = '';
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		$validation_error = $wpcf7_contact_form->validation_error( $name );

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_text', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_text*', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_email', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_email*', 'wpcf7_text_validation_filter', 10, 2 );

function wpcf7_text_validation_filter( $result, $tag ) {
	global $wpcf7_contact_form;

	$type = $tag['type'];
	$name = $tag['name'];

	$_POST[$name] = trim( strtr( (string) $_POST[$name], "\n", " " ) );

	if ( 'text*' == $type ) {
		if ( '' == $_POST[$name] ) {
			$result['valid'] = false;
			$result['reason'][$name] = $wpcf7_contact_form->message( 'invalid_required' );
		}
	}

	if ( 'email' == $type || 'email*' == $type ) {
		if ( 'email*' == $type && '' == $_POST[$name] ) {
			$result['valid'] = false;
			$result['reason'][$name] = $wpcf7_contact_form->message( 'invalid_required' );
		} elseif ( '' != $_POST[$name] && ! is_email( $_POST[$name] ) ) {
			$result['valid'] = false;
			$result['reason'][$name] = $wpcf7_contact_form->message( 'invalid_email' );
		}
	}

	return $result;
}

?>