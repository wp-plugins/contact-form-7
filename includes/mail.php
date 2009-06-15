<?php

function wpcf7_mail( $contact_form, $files = array() ) {
	global $wpcf7_posted_data;

	$wpcf7_posted_data = $_POST;

	if ( WPCF7_USE_PIPE ) {
		wpcf7_pipe_all_posted( $contact_form );
	}

	if ( wpcf7_compose_and_send_mail( $contact_form->mail, $files ) ) {
		if ( $contact_form->mail_2['active'] )
			wpcf7_compose_and_send_mail( $contact_form->mail_2, $files );

		return true;
	}

	return false;
}

function wpcf7_compose_and_send_mail( $mail_template, $attachments = array() ) {
	$regex = '/\[\s*([a-zA-Z][0-9a-zA-Z:._-]*)\s*\]/';
	$callback = 'wpcf7_mail_callback';
	$mail_subject = preg_replace_callback( $regex, $callback, $mail_template['subject'] );
	$mail_sender = preg_replace_callback( $regex, $callback, $mail_template['sender'] );
	$mail_body = preg_replace_callback( $regex, $callback, $mail_template['body'] );
	$mail_recipient = preg_replace_callback( $regex, $callback, $mail_template['recipient'] );

	$mail_headers = "From: $mail_sender\n";

	if ( $mail_template['use_html'] )
		$mail_headers .= "Content-Type: text/html\n";

	$mail_additional_headers = preg_replace_callback( $regex, $callback, $mail_template['additional_headers'] );
	$mail_headers .= trim($mail_additional_headers) . "\n";

	if ( $attachments ) {
		$for_this_mail = array();
		foreach ( $attachments as $name => $path ) {
			if ( false === strpos( $mail_template['attachments'], "[${name}]" ) )
				continue;
			$for_this_mail[] = $path;
		}
		return @wp_mail( $mail_recipient, $mail_subject, $mail_body, $mail_headers, $for_this_mail );
	} else {
		return @wp_mail( $mail_recipient, $mail_subject, $mail_body, $mail_headers );
	}
}

function wpcf7_mail_callback( $matches ) {
	global $wpcf7_posted_data;

	if ( isset( $wpcf7_posted_data[$matches[1]] ) ) {
		$submitted = $wpcf7_posted_data[$matches[1]];

		if ( is_array( $submitted ) )
			$submitted = join( ', ', $submitted );
		return stripslashes( $submitted );
	} else {

		// Special [wpcf7.remote_ip] tag
		if ( 'wpcf7.remote_ip' == $matches[1] )
			return preg_replace( '/[^0-9a-f.:, ]/', '', $_SERVER['REMOTE_ADDR'] );

		return $matches[0];
	}
}

?>