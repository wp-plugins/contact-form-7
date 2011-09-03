<?php
/**
** Akismet Filter
**/

add_filter( 'wpcf7_spam', 'wpcf7_akismet' );

function wpcf7_akismet( $spam ) {
	global $akismet_api_host, $akismet_api_port;

	if ( ! function_exists( 'akismet_get_key' ) || ! akismet_get_key() )
		return false;

	$akismet_ready = false;
	$author = $author_email = $author_url = $content = '';

	$fes = wpcf7_scan_shortcode();

	foreach ( $fes as $fe ) {
		if ( ! isset( $fe['name'] ) || ! is_array( $fe['options'] ) )
			continue;

		if ( preg_grep( '%^akismet:author$%', $fe['options'] ) ) {
			$author .= ' ' . $_POST[$fe['name']];
			$author = trim( $author );
			$akismet_ready = true;
		}

		if ( preg_grep( '%^akismet:author_email$%', $fe['options'] ) && '' == $author_email ) {
			$author_email = trim( $_POST[$fe['name']] );
			$akismet_ready = true;
		}

		if ( preg_grep( '%^akismet:author_url$%', $fe['options'] ) && '' == $author_url ) {
			$author_url = trim( $_POST[$fe['name']] );
			$akismet_ready = true;
		}

		if ( '' != $content )
			$content .= "\n\n";

		$content .= $_POST[$fe['name']];
	}

	if ( ! $akismet_ready )
		return false;

	$c['blog'] = get_option( 'home' );
	$c['blog_lang'] = get_locale();
	$c['blog_charset'] = get_option( 'blog_charset' );
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

	$ignore = array( 'HTTP_COOKIE', 'HTTP_COOKIE2', 'PHP_AUTH_PW' );

	foreach ( $_SERVER as $key => $value ) {
		if ( ! in_array( $key, (array) $ignore ) )
			$c["$key"] = $value;
	}

	$query_string = '';

	foreach ( $c as $key => $data )
		$query_string .= $key . '=' . urlencode( stripslashes( (string) $data ) ) . '&';

	$response = akismet_http_post( $query_string,
		$akismet_api_host, '/1.1/comment-check', $akismet_api_port );

	if ( 'true' == $response[1] )
		$spam = true;

	return $spam;
}


/* Messages */

add_filter( 'wpcf7_messages', 'wpcf7_akismet_messages' );

function wpcf7_akismet_messages( $messages ) {
	return array_merge( $messages, array( 'akismet_says_spam' => array(
		'description' => __( "Akismet judged the sending activity as spamming", 'wpcf7' ),
		'default' => __( 'Failed to send your message. Please try later or contact the administrator by another method.', 'wpcf7' )
	) ) );
}

add_filter( 'wpcf7_display_message', 'wpcf7_akismet_display_message', 10, 2 );

function wpcf7_akismet_display_message( $message, $status ) {
	if ( 'spam' == $status && empty( $message ) )
		$message = wpcf7_get_message( 'akismet_says_spam' );

	return $message;
}

?>