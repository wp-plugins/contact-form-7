<?php
/**
** ICL module for ICanLocalize translation service
**/

function icl_wpcf7_tag_func( $atts ) {

	if ( is_string( $atts ) )
		$atts = explode( ' ', $atts, 2 );

	$atts = (array) $atts;

	return '<strong>' . $atts[0] . '</strong>';
}

wpcf7_add_shortcode( 'icl', 'icl_wpcf7_tag_func' );

?>