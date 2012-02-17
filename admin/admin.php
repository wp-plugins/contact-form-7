<?php

function wpcf7_admin_has_edit_cap() {
	return current_user_can( WPCF7_ADMIN_READ_WRITE_CAPABILITY );
}

add_action( 'admin_init', 'wpcf7_admin_init' );

function wpcf7_admin_init() {
	if ( ! wpcf7_admin_has_edit_cap() )
		return;

	if ( isset( $_POST['wpcf7-save'] ) ) {
		$id = $_POST['post_ID'];
		check_admin_referer( 'wpcf7-save_' . $id );

		if ( ! $contact_form = wpcf7_contact_form( $id ) ) {
			$contact_form = new WPCF7_ContactForm();
			$contact_form->initial = true;
		}

		$contact_form->title = trim( $_POST['wpcf7-title'] );

		$form = trim( $_POST['wpcf7-form'] );

		$mail = array(
			'subject' => trim( $_POST['wpcf7-mail-subject'] ),
			'sender' => trim( $_POST['wpcf7-mail-sender'] ),
			'body' => trim( $_POST['wpcf7-mail-body'] ),
			'recipient' => trim( $_POST['wpcf7-mail-recipient'] ),
			'additional_headers' => trim( $_POST['wpcf7-mail-additional-headers'] ),
			'attachments' => trim( $_POST['wpcf7-mail-attachments'] ),
			'use_html' =>
				isset( $_POST['wpcf7-mail-use-html'] ) && 1 == $_POST['wpcf7-mail-use-html']
		);

		$mail_2 = array(
			'active' =>
				isset( $_POST['wpcf7-mail-2-active'] ) && 1 == $_POST['wpcf7-mail-2-active'],
			'subject' => trim( $_POST['wpcf7-mail-2-subject'] ),
			'sender' => trim( $_POST['wpcf7-mail-2-sender'] ),
			'body' => trim( $_POST['wpcf7-mail-2-body'] ),
			'recipient' => trim( $_POST['wpcf7-mail-2-recipient'] ),
			'additional_headers' => trim( $_POST['wpcf7-mail-2-additional-headers'] ),
			'attachments' => trim( $_POST['wpcf7-mail-2-attachments'] ),
			'use_html' =>
				isset( $_POST['wpcf7-mail-2-use-html'] ) && 1 == $_POST['wpcf7-mail-2-use-html']
		);

		$messages = isset( $contact_form->messages ) ? $contact_form->messages : array();

		foreach ( wpcf7_messages() as $key => $arr ) {
			$field_name = 'wpcf7-message-' . strtr( $key, '_', '-' );
			if ( isset( $_POST[$field_name] ) )
				$messages[$key] = trim( $_POST[$field_name] );
		}

		$additional_settings = trim( $_POST['wpcf7-additional-settings'] );

		$props = apply_filters( 'wpcf7_contact_form_admin_posted_properties',
			compact( 'form', 'mail', 'mail_2', 'messages', 'additional_settings' ) );

		foreach ( (array) $props as $key => $prop )
			$contact_form->{$key} = $prop;

		$query = array();
		$query['message'] = ( $contact_form->initial ) ? 'created' : 'saved';

		$contact_form->save();

		$query['contactform'] = $contact_form->id;
		$redirect_to = wpcf7_admin_url( $query );
		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( isset( $_POST['wpcf7-copy'] ) ) {
		$id = $_POST['post_ID'];
		check_admin_referer( 'wpcf7-copy_' . $id );

		$query = array();

		if ( $contact_form = wpcf7_contact_form( $id ) ) {
			$new_contact_form = $contact_form->copy();
			$new_contact_form->save();

			$query['contactform'] = $new_contact_form->id;
			$query['message'] = 'created';
		} else {
			$query['contactform'] = $contact_form->id;
		}

		$redirect_to = wpcf7_admin_url( $query );
		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( isset( $_POST['wpcf7-delete'] ) ) {
		$id = $_POST['post_ID'];
		check_admin_referer( 'wpcf7-delete_' . $id );

		if ( $contact_form = wpcf7_contact_form( $id ) )
			$contact_form->delete();

		$redirect_to = wpcf7_admin_url( array( 'message' => 'deleted' ) );
		wp_safe_redirect( $redirect_to );
		exit();
	}
}

add_action( 'admin_menu', 'wpcf7_admin_menu', 9 );

function wpcf7_admin_menu() {
	add_menu_page( __( 'Contact Form 7', 'wpcf7' ), __( 'Contact', 'wpcf7' ),
		WPCF7_ADMIN_READ_CAPABILITY, 'wpcf7', 'wpcf7_admin_management_page',
		wpcf7_plugin_url( 'admin/images/menu-icon.png' ) );

	add_submenu_page( 'wpcf7', __( 'Edit Contact Forms', 'wpcf7' ), __( 'Edit', 'wpcf7' ),
		WPCF7_ADMIN_READ_CAPABILITY, 'wpcf7', 'wpcf7_admin_management_page' );
}

add_action( 'admin_enqueue_scripts', 'wpcf7_admin_enqueue_styles' );

function wpcf7_admin_enqueue_styles() {
	global $plugin_page;

	if ( ! isset( $plugin_page ) || 'wpcf7' != $plugin_page )
		return;

	wp_enqueue_style( 'thickbox' );

	wp_enqueue_style( 'contact-form-7-admin', wpcf7_plugin_url( 'admin/styles.css' ),
		array(), WPCF7_VERSION, 'all' );

	if ( wpcf7_is_rtl() ) {
		wp_enqueue_style( 'contact-form-7-admin-rtl',
			wpcf7_plugin_url( 'admin/styles-rtl.css' ), array(), WPCF7_VERSION, 'all' );
	}
}

add_action( 'admin_enqueue_scripts', 'wpcf7_admin_enqueue_scripts' );

function wpcf7_admin_enqueue_scripts() {
	global $plugin_page, $wpcf7_tag_generators;

	if ( ! isset( $plugin_page ) || 'wpcf7' != $plugin_page )
		return;

	wp_enqueue_script( 'thickbox' );
	wp_enqueue_script( 'postbox' );

	wp_enqueue_script( 'wpcf7-admin-taggenerator', wpcf7_plugin_url( 'admin/taggenerator.js' ),
		array( 'jquery' ), WPCF7_VERSION, true );

	wp_enqueue_script( 'wpcf7-admin', wpcf7_plugin_url( 'admin/scripts.js' ),
		array( 'jquery', 'wpcf7-admin-taggenerator' ), WPCF7_VERSION, true );

	$taggenerators = array();

	foreach ( (array) $wpcf7_tag_generators as $name => $tg ) {
		$taggenerators[$name] = array_merge(
			(array) $tg['options'],
			array( 'title' => $tg['title'], 'content' => $tg['content'] ) );
	}

	wp_localize_script( 'wpcf7-admin', '_wpcf7', array(
		'generateTag' => __( 'Generate Tag', 'wpcf7' ),
		'pluginUrl' => wpcf7_plugin_url(),
		'tagGenerators' => $taggenerators ) );
}

add_action( 'admin_print_footer_scripts', 'wpcf7_print_taggenerators_json', 20 );

function wpcf7_print_taggenerators_json() { // for backward compatibility
	global $plugin_page, $wpcf7_tag_generators;

	if ( ! version_compare( get_bloginfo( 'version' ), '3.3-dev', '<' ) )
		return;

	if ( ! isset( $plugin_page ) || 'wpcf7' != $plugin_page )
		return;

	$taggenerators = array();

	foreach ( (array) $wpcf7_tag_generators as $name => $tg ) {
		$taggenerators[$name] = array_merge(
			(array) $tg['options'],
			array( 'title' => $tg['title'], 'content' => $tg['content'] ) );
	}

?>
<script type="text/javascript">
/* <![CDATA[ */
_wpcf7.tagGenerators = <?php echo json_encode( $taggenerators ) ?>;
/* ]]> */
</script>
<?php
}

function wpcf7_admin_management_page() {
	$contact_forms = get_posts( array(
		'numberposts' => -1,
		'orderby' => 'ID',
		'order' => 'ASC',
		'post_type' => 'wpcf7_contact_form' ) );

	$cf = null;
	$unsaved = false;

	if ( ! isset( $_GET['contactform'] ) )
		$_GET['contactform'] = '';

	if ( 'new' == $_GET['contactform'] && wpcf7_admin_has_edit_cap() ) {
		$unsaved = true;
		$current = -1;
		$cf = wpcf7_get_contact_form_default_pack(
			array( 'locale' => ( isset( $_GET['locale'] ) ? $_GET['locale'] : '' ) ) );
	} elseif ( $cf = wpcf7_contact_form( $_GET['contactform'] ) ) {
		$current = (int) $_GET['contactform'];
	} else {
		$first = reset( $contact_forms ); // Returns first item

		if ( $first ) {
			$current = $first->ID;
			$cf = wpcf7_contact_form( $current );
		}
	}

	require_once WPCF7_PLUGIN_DIR . '/admin/includes/meta-boxes.php';
	require_once WPCF7_PLUGIN_DIR . '/admin/edit.php';
}

/* Misc */

add_filter( 'plugin_action_links', 'wpcf7_plugin_action_links', 10, 2 );

function wpcf7_plugin_action_links( $links, $file ) {
	if ( $file != WPCF7_PLUGIN_BASENAME )
		return $links;

	$url = wpcf7_admin_url();

	$settings_link = '<a href="' . esc_attr( $url ) . '">'
		. esc_html( __( 'Settings', 'wpcf7' ) ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

add_action( 'wpcf7_admin_before_subsubsub', 'wpcf7_cf7com_links', 9 );

function wpcf7_cf7com_links( &$contact_form ) {
	$links = '<div class="cf7com-links">'
		. '<a href="' . esc_url_raw( __( 'http://contactform7.com/', 'wpcf7' ) ) . '" target="_blank">'
		. esc_html( __( 'Contactform7.com', 'wpcf7' ) ) . '</a>&ensp;'
		. '<a href="' . esc_url_raw( __( 'http://contactform7.com/docs/', 'wpcf7' ) ) . '" target="_blank">'
		. esc_html( __( 'Docs', 'wpcf7' ) ) . '</a> - '
		. '<a href="' . esc_url_raw( __( 'http://contactform7.com/faq/', 'wpcf7' ) ) . '" target="_blank">'
		. esc_html( __( 'FAQ', 'wpcf7' ) ) . '</a> - '
		. '<a href="' . esc_url_raw( __( 'http://contactform7.com/support/', 'wpcf7' ) ) . '" target="_blank">'
		. esc_html( __( 'Support', 'wpcf7' ) ) . '</a>'
		. '</div>';

	echo apply_filters( 'wpcf7_cf7com_links', $links );
}

add_action( 'wpcf7_admin_before_subsubsub', 'wpcf7_updated_message' );

function wpcf7_updated_message( &$contact_form ) {
	if ( ! isset( $_GET['message'] ) )
		return;

	switch ( $_GET['message'] ) {
		case 'created':
			$updated_message = __( "Contact form created.", 'wpcf7' );
			break;
		case 'saved':
			$updated_message = __( "Contact form saved.", 'wpcf7' );
			break;
		case 'deleted':
			$updated_message = __( "Contact form deleted.", 'wpcf7' );
			break;
	}

	if ( ! $updated_message )
		return;

?>
<div id="message" class="updated"><p><?php echo esc_html( $updated_message ); ?></p></div>
<?php
}

add_action( 'wpcf7_admin_before_subsubsub', 'wpcf7_donation_link' );

function wpcf7_donation_link( &$contact_form ) {
	if ( ! WPCF7_SHOW_DONATION_LINK )
		return;

	if ( 'new' == $_GET['contactform'] || ! empty( $_GET['message'] ) )
		return;

	$show_link = true;

	$num = mt_rand( 0, 99 );

	if ( $num >= 20 )
		$show_link = false;

	$show_link = apply_filters( 'wpcf7_show_donation_link', $show_link );

	if ( ! $show_link )
		return;

	$texts = array(
		__( "Contact Form 7 needs your support. Please donate today.", 'wpcf7' ),
		__( "Your contribution is needed for making this plugin better.", 'wpcf7' ) );

	$text = $texts[array_rand( $texts )];

?>
<div class="donation">
<p><a href="<?php echo esc_url_raw( __( 'http://contactform7.com/donate/', 'wpcf7' ) ); ?>"><?php echo esc_html( $text ); ?></a> <a href="<?php echo esc_url_raw( __( 'http://contactform7.com/donate/', 'wpcf7' ) ); ?>" class="button"><?php echo esc_html( __( "Donate", 'wpcf7' ) ); ?></a></p>
</div>
<?php
}

add_action( 'admin_notices', 'wpcf7_old_wp_version_error', 9 );

function wpcf7_old_wp_version_error() {
	global $plugin_page;

	if ( 'wpcf7' != $plugin_page )
		return;

	$wp_version = get_bloginfo( 'version' );

	if ( ! version_compare( $wp_version, WPCF7_REQUIRED_WP_VERSION, '<' ) )
		return;

?>
<div class="error">
<p><?php echo sprintf( __( '<strong>Contact Form 7 %1$s requires WordPress %2$s or higher.</strong> Please <a href="%3$s">update WordPress</a> first.', 'wpcf7' ), WPCF7_VERSION, WPCF7_REQUIRED_WP_VERSION, admin_url( 'update-core.php' ) ); ?></p>
</div>
<?php
}

?>