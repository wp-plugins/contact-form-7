<?php

function wpcf7_refill_quiz( $contact_form ) {
	global $wpcf7;

	$fes = $wpcf7->form_elements( $contact_form['form'], false );
	$refill = array();

	foreach ( $fes as $fe ) {
		$type = $fe['type'];
		$name = $fe['name'];
		$values = $fe['values'];
		$raw_values = $fe['raw_values'];

		if ( 'quiz' != $type )
			continue;

		if ( count( $values ) == 0 )
			continue;

		if ( count( $values ) == 1 )
			$question = $values[0];
		else
			$question = $values[array_rand( $values )];

		$pipes = wpcf7_get_pipes( $raw_values );
		$answer = wpcf7_pipe( $pipes, $question );
		$answer = wpcf7_canonicalize( $answer );

		$refill[$name] = array( $question, wp_hash( $answer, 'wpcf7_quiz' ) );
	}

	return $refill;
}

?>