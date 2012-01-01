<?php

function wpcf7_add_tag_generator( $name, $title, $elm_id, $callback, $options = array() ) {
	global $wpcf7_tag_generators;

	$name = trim( $name );
	if ( '' == $name )
		return false;

	if ( ! is_array( $wpcf7_tag_generators ) )
		$wpcf7_tag_generators = array();

	$wpcf7_tag_generators[$name] = array(
		'title' => $title,
		'content' => $elm_id,
		'options' => $options );

	if ( is_callable( $callback ) )
		add_action( 'wpcf7_admin_footer', $callback );

	return true;
}

?>