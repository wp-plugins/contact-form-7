<?php
/**
** A base module for [checkbox], [checkbox*], and [radio]
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'checkbox', 'wpcf7_checkbox_shortcode_handler', true );
wpcf7_add_shortcode( 'checkbox*', 'wpcf7_checkbox_shortcode_handler', true );
wpcf7_add_shortcode( 'radio', 'wpcf7_checkbox_shortcode_handler', true );

function wpcf7_checkbox_shortcode_handler( $tag ) {
	global $wpcf7_contact_form;

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];
	$labels = (array) $tag['labels'];

	if ( empty( $name ) )
		return '';

	$atts = '';
	$id_att = '';
	$class_att = '';

	$defaults = array();

	$label_first = false;
	$use_label_element = false;

	if ( 'checkbox*' == $type )
		$class_att .= ' wpcf7-validates-as-required';

	if ( 'checkbox' == $type || 'checkbox*' == $type )
		$class_att .= ' wpcf7-checkbox';

	if ( 'radio' == $type )
		$class_att .= ' wpcf7-radio';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '/^default:([0-9_]+)$/', $option, $matches ) ) {
			$defaults = explode( '_', $matches[1] );

		} elseif ( preg_match( '%^label[_-]?first$%', $option ) ) {
			$label_first = true;

		} elseif ( preg_match( '%^use[_-]?label[_-]?element$%', $option ) ) {
			$use_label_element = true;

		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	$multiple = preg_match( '/^checkbox[*]?$/', $type ) && ! preg_grep( '%^exclusive$%', $options );

	$html = '';

	if ( preg_match( '/^checkbox[*]?$/', $type ) && ! $multiple && WPCF7_LOAD_JS )
		$onclick = ' onclick="wpcf7ExclusiveCheckbox(this);"';

	$input_type = rtrim( $type, '*' );

	$posted = is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) && $wpcf7_contact_form->is_posted();

	foreach ( $values as $key => $value ) {
		$checked = false;

		if ( in_array( $key + 1, (array) $defaults ) )
			$checked = true;

		if ( $posted) {
			if ( $multiple && in_array( esc_sql( $value ), (array) $_POST[$name] ) )
				$checked = true;
			if ( ! $multiple && $_POST[$name] == esc_sql( $value ) )
				$checked = true;
		}

		$checked = $checked ? ' checked="checked"' : '';

		if ( isset( $labels[$key] ) )
			$label = $labels[$key];
		else
			$label = $value;

		if ( $label_first ) { // put label first, input last
			$item = '<span class="wpcf7-list-item-label">' . esc_html( $label ) . '</span>&nbsp;';
			$item .= '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . esc_attr( $value ) . '"' . $checked . $onclick . ' />';
		} else {
			$item = '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . esc_attr( $value ) . '"' . $checked . $onclick . ' />';
			$item .= '&nbsp;<span class="wpcf7-list-item-label">' . esc_html( $label ) . '</span>';
		}

		if ( $use_label_element )
			$item = '<label>' . $item . '</label>';

		$item = '<span class="wpcf7-list-item">' . $item . '</span>';
		$html .= $item;
	}

	$html = '<span' . $atts . '>' . $html . '</span>';

	$validation_error = '';
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		$validation_error = $wpcf7_contact_form->validation_error( $name );

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_checkbox', 'wpcf7_checkbox_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_checkbox*', 'wpcf7_checkbox_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_radio', 'wpcf7_checkbox_validation_filter', 10, 2 );

function wpcf7_checkbox_validation_filter( $result, $tag ) {
	global $wpcf7_contact_form;

	$type = $tag['type'];
	$name = $tag['name'];
	$values = $tag['values'];

	if ( is_array( $_POST[$name] ) ) {
		foreach ( $_POST[$name] as $key => $value ) {
			$value = stripslashes( $value );
			if ( ! in_array( $value, (array) $values ) ) // Not in given choices.
				unset( $_POST[$name][$key] );
		}
	} else {
		$value = stripslashes( $_POST[$name] );
		if ( ! in_array( $value, (array) $values ) ) //  Not in given choices.
			$_POST[$name] = '';
	}

	if ( 'checkbox*' == $type ) {
		if ( empty( $_POST[$name] ) ) {
			$result['valid'] = false;
			$result['reason'][$name] = $wpcf7_contact_form->message( 'invalid_required' );
		}
	}

	return $result;
}

?>