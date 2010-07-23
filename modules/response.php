<?php
/**
** A base module for [response]
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'response', 'wpcf7_response_shortcode_handler' );

function wpcf7_response_shortcode_handler( $tag ) {
	global $wpcf7_contact_form;

	$wpcf7_contact_form->responses_count += 1;
	return $wpcf7_contact_form->form_response_output();
}

?>