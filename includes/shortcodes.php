<?php

class WPCF7_ShortcodeManager {

	var $shortcode_tags = array();

	function add_shortcode( $tag, $func ) {
		if ( is_callable( $func ) )
			$this->shortcode_tags[$tag] = $func;
	}

	function remove_shortcode( $tag ) {
		unset( $this->shortcode_tags[$tag] );
	}

	function remove_all_shortcodes() {
		$this->shortcode_tags = array();
	}

	function do_shortcode( $content ) {
		if ( empty( $this->shortcode_tags ) || ! is_array( $this->shortcode_tags) )
			return $content;

		$pattern = $this->get_shortcode_regex();
		return preg_replace_callback( '/' . $pattern . '/s',
			array(&$this, 'do_shortcode_tag'), $content );
	}

	function get_shortcode_regex() {
		$tagnames = array_keys( $this->shortcode_tags );
		$tagregexp = join( '|', array_map( 'preg_quote', $tagnames ) );

		return '(.?)\[(' . $tagregexp . ')\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)';
	}

	function do_shortcode_tag( $m ) {
		// allow [[foo]] syntax for escaping a tag
		if ( $m[1] == '[' && $m[6] == ']' ) {
			return substr( $m[0], 1, -1 );
		}

		$tag = $m[2];
		$attr = $this->shortcode_parse_atts( $m[3] );

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
		$pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
		$text = preg_replace( "/[\x{00a0}\x{200b}]+/u", " ", $text );
		if ( preg_match_all( $pattern, $text, $match, PREG_SET_ORDER ) ) {
			foreach ( $match as $m ) {
				if ( ! empty( $m[1] ) )
					$atts[strtolower( $m[1] )] = stripcslashes( $m[2] );
				elseif ( ! empty( $m[3] ) )
					$atts[strtolower( $m[3] )] = stripcslashes( $m[4] );
				elseif ( ! empty($m[5] ) )
					$atts[strtolower( $m[5] )] = stripcslashes( $m[6] );
				elseif ( isset( $m[7] ) and strlen( $m[7] ) )
					$atts[] = stripcslashes( $m[7] );
				elseif ( isset( $m[8] ) )
					$atts[] = stripcslashes( $m[8] );
			}
		} else {
			$atts = ltrim( $text );
		}
		return $atts;
	}

	function shortcode_atts( $pairs, $atts ) {
		$atts = (array) $atts;
		$out = array();

		foreach( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) )
				$out[$name] = $atts[$name];
			else
				$out[$name] = $default;
		}
		return $out;
	}

	function strip_shortcodes( $content ) {
		if ( empty( $this->shortcode_tags ) || ! is_array( $this->shortcode_tags ) )
			return $content;

		$pattern = $this->get_shortcode_regex();

		return preg_replace( '/' . $pattern . '/s', '', $content );
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

?>