<?php
/**
** Filters for Special Mail Tags
**/

add_filter( 'wpcf7_special_mail_tags', 'wpcf7_special_mail_tag', 10, 2 );

function wpcf7_special_mail_tag( $output, $name ) {

	// For backwards compat.
	$name = preg_replace( '/^wpcf7\./', '_', $name );

	if ( '_remote_ip' == $name )
		$output = preg_replace( '/[^0-9a-f.:, ]/', '', $_SERVER['REMOTE_ADDR'] );

	elseif ( '_url' == $name )
		$output = get_option( 'home' ) . wpcf7_get_request_uri();

	elseif ( '_date' == $name )
		$output = date_i18n( get_option( 'date_format' ) );

	elseif ( '_time' == $name )
		$output = date_i18n( get_option( 'time_format' ) );

	return $output;
}

add_filter( 'wpcf7_special_mail_tags', 'wpcf7_special_mail_tag_for_post_data', 10, 2 );

function wpcf7_special_mail_tag_for_post_data( $output, $name ) {

	if ( ! isset( $_POST['_wpcf7_unit_tag'] ) || empty( $_POST['_wpcf7_unit_tag'] ) )
		return $output;

	if ( ! preg_match( '/^wpcf7-f(\d+)-p(\d+)-o(\d+)$/', $_POST['_wpcf7_unit_tag'], $matches ) )
		return $output;

	$post_id = (int) $matches[2];

	if ( ! $post = get_post( $post_id ) )
		return $output;

	$user = new WP_User( $post->post_author );

	// For backwards compat.
	$name = preg_replace( '/^wpcf7\./', '_', $name );

	if ( '_post_id' == $name )
		$output = (string) $post->ID;

	elseif ( '_post_name' == $name )
		$output = $post->post_name;

	elseif ( '_post_title' == $name )
		$output = $post->post_title;

	elseif ( '_post_url' == $name )
		$output = get_permalink( $post->ID );

	elseif ( '_post_author' == $name )
		$output = $user->display_name;

	elseif ( '_post_author_email' == $name )
		$output = $user->user_email;

	return $output;
}

?>