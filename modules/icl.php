<?php
/**
** Note: This ICL module is obsolete and no longer functioning on this version.
** There is a simpler way for creating contact forms of other languages.
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'icl', 'icl_wpcf7_shortcode_handler', true );

function icl_wpcf7_shortcode_handler( $tag ) {

	if ( ! is_array( $tag ) )
		return '';

	$name = $tag['name'];
	$values = (array) $tag['values'];
	$content = $tag['content'];

	// Just return the content.

	$content = trim( $content );
	if ( ! empty( $content ) )
		return $content;

	$value = trim( $values[0] );
	if ( ! empty( $value ) )
		return $value;

	return '';
}


/* Message dispaly filter */

add_filter( 'wpcf7_display_message', 'icl_wpcf7_display_message_filter' );

function icl_wpcf7_display_message_filter( $message ) {
	$shortcode_manager = new WPCF7_ShortcodeManager();
	$shortcode_manager->add_shortcode( 'icl', 'icl_wpcf7_shortcode_handler', true );

	return $shortcode_manager->do_shortcode( $message );
}


/* Warning message */

add_action( 'wpcf7_admin_before_subsubsub', 'icl_wpcf7_display_warning_message' );

function icl_wpcf7_display_warning_message( &$contact_form ) {
	if ( ! $contact_form )
		return;

	$has_icl_tags = (bool) $contact_form->form_scan_shortcode(
		array( 'type' => array( 'icl' ) ) );

	if ( ! $has_icl_tags ) {
		$messages = (array) $contact_form->messages;

		$shortcode_manager = new WPCF7_ShortcodeManager();
		$shortcode_manager->add_shortcode( 'icl', create_function( '$tag', 'return null;' ), true );

		foreach ( $messages as $message ) {
			if ( $shortcode_manager->scan_shortcode( $message ) ) {
				$has_icl_tags = true;
				break;
			}
		}
	}

	if ( ! $has_icl_tags )
		return;

	$message = __( "This contact form contains [icl] tags, but they are obsolete and no longer functioning on this version of Contact Form 7. <a href=\"http://contactform7.com/2009/12/25/contact-form-in-your-language/#Creating_contact_form_in_different_languages\" target=\"_blank\">There is a simpler way for creating contact forms of other languages</a> and you are recommended to use it.", 'wpcf7' );

	echo '<div class="error"><p><strong>' . $message . '</strong></p></div>';
}

?>