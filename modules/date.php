<?php
/**
** A base module for the following types of tags:
** 	[date] and [date*]		# Date
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'date', 'wpcf7_date_shortcode_handler', true );
wpcf7_add_shortcode( 'date*', 'wpcf7_date_shortcode_handler', true );

function wpcf7_date_shortcode_handler( $tag ) {
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

	$class_att .= ' wpcf7-validates-as-date';

	if ( $validation_error )
		$class_att .= ' wpcf7-not-valid';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^min:([0-9]{4}-[0-9]{2}-[0-9]{2})$%', $option, $matches ) ) {
			$min_att = $matches[1];

		} elseif ( preg_match( '%^max:([0-9]{4}-[0-9]{2}-[0-9]{2})$%', $option, $matches ) ) {
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

add_filter( 'wpcf7_validate_date', 'wpcf7_date_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_date*', 'wpcf7_date_validation_filter', 10, 2 );

function wpcf7_date_validation_filter( $result, $tag ) {
	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];

	$value = isset( $_POST[$name] )
		? trim( strtr( (string) $_POST[$name], "\n", " " ) )
		: '';

	foreach ( $options as $option ) {
		if ( preg_match( '%^min:([0-9]{4}-[0-9]{2}-[0-9]{2})$%', $option, $matches ) ) {
			$min_att = $matches[1];

		} elseif ( preg_match( '%^max:([0-9]{4}-[0-9]{2}-[0-9]{2})$%', $option, $matches ) ) {
			$max_att = $matches[1];

		}
	}

	if ( '*' == substr( $type, -1 ) && '' == $value ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
	} elseif ( '' != $value && ! wpcf7_is_date( $value ) ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'invalid_date' );
	} elseif ( '' != $value && ! empty( $min_att ) && $value < $min_att ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'date_too_early' );
	} elseif ( '' != $value && ! empty( $max_att ) && $max_att < $value ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'date_too_late' );
	}

	return $result;
}


/* Messages */

add_filter( 'wpcf7_messages', 'wpcf7_date_messages' );

function wpcf7_date_messages( $messages ) {
	return array_merge( $messages, array(
		'invalid_date' => array(
			'description' => __( "Date format that the sender entered is invalid", 'wpcf7' ),
			'default' => __( 'Date format seems invalid.', 'wpcf7' )
		),

		'date_too_early' => array(
			'description' => __( "Date is earlier than minimum limit", 'wpcf7' ),
			'default' => __( 'This date is too early.', 'wpcf7' )
		),

		'date_too_late' => array(
			'description' => __( "Date is later than maximum limit", 'wpcf7' ),
			'default' => __( 'This date is too late.', 'wpcf7' )
		) ) );
}


/* Tag generator */

add_action( 'admin_init', 'wpcf7_add_tag_generator_date', 19 );

function wpcf7_add_tag_generator_date() {
	if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
		return;

	wpcf7_add_tag_generator( 'date', __( 'Date', 'wpcf7' ),
		'wpcf7-tg-pane-date', 'wpcf7_tg_pane_date' );
}

function wpcf7_tg_pane_date( &$contact_form ) {
	wpcf7_tg_pane_date_and_relatives( 'date' );
}

function wpcf7_tg_pane_date_and_relatives( $type = 'date' ) {
	if ( ! in_array( $type, array() ) )
		$type = 'date';

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
<input type="date" name="min" class="date oneline option" /></td>

<td><code>max</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="date" name="max" class="date oneline option" /></td>
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