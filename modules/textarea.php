<?php
/**
** A base module for [textarea] and [textarea*]
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'textarea', 'wpcf7_textarea_shortcode_handler', true );
wpcf7_add_shortcode( 'textarea*', 'wpcf7_textarea_shortcode_handler', true );

function wpcf7_textarea_shortcode_handler( $tag ) {
	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];
	$content = $tag['content'];

	if ( empty( $name ) )
		return '';

	$validation_error = wpcf7_get_validation_error( $name );

	$atts = '';
	$id_att = '';
	$class_att = '';
	$cols_att = '';
	$rows_att = '';
	$maxlength_att = '';
	$tabindex_att = '';
	$placeholder_att = '';

	$class_att = wpcf7_form_controls_class( $type );

	if ( $validation_error )
		$class_att .= ' wpcf7-not-valid';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^(?:([0-9]*)x([0-9]*))?(?:/([0-9]+))?$%', $option, $matches ) ) {
			if ( isset( $matches[1] ) )
				$cols_att = (int) $matches[1];

			if ( isset( $matches[2] ) )
				$rows_att = (int) $matches[2];

			if ( isset( $matches[3] ) )
				$maxlength_att = (int) $matches[3];

		} elseif ( preg_match( '%^tabindex:(\d+)$%', $option, $matches ) ) {
			$tabindex_att = (int) $matches[1];

		}
	}

	$value = (string) reset( $values );

	if ( ! empty( $content ) )
		$value = $content;


	if ( preg_grep( '%^placeholder|watermark$%', $options ) ) {
		$placeholder_att = $value;
		$value = '';
	}

	if ( wpcf7_is_posted() && isset( $_POST[$name] ) )
		$value = stripslashes_deep( $_POST[$name] );

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

	if ( $maxlength_att )
		$atts .= ' maxlength="' . $maxlength_att . '"';

	if ( '' !== $tabindex_att )
		$atts .= sprintf( ' tabindex="%d"', $tabindex_att );

	if ( $placeholder_att )
		$atts .= sprintf( ' placeholder="%s"', esc_attr( $placeholder_att ) );

	$html = '<textarea name="' . $name . '"' . $atts . '>' . esc_textarea( $value ) . '</textarea>';

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_textarea', 'wpcf7_textarea_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_textarea*', 'wpcf7_textarea_validation_filter', 10, 2 );

function wpcf7_textarea_validation_filter( $result, $tag ) {
	$type = $tag['type'];
	$name = $tag['name'];

	$value = isset( $_POST[$name] ) ? (string) $_POST[$name] : '';

	if ( 'textarea*' == $type ) {
		if ( '' == $value ) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
		}
	}

	return $result;
}


/* Tag generator */

add_action( 'admin_init', 'wpcf7_add_tag_generator_textarea', 20 );

function wpcf7_add_tag_generator_textarea() {
	if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
		return;

	wpcf7_add_tag_generator( 'textarea', __( 'Text area', 'wpcf7' ),
		'wpcf7-tg-pane-textarea', 'wpcf7_tg_pane_textarea' );
}

function wpcf7_tg_pane_textarea( &$contact_form ) {
?>
<div id="wpcf7-tg-pane-textarea" class="hidden">
<form action="">
<table>
<tr><td><input type="checkbox" name="required" />&nbsp;<?php echo esc_html( __( 'Required field?', 'wpcf7' ) ); ?></td></tr>
<tr><td><?php echo esc_html( __( 'Name', 'wpcf7' ) ); ?><br /><input type="text" name="name" class="tg-name oneline" /></td><td></td></tr>
</table>

<table>
<tr>
<td><code>id</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="id" class="idvalue oneline option" /></td>

<td><code>class</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="class" class="classvalue oneline option" /></td>
</tr>

<tr>
<td><code>cols</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="number" name="cols" class="numeric oneline option" min="1" /></td>

<td><code>rows</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="number" name="rows" class="numeric oneline option" min="1" /></td>
</tr>

<tr>
<td><code>maxlength</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="number" name="maxlength" class="numeric oneline option" min="1" /></td>
</tr>

<tr>
<td><?php echo esc_html( __( 'Default value', 'wpcf7' ) ); ?> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br /><input type="text" name="values" class="oneline" /></td>

<td>
<br /><input type="checkbox" name="placeholder" class="option" />&nbsp;<?php echo esc_html( __( 'Use this text as placeholder?', 'wpcf7' ) ); ?>
</td>
</tr>
</table>

<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'wpcf7' ) ); ?><br /><input type="text" name="textarea" class="tag" readonly="readonly" onfocus="this.select()" /></div>

<div class="tg-mail-tag"><?php echo esc_html( __( "And, put this code into the Mail fields below.", 'wpcf7' ) ); ?><br /><span class="arrow">&#11015;</span>&nbsp;<input type="text" class="mail-tag" readonly="readonly" onfocus="this.select()" /></div>
</form>
</div>
<?php
}

?>