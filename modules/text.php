<?php
/**
** A base module for the following types of tags:
** 	[text] and [text*]		# Single-line text
** 	[email] and [email*]	# Email address
** 	[url] and [url*]		# URL
** 	[tel] and [tel*]		# Telephone number
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'text', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'text*', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'email', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'email*', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'url', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'url*', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'tel', 'wpcf7_text_shortcode_handler', true );
wpcf7_add_shortcode( 'tel*', 'wpcf7_text_shortcode_handler', true );

function wpcf7_text_shortcode_handler( $tag ) {
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

	$atts = $id_att = $size_att = $maxlength_att = '';
	$tabindex_att = $placeholder_att = '';

	$class_att = wpcf7_form_controls_class( $type, 'wpcf7-text' );

	if ( in_array( $basetype, array( 'email', 'url', 'tel' ) ) )
		$class_att .= ' wpcf7-validates-as-' . $basetype;

	if ( $validation_error )
		$class_att .= ' wpcf7-not-valid';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^([0-9]*)[/x]([0-9]*)$%', $option, $matches ) ) {
			$size_att = (int) $matches[1];
			$maxlength_att = (int) $matches[2];

		} elseif ( preg_match( '%^tabindex:(\d+)$%', $option, $matches ) ) {
			$tabindex_att = (int) $matches[1];

		}
	}

	$value = (string) reset( $values );

	if ( preg_grep( '%^placeholder|watermark$%', $options ) ) {
		$placeholder_att = $value;
		$value = '';
	} elseif ( empty( $value ) && is_user_logged_in() ) {
		$user = wp_get_current_user();

		$user_options = array(
			'default:user_login' => 'user_login',
			'default:user_email' => 'user_email',
			'default:user_url' => 'user_url',
			'default:user_first_name' => 'first_name',
			'default:user_last_name' => 'last_name',
			'default:user_nickname' => 'nickname',
			'default:user_display_name' => 'display_name' );

		foreach ( $user_options as $option => $prop ) {
			if ( preg_grep( '%^' . $option . '$%', $options ) ) {
				$value = $user->{$prop};
				break;
			}
		}
	}

	if ( wpcf7_is_posted() && isset( $_POST[$name] ) )
		$value = stripslashes_deep( $_POST[$name] );

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

add_filter( 'wpcf7_validate_text', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_text*', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_email', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_email*', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_url', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_url*', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_tel', 'wpcf7_text_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_tel*', 'wpcf7_text_validation_filter', 10, 2 );

function wpcf7_text_validation_filter( $result, $tag ) {
	$type = $tag['type'];
	$name = $tag['name'];

	$value = isset( $_POST[$name] )
		? trim( strtr( (string) $_POST[$name], "\n", " " ) )
		: '';

	if ( 'text*' == $type ) {
		if ( '' == $value ) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
		}
	}

	if ( 'email' == $type || 'email*' == $type ) {
		if ( 'email*' == $type && '' == $value ) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
		} elseif ( '' != $value && ! wpcf7_is_email( $value ) ) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_email' );
		}
	}

	if ( 'url' == $type || 'url*' == $type ) {
		if ( 'url*' == $type && '' == $value ) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
		} elseif ( '' != $value && ! wpcf7_is_url( $value ) ) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_url' );
		}
	}

	if ( 'tel' == $type || 'tel*' == $type ) {
		if ( 'tel*' == $type && '' == $value ) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
		} elseif ( '' != $value && ! wpcf7_is_tel( $value ) ) {
			$result['valid'] = false;
			$result['reason'][$name] = wpcf7_get_message( 'invalid_tel' );
		}
	}

	return $result;
}


/* Messages */

add_filter( 'wpcf7_messages', 'wpcf7_text_messages' );

function wpcf7_text_messages( $messages ) {
	return array_merge( $messages, array(
		'invalid_email' => array(
			'description' => __( "Email address that the sender entered is invalid", 'wpcf7' ),
			'default' => __( 'Email address seems invalid.', 'wpcf7' )
		),

		'invalid_url' => array(
			'description' => __( "URL that the sender entered is invalid", 'wpcf7' ),
			'default' => __( 'URL seems invalid.', 'wpcf7' )
		),

		'invalid_tel' => array(
			'description' => __( "Telephone number that the sender entered is invalid", 'wpcf7' ),
			'default' => __( 'Telephone number seems invalid.', 'wpcf7' )
		) ) );
}


/* Tag generator */

add_action( 'admin_init', 'wpcf7_add_tag_generator_text', 15 );

function wpcf7_add_tag_generator_text() {
	if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
		return;

	wpcf7_add_tag_generator( 'text', __( 'Text field', 'wpcf7' ),
		'wpcf7-tg-pane-text', 'wpcf7_tg_pane_text' );

	wpcf7_add_tag_generator( 'email', __( 'Email', 'wpcf7' ),
		'wpcf7-tg-pane-email', 'wpcf7_tg_pane_email' );

	wpcf7_add_tag_generator( 'url', __( 'URL', 'wpcf7' ),
		'wpcf7-tg-pane-url', 'wpcf7_tg_pane_url' );

	wpcf7_add_tag_generator( 'tel', __( 'Telephone number', 'wpcf7' ),
		'wpcf7-tg-pane-tel', 'wpcf7_tg_pane_tel' );
}

function wpcf7_tg_pane_text( &$contact_form ) {
	wpcf7_tg_pane_text_and_relatives( 'text' );
}

function wpcf7_tg_pane_email( &$contact_form ) {
	wpcf7_tg_pane_text_and_relatives( 'email' );
}

function wpcf7_tg_pane_url( &$contact_form ) {
	wpcf7_tg_pane_text_and_relatives( 'url' );
}

function wpcf7_tg_pane_tel( &$contact_form ) {
	wpcf7_tg_pane_text_and_relatives( 'tel' );
}

function wpcf7_tg_pane_text_and_relatives( $type = 'text' ) {
	if ( ! in_array( $type, array( 'email', 'url', 'tel' ) ) )
		$type = 'text';

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
<td><code>size</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="number" name="size" class="numeric oneline option" min="1" /></td>

<td><code>maxlength</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="number" name="maxlength" class="numeric oneline option" min="1" /></td>
</tr>

<?php if ( in_array( $type, array( 'text', 'email', 'url' ) ) ) : ?>
<tr>
<td colspan="2"><?php echo esc_html( __( 'Akismet', 'wpcf7' ) ); ?> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<?php if ( 'text' == $type ) : ?>
<input type="checkbox" name="akismet:author" class="option" />&nbsp;<?php echo esc_html( __( "This field requires author's name", 'wpcf7' ) ); ?><br />
<?php elseif ( 'email' == $type ) : ?>
<input type="checkbox" name="akismet:author_email" class="option" />&nbsp;<?php echo esc_html( __( "This field requires author's email address", 'wpcf7' ) ); ?>
<?php elseif ( 'url' == $type ) : ?>
<input type="checkbox" name="akismet:author_url" class="option" />&nbsp;<?php echo esc_html( __( "This field requires author's URL", 'wpcf7' ) ); ?>
<?php endif; ?>
</td>
</tr>
<?php endif; ?>

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