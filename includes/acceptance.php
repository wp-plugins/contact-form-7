<?php

function wpcf7_acceptance( $contact_form ) {
	$fes = $contact_form->form_scan_shortcode();

	$accepted = true;

	foreach ( $fes as $fe ) {
		if ( 'acceptance' != $fe['type'] )
			continue;

		$invert = (bool) preg_grep( '%^invert$%', $fe['options'] );

		if ( $invert && $_POST[$fe['name']] || ! $invert && ! $_POST[$fe['name']] )
			$accepted = false;
	}

	return $accepted;
}

?>