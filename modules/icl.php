<?php
/**
** ICL module for ICanLocalize translation service
**/

/* Shortcode handler */

function icl_wpcf7_shortcode_handler( $tag ) {

	if ( ! is_array( $tag ) )
		return '';

	$name = $tag['name'];
	$values = (array) $tag['values'];
	$content = $tag['content'];

	$content = trim( $content );
	if ( ! empty( $content ) ) {
		$string_name = icl_wpcf7_string_name( $content, $name );
		return icl_wpcf7_translate( $string_name, $content );
	}

	$value = trim( $values[0] );
	if ( ! empty( $value ) ) {
		$string_name = icl_wpcf7_string_name( $value, $name, 0 );
		return icl_wpcf7_translate( $string_name, $value );
	}

	return '';
}

wpcf7_add_shortcode( 'icl', 'icl_wpcf7_shortcode_handler', true );


/* Form tag filter */

function icl_wpcf7_form_tag_filter( $tag ) {
	if ( ! is_array( $tag ) )
		return $tag;

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];
	$content = $tag['content'];

	$icl_option = array();
	foreach ( $options as $option ) {
		if ( 'icl' == $option ) {
			$icl_option = array( 'icl', null );
			break;
		} elseif ( preg_match( '/^icl:(.+)$/', $option, $matches ) ) {
			$icl_option = array( 'icl', $matches[1] );
			break;
		}
	}

	if ( ! ('icl' == $type || $icl_option ) )
		return $tag;

	$str_id = $icl_option[1] ? $icl_option[1] : $name;

	if ( ! empty( $values ) ) {
		$new_values = array();
		foreach ( $values as $key => $value ) {
			$string_name = icl_wpcf7_string_name( $value, $str_id, $key );
			$new_values[$key] = icl_wpcf7_translate( $string_name, $value );
		}

		if ( preg_match( '/^(?:text|email|textarea|captchar|submit)[*]?$/', $type ) )
			$tag['values'] = $new_values;
		else
			$tag['labels'] = $new_values;
	}

	$content = trim( $content );

	if ( ! empty( $content ) ) {
		$string_name = icl_wpcf7_string_name( $content, $str_id );
		$content = icl_wpcf7_translate( $string_name, $content );
		$tag['content'] = $content;
	}

	return $tag;
}

add_filter( 'wpcf7_form_tag', 'icl_wpcf7_form_tag_filter' );


/* Message dispaly filter */

function icl_wpcf7_display_message_filter( $message ) {
	$shortcode_manager = new WPCF7_ShortcodeManager();
	$shortcode_manager->add_shortcode( 'icl', 'icl_wpcf7_shortcode_handler', true );

	return $shortcode_manager->do_shortcode( $message );
}

add_filter( 'wpcf7_display_message', 'icl_wpcf7_display_message_filter' );


/* Collecting strings hook after saving */

function icl_wpcf7_collect_strings( &$contact_form ) {
	$scanned = $contact_form->form_scan_shortcode();

	foreach ( $scanned as $tag ) {
		if ( ! is_array( $tag ) )
			continue;

		$type = $tag['type'];
		$name = $tag['name'];
		$options = (array) $tag['options'];
		$values = (array) $tag['values'];
		$content = $tag['content'];

		$icl_option = array();
		foreach ( $options as $option ) {
			if ( 'icl' == $option ) {
				$icl_option = array( 'icl', null );
				break;
			} elseif ( preg_match( '/^icl:(.+)$/', $option, $matches ) ) {
				$icl_option = array( 'icl', $matches[1] );
				break;
			}
		}

		if ( ! ('icl' == $type || $icl_option ) )
			continue;

		$str_id = $icl_option[1] ? $icl_option[1] : $name;

		if ( ! empty( $content ) ) {
			$string_name = icl_wpcf7_string_name( $content, $str_id );
			icl_wpcf7_register_string( $string_name, $content );

		} elseif ( ! empty( $values ) ) {
			foreach ( $values as $key => $value ) {
				$value = trim( $value );
				$string_name = icl_wpcf7_string_name( $value, $str_id, $key );
				icl_wpcf7_register_string( $string_name, $value );
			}
		}
	}

	/* From messages */

	$messages = (array) $contact_form->messages;

	$shortcode_manager = new WPCF7_ShortcodeManager();
	$shortcode_manager->add_shortcode( 'icl', create_function( '$tag', 'return null;' ), true );

	foreach ( $messages as $message ) {
		$tags = $shortcode_manager->scan_shortcode( $message );
		foreach ( $tags as $tag ) {
			$name = $tag['name'];
			$values = (array) $tag['values'];
			$content = trim( $tag['content'] );

			if ( ! empty( $content ) ) {
				$string_name = icl_wpcf7_string_name( $content, $name );
				icl_wpcf7_register_string( $string_name, $content );

			} else {
				foreach ( $values as $key => $value ) {
					$value = trim( $value );
					$string_name = icl_wpcf7_string_name( $value, $name, $key );
					icl_wpcf7_register_string( $string_name, $value );
				}
			}
		}
	}
}

add_action( 'wpcf7_after_save', 'icl_wpcf7_collect_strings' );


/* Functions */

function icl_wpcf7_string_name( $value, $name = '', $key = '' ) {
	if ( ! empty( $name ) ) {
		$string_name = '@' . $name;
		if ( '' !== $key )
			$string_name .= ' ' . $key;
	} else {
		$string_name = '#' . md5( $value );
	}

	return $string_name;
}

function icl_wpcf7_register_string( $name, $value ) {
	if ( ! function_exists( 'icl_register_string' ) )
		return false;

	$context = 'Contact Form 7';

	$value = trim( $value );
	if ( empty( $value ) )
		return false;

	icl_register_string( $context, $name, $value );
}

function icl_wpcf7_translate( $name, $value = '' ) {
	if ( ! function_exists( 'icl_t' ) )
		return $value;

	if ( empty( $name ) )
		return $value;

	$context = 'Contact Form 7';

	return icl_t( $context, $name, $value );
}

?>