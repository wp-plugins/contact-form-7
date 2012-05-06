<?php
/**
** Module for Flamingo plugin.
** http://wordpress.org/extend/plugins/flamingo/
**/

add_action( 'flamingo_init', 'wpcf7_flamingo_init' );

function wpcf7_flamingo_init() {
	if ( ! class_exists( 'Flamingo_Inbound_Message' ) )
		return;

	if ( ! term_exists( 'contact-form-7', Flamingo_Inbound_Message::channel_taxonomy ) ) {
		wp_insert_term( __( 'Contact Form 7', 'wpcf7' ),
			Flamingo_Inbound_Message::channel_taxonomy,
			array( 'slug' => 'contact-form-7' ) );
	}
}

add_action( 'wpcf7_before_send_mail', 'wpcf7_flamingo_before_send_mail' );

function wpcf7_flamingo_before_send_mail( $contactform ) {
	if ( ! ( class_exists( 'Flamingo_Contact' ) && class_exists( 'Flamingo_Inbound_Message' ) ) )
		return;

	if ( empty( $contactform->posted_data ) || ! empty( $contactform->skip_mail ) )
		return;

	$fields_senseless = $contactform->form_scan_shortcode(
		array( 'type' => array( 'captchar', 'quiz', 'acceptance' ) ) );

	$exclude_names = array();

	foreach ( $fields_senseless as $tag )
		$exclude_names[] = $tag['name'];

	$posted_data = $contactform->posted_data;

	foreach ( $posted_data as $key => $value ) {
		if ( '_' == substr( $key, 0, 1 ) || in_array( $key, $exclude_names ) )
			unset( $posted_data[$key] );
	}

	$args = array(
		'channel' => 'contact-form-7',
		'fields' => $posted_data,
		'email' => '',
		'name' => '',
		'from' => '',
		'subject' => '' );

	if ( ! empty( $posted_data['your-email'] ) )
		$args['from_email'] = $args['email'] = trim( $posted_data['your-email'] );

	if ( ! empty( $posted_data['your-name'] ) )
		$args['from_name'] = $args['name'] = trim( $posted_data['your-name'] );

	if ( ! empty( $posted_data['your-subject'] ) )
		$args['subject'] = trim( $posted_data['your-subject'] );

	$args['from'] = trim( sprintf( '%s <%s>', $args['from_name'], $args['from_email'] ) );

	Flamingo_Contact::add( $args );
	Flamingo_Inbound_Message::add( $args );
}

?>