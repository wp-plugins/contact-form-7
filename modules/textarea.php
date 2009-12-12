<?php
/**
** A base module for [textarea] and [textarea*]
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'textarea', 'wpcf7_textarea_shortcode_handler', true );
wpcf7_add_shortcode( 'textarea*', 'wpcf7_textarea_shortcode_handler', true );

function wpcf7_textarea_shortcode_handler( $tag ) {
	global $wpcf7_contact_form;

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];
	$content = $tag['content'];

	if ( empty( $name ) )
		return '';

	$atts = '';
	$id_att = '';
	$class_att = '';
	$cols_att = '';
	$rows_att = '';

	if ( 'textarea*' == $type )
		$class_att .= ' wpcf7-validates-as-required';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^([0-9]*)[x/]([0-9]*)$%', $option, $matches ) ) {
			$cols_att = (int) $matches[1];
			$rows_att = (int) $matches[2];
		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	if ( $cols_att )
		$atts .= ' cols="' . $cols_att . '"';
	else
		$atts .= ' cols="40"'; // default size

	if ( $rows_att )
		$atts .= ' rows="' . $rows_att . '"';
	else
		$atts .= ' rows="10"'; // default size

	// Value
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) && $wpcf7_contact_form->is_posted() ) {
		if ( isset( $_POST['_wpcf7_mail_sent'] ) && $_POST['_wpcf7_mail_sent']['ok'] )
			$value = '';
		else
			$value = $_POST[$name];
	} else {
		$value = $values[0];

		if ( ! empty( $content ) )
			$value = $content;
	}

	$html = '<textarea name="' . $name . '"' . $atts . '>' . esc_html( $value ) . '</textarea>';

	$validation_error = '';
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		$validation_error = $wpcf7_contact_form->validation_error( $name );

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_textarea', 'wpcf7_textarea_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_textarea*', 'wpcf7_textarea_validation_filter', 10, 2 );

function wpcf7_textarea_validation_filter( $result, $tag ) {
	global $wpcf7_contact_form;

	$type = $tag['type'];
	$name = $tag['name'];

	$_POST[$name] = (string) $_POST[$name];

	if ( 'textarea*' == $type ) {
		if ( '' == $_POST[$name] ) {
			$result['valid'] = false;
			$result['reason'][$name] = $wpcf7_contact_form->message( 'invalid_required' );
		}
	}

	return $result;
}


/* Tag generator */

add_action( 'wpcf7_admin_footer', 'wpcf7_tg_pane_textarea' );

function wpcf7_tg_pane_textarea( &$contact_form ) {
?>
<div id="wpcf7-tg-pane-textarea" class="hidden">
<form action="">
<input type="hidden" name="type" value="textarea" />
<table>
<tr><td><input type="checkbox" name="required" />&nbsp;<?php echo esc_html( __( 'Required field?', 'wpcf7' ) ); ?></td></tr>
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
<td><code>cols</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="cols" class="numeric oneline" /></td>

<td><code>rows</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="rows" class="numeric oneline" /></td>
</tr>

<tr>
<td><?php echo esc_html( __( 'Default value', 'wpcf7' ) ); ?> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br /><input type="text" name="default-value" class="oneline" /></td>
</tr>
</table>

<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'wpcf7' ) ); ?><br /><input type="text" class="tag" readonly="readonly" onfocus="this.select()" /></div>
</form>
</div>
<?php
}

?>