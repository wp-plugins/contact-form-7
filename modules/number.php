<?php
/**
** A base module for the following types of tags:
** 	[number] and [number*]		# Number
** 	[range] and [range*]		# Range
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'number', 'wpcf7_number_shortcode_handler', true );
wpcf7_add_shortcode( 'number*', 'wpcf7_number_shortcode_handler', true );
wpcf7_add_shortcode( 'range', 'wpcf7_number_shortcode_handler', true );
wpcf7_add_shortcode( 'range*', 'wpcf7_number_shortcode_handler', true );

function wpcf7_number_shortcode_handler( $tag ) {
	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	if ( empty( $name ) )
		return '';

	$basetype = trim( $type, '*' );

	$validation_error = wpcf7_get_validation_error( $name );

	$atts = $id_att = $min_att = $max_att = $step_att = '';
	$tabindex_att = $placeholder_att = '';

	$class_att = wpcf7_form_controls_class( $type );

	$class_att .= ' wpcf7-validates-as-number';

	if ( $validation_error )
		$class_att .= ' wpcf7-not-valid';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^min:(-?[0-9]+)$%', $option, $matches ) ) {
			$min_att = $matches[1];

		} elseif ( preg_match( '%^max:(-?[0-9]+)$%', $option, $matches ) ) {
			$max_att = $matches[1];

		} elseif ( preg_match( '%^step:([1-9][0-9]*)$%', $option, $matches ) ) {
			$step_att = (int) $matches[1];

		} elseif ( preg_match( '%^tabindex:(\d+)$%', $option, $matches ) ) {
			$tabindex_att = (int) $matches[1];

		}
	}

	$value = (string) reset( $values );

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

	if ( '' !== $min_att )
		$atts .= ' min="' . $min_att . '"';

	if ( '' !== $max_att )
		$atts .= ' max="' . $max_att . '"';

	if ( '' !== $step_att )
		$atts .= ' step="' . $step_att . '"';

	if ( '' !== $tabindex_att )
		$atts .= sprintf( ' tabindex="%d"', $tabindex_att );

	if ( $placeholder_att )
		$atts .= sprintf( ' placeholder="%s"', esc_attr( $placeholder_att ) );

	if ( wpcf7_support_html5() ) {
		$type_att = $basetype;
	} else {
		$type_att = 'text';
	}

	$html = '<input type="' . $type_att . '" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $atts . ' />';

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_number', 'wpcf7_number_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_number*', 'wpcf7_number_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_range', 'wpcf7_number_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_range*', 'wpcf7_number_validation_filter', 10, 2 );

function wpcf7_number_validation_filter( $result, $tag ) {
	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];

	$value = isset( $_POST[$name] )
		? trim( strtr( (string) $_POST[$name], "\n", " " ) )
		: '';

	foreach ( $options as $option ) {
		if ( preg_match( '%^min:(-?[0-9]+)$%', $option, $matches ) ) {
			$min_att = $matches[1];
		} elseif ( preg_match( '%^max:(-?[0-9]+)$%', $option, $matches ) ) {
			$max_att = $matches[1];
		}
	}

	if ( '*' == substr( $type, -1 ) && '' == $value ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
	} elseif ( '' != $value && ! wpcf7_is_number( $value ) ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'invalid_number' );
	} elseif ( '' != $value && '' != $min_att && (float) $value < $min_att ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'number_too_small' );
	} elseif ( '' != $value && '' != $max_att && $max_att < (float) $value ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'number_too_large' );
	}

	return $result;
}


/* Messages */

add_filter( 'wpcf7_messages', 'wpcf7_number_messages' );

function wpcf7_number_messages( $messages ) {
	return array_merge( $messages, array(
		'invalid_number' => array(
			'description' => __( "Number format that the sender entered is invalid", 'wpcf7' ),
			'default' => __( 'Number format seems invalid.', 'wpcf7' )
		),

		'number_too_small' => array(
			'description' => __( "Number is smaller than minimum limit", 'wpcf7' ),
			'default' => __( 'This number is too small.', 'wpcf7' )
		),

		'number_too_large' => array(
			'description' => __( "Number is larger than maximum limit", 'wpcf7' ),
			'default' => __( 'This number is too large.', 'wpcf7' )
		) ) );
}


/* Tag generator */

add_action( 'admin_init', 'wpcf7_add_tag_generator_number', 18 );

function wpcf7_add_tag_generator_number() {
	if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
		return;

	wpcf7_add_tag_generator( 'number', __( 'Number (spinbox)', 'wpcf7' ),
		'wpcf7-tg-pane-number', 'wpcf7_tg_pane_number' );

	wpcf7_add_tag_generator( 'range', __( 'Number (slider)', 'wpcf7' ),
		'wpcf7-tg-pane-range', 'wpcf7_tg_pane_range' );
}

function wpcf7_tg_pane_number( &$contact_form ) {
	wpcf7_tg_pane_number_and_relatives( 'number' );
}

function wpcf7_tg_pane_range( &$contact_form ) {
	wpcf7_tg_pane_number_and_relatives( 'range' );
}

function wpcf7_tg_pane_number_and_relatives( $type = 'number' ) {
	if ( ! in_array( $type, array( 'range' ) ) )
		$type = 'number';

?>
<div id="wpcf7-tg-pane-<?php echo $type; ?>" class="hidden">
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
<td><code>min</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="number" name="min" class="numeric oneline option" /></td>

<td><code>max</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="number" name="max" class="numeric oneline option" /></td>
</tr>

<tr>
<td><code>step</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="number" name="step" class="numeric oneline option" min="1" /></td>
</tr>

<tr>
<td><?php echo esc_html( __( 'Default value', 'wpcf7' ) ); ?> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br /><input type="text" name="values" class="oneline" /></td>

<td>
<br /><input type="checkbox" name="placeholder" class="option" />&nbsp;<?php echo esc_html( __( 'Use this text as placeholder?', 'wpcf7' ) ); ?>
</td>
</tr>
</table>

<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'wpcf7' ) ); ?><br /><input type="text" name="<?php echo $type; ?>" class="tag" readonly="readonly" onfocus="this.select()" /></div>

<div class="tg-mail-tag"><?php echo esc_html( __( "And, put this code into the Mail fields below.", 'wpcf7' ) ); ?><br /><span class="arrow">&#11015;</span>&nbsp;<input type="text" class="mail-tag" readonly="readonly" onfocus="this.select()" /></div>
</form>
</div>
<?php
}

?>