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


/* Tag generator */

add_action( 'wpcf7_admin_footer', 'wpcf7_tg_pane_checkbox' );

function wpcf7_tg_pane_checkbox( &$contact_form ) {
	wpcf7_tg_pane_checkbox_and_radio( 'checkbox' );
	wpcf7_tg_pane_checkbox_and_radio( 'radio' );
}

function wpcf7_tg_pane_checkbox_and_radio( $type = 'checkbox' ) {
	if ( 'radio' != $type )
		$type = 'checkbox';

?>
<div id="wpcf7-tg-pane-<?php echo $type; ?>" class="hidden">
<form action="">
<input type="hidden" name="type" value="<?php echo $type; ?>" />
<table>
<?php if ( 'checkbox' == $type ) : ?>
<tr><td><input type="checkbox" name="required" />&nbsp;<?php echo esc_html( __( 'Required field?', 'wpcf7' ) ); ?></td></tr>
<?php endif; ?>

<tr><td><?php echo esc_html( __( 'Name', 'wpcf7' ) ); ?><br /><input type="text" name="name" class="tg-name oneline" /></td><td></td></tr>
</table>

<table>
<tr>
<td><code>id</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="id" class="idvalue oneline" /></td>

<td><code>class</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="class" class="classvalue oneline" /></td>
</tr>

<tr>
<td><?php echo esc_html( __( 'Choices', 'wpcf7' ) ); ?><br />
<textarea name="values"></textarea><br />
<span style="font-size: smaller"><?php echo esc_html( __( "* One choice per line.", 'wpcf7' ) ); ?></span>
</td>

<?php if ( 'checkbox' == $type ) : ?>
<td>
<br /><input type="checkbox" name="exclusive" class="option" />&nbsp;<?php echo esc_html( __( 'Make checkboxes exclusive?', 'wpcf7' ) ); ?>
</td>
<?php endif; ?>
</tr>
</table>

<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'wpcf7' ) ); ?><br /><input type="text" class="tag" readonly="readonly" onfocus="this.select()" /></div>
</form>
</div>
<?php
}

?>