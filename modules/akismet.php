<?php
/**
** Akismet Filter
**/

add_filter( 'wpcf7_spam', 'wpcf7_akismet' );

function wpcf7_akismet( $spam ) {
	global $wpcf7_contact_form;

	return $wpcf7_contact_form->akismet();
}

?>