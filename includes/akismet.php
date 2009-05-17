<?php

function wpcf7_akismet( $contact_form ) {
	global $akismet_api_host, $akismet_api_port, $wpcf7;

	if ( ! function_exists( 'akismet_http_post' ) || ! ( get_option( 'wordpress_api_key' ) || $wpcom_api_key ) )
		return false;

	$akismet_ready = false;
	$author = $author_email = $author_url = $content = '';
	$fes = $wpcf7->form_elements( $contact_form['form'], false );

	foreach ( $fes as $fe ) {
		if ( ! is_array( $fe['options'] ) ) continue;

		if ( preg_grep( '%^akismet:author$%', $fe['options'] ) && '' == $author ) {
			$author = $_POST[$fe['name']];
			$akismet_ready = true;
		}

		if ( preg_grep( '%^akismet:author_email$%', $fe['options'] ) && '' == $author_email ) {
			$author_email = $_POST[$fe['name']];
			$akismet_ready = true;
		}

		if ( preg_grep( '%^akismet:author_url$%', $fe['options'] ) && '' == $author_url ) {
			$author_url = $_POST[$fe['name']];
			$akismet_ready = true;
		}

		if ( '' != $content )
			$content .= "\n\n";

		$content .= $_POST[$fe['name']];
	}

	if ( ! $akismet_ready )
		return false;

	$c['blog'] = get_option( 'home' );
	$c['user_ip'] = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
	$c['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
	$c['referrer'] = $_SERVER['HTTP_REFERER'];
	$c['comment_type'] = 'contactform7';
	if ( $permalink = get_permalink() )
		$c['permalink'] = $permalink;
	if ( '' != $author )
		$c['comment_author'] = $author;
	if ( '' != $author_email )
		$c['comment_author_email'] = $author_email;
	if ( '' != $author_url )
		$c['comment_author_url'] = $author_url;
	if ( '' != $content )
		$c['comment_content'] = $content;

	$ignore = array( 'HTTP_COOKIE' );

	foreach ( $_SERVER as $key => $value )
		if ( ! in_array( $key, (array) $ignore ) )
			$c["$key"] = $value;

	$query_string = '';
	foreach ( $c as $key => $data )
		$query_string .= $key . '=' . urlencode( stripslashes( $data ) ) . '&';

	$response = akismet_http_post( $query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port );
	if ( 'true' == $response[1] )
		return true;
	else
		return false;
}

?>