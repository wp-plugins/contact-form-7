<?php

function wpcf7_message( $contact_form, $status ) {
	if ( ! isset( $contact_form['messages'] ) || ! isset( $contact_form['messages'][$status] ) )
		return wpcf7_default_message( $status );

	return $contact_form['messages'][$status];
}

?>