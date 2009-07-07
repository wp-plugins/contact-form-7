<?php

class WPCF7_ShortcodeManager {

	var $shortcode_tags = array();

	// Taggs scanned at the last time of do_shortcode()
	var $scanned_tags = null;

	function add_shortcode( $tag, $func ) {
		if ( is_callable( $func ) )
			$this->shortcode_tags[$tag] = $func;
	}

	function remove_shortcode( $tag ) {
		unset( $this->shortcode_tags[$tag] );
	}

	function do_shortcode( $content ) {
		$this->scanned_tags = array();

		if ( empty( $this->shortcode_tags ) || ! is_array( $this->shortcode_tags) )
			return $content;

		$pattern = $this->get_shortcode_regex();
		return preg_replace_callback( '/' . $pattern . '/s',
			array(&$this, 'do_shortcode_tag'), $content );
	}

	function get_shortcode_regex() {
		$tagnames = array_keys( $this->shortcode_tags );
		$tagregexp = join( '|', array_map( 'preg_quote', $tagnames ) );

		return '(.?)\[(' . $tagregexp . ')(?:\s(.*?))?(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)';
	}

	function do_shortcode_tag( $m ) {
		// allow [[foo]] syntax for escaping a tag
		if ( $m[1] == '[' && $m[6] == ']' ) {
			return substr( $m[0], 1, -1 );
		}

		$tag = $m[2];
		$attr = $this->shortcode_parse_atts( $m[3] );

		$this->scanned_tags[] = array(
			'type' => $tag,
			'attr' => $attr,
			'content' => $m[5] );

		if ( isset( $m[5] ) ) {
			// enclosing tag - extra parameter
			return $m[1] . call_user_func( $this->shortcode_tags[$tag], $attr, $m[5], $m[2] ) . $m[6];
		} else {
			// self-closing tag
			return $m[1] . call_user_func( $this->shortcode_tags[$tag], $attr, NULL, $m[2] ) . $m[6];
		}
	}

	function shortcode_parse_atts( $text ) {
		$atts = array();
		$text = preg_replace( "/[\x{00a0}\x{200b}]+/u", " ", $text );
		$text = trim( $text );

		$pattern = '%^([-0-9a-zA-Z:.#_/|\s]*?)?((?:\s+(?:"[^"]*"|\'[^\']*\'))*)?$%';

		if ( preg_match( $pattern, $text, $match ) ) {
			if ( ! empty( $match[1] ) ) {
				$atts['options'] = preg_split( '/[\s]+/', trim( $match[1] ) );
				if ( ! empty( $atts['options'] ) )
					$atts['maybe_name'] = $atts['options'][0];
			}
			if ( ! empty( $match[2] ) ) {
				preg_match_all( '/"[^"]*"|\'[^\']*\'/', $match[2], $matched_values );
				$atts['values'] = wpcf7_strip_quote_deep( $matched_values[0] );
			}
		} else {
			$atts = $text;
		}

		return $atts;
	}

}

$wpcf7_shortcode_manager = new WPCF7_ShortcodeManager();

function wpcf7_add_shortcode( $tag, $func ) {
	global $wpcf7_shortcode_manager;

	return $wpcf7_shortcode_manager->add_shortcode( $tag, $func );
}

function wpcf7_remove_shortcode( $tag ) {
	global $wpcf7_shortcode_manager;

	return $wpcf7_shortcode_manager->remove_shortcode( $tag );
}

function wpcf7_do_shortcode( $content ) {
	global $wpcf7_shortcode_manager;

	return $wpcf7_shortcode_manager->do_shortcode( $content );
}

function wpcf7_scanned_shortcodes() {
	global $wpcf7_shortcode_manager;

	return $wpcf7_shortcode_manager->scanned_tags;
}

?>