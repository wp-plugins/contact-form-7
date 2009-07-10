<?php

function wpcf7_refill_quiz( $contact_form ) {
	$fes = $contact_form->form_scan_shortcode( array( 'type' => 'quiz' ) );

	$refill = array();

	foreach ( $fes as $fe ) {
		$name = $fe['name'];
		$pipes = $fe['pipes'];

		if ( is_a( $pipes, 'WPCF7_Pipes' ) && ! $pipes->zero() ) {
			$pipe = $pipes->random_pipe();
			$question = $pipe->before;
			$answer = $pipe->after;
		} else {
			// default quiz
			$question = '1+1=?';
			$answer = '2';
		}

		$answer = wpcf7_canonicalize( $answer );

		$refill[$name] = array( $question, wp_hash( $answer, 'wpcf7_quiz' ) );
	}

	return $refill;
}

?>