<?php

function wpcf7_admin_has_edit_cap() {
	return current_user_can( WPCF7_ADMIN_READ_WRITE_CAPABILITY );
}

add_action( 'admin_menu', 'wpcf7_admin_add_pages', 9 );

function wpcf7_admin_add_pages() {

	if ( isset( $_POST['wpcf7-save'] ) && wpcf7_admin_has_edit_cap() ) {
		$id = $_POST['wpcf7-id'];
		check_admin_referer( 'wpcf7-save_' . $id );

		if ( ! $contact_form = wpcf7_contact_form( $id ) ) {
			$contact_form = new WPCF7_ContactForm();
			$contact_form->initial = true;
		}

		$title = trim( $_POST['wpcf7-title'] );

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

		$messages = $contact_form->messages;
		foreach ( wpcf7_messages() as $key => $arr ) {
			$field_name = 'wpcf7-message-' . strtr( $key, '_', '-' );
			if ( isset( $_POST[$field_name] ) )
				$messages[$key] = trim( $_POST[$field_name] );
		}

		$additional_settings = trim( $_POST['wpcf7-additional-settings'] );

		$query = array();
		$query['message'] = ( $contact_form->initial ) ? 'created' : 'saved';

		$contact_form->title = $title;
		$contact_form->form = $form;
		$contact_form->mail = $mail;
		$contact_form->mail_2 = $mail_2;
		$contact_form->messages = $messages;
		$contact_form->additional_settings = $additional_settings;

		$contact_form->save();

		$query['contactform'] = $contact_form->id;
		$redirect_to = wpcf7_admin_url( $query );
		wp_redirect( $redirect_to );
		exit();
	} elseif ( isset( $_POST['wpcf7-copy'] ) && wpcf7_admin_has_edit_cap() ) {
		$id = $_POST['wpcf7-id'];
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
		wp_redirect( $redirect_to );
		exit();
	} elseif ( isset( $_POST['wpcf7-delete'] ) && wpcf7_admin_has_edit_cap() ) {
		$id = $_POST['wpcf7-id'];
		check_admin_referer( 'wpcf7-delete_' . $id );

		if ( $contact_form = wpcf7_contact_form( $id ) )
			$contact_form->delete();

		$redirect_to = wpcf7_admin_url( array( 'message' => 'deleted' ) );
		wp_redirect( $redirect_to );
		exit();
	} elseif ( isset( $_GET['wpcf7-create-table'] ) ) {
		check_admin_referer( 'wpcf7-create-table' );

		$query = array();

		if ( ! wpcf7_table_exists() && current_user_can( 'activate_plugins' ) ) {
			wpcf7_install();
			if ( wpcf7_table_exists() ) {
				$query['message'] = 'table_created';
			} else {
				$query['message'] = 'table_not_created';
			}
		}

		wp_redirect( wpcf7_admin_url( $query ) );
		exit();
	}

	add_menu_page( __( 'Contact Form 7', 'wpcf7' ), __( 'Contact', 'wpcf7' ),
		WPCF7_ADMIN_READ_CAPABILITY, 'wpcf7', 'wpcf7_admin_management_page' );

	add_submenu_page( 'wpcf7', __( 'Edit Contact Forms', 'wpcf7' ), __( 'Edit', 'wpcf7' ),
		WPCF7_ADMIN_READ_CAPABILITY, 'wpcf7', 'wpcf7_admin_management_page' );
}

add_action( 'admin_print_styles', 'wpcf7_admin_enqueue_styles' );

function wpcf7_admin_enqueue_styles() {
	global $plugin_page;

	if ( ! isset( $plugin_page ) || 'wpcf7' != $plugin_page )
		return;

	wp_enqueue_style( 'thickbox' );

	wp_enqueue_style( 'contact-form-7-admin', wpcf7_plugin_url( 'admin/styles.css' ),
		array(), WPCF7_VERSION, 'all' );

	if ( 'rtl' == get_bloginfo( 'text_direction' ) ) {
		wp_enqueue_style( 'contact-form-7-admin-rtl',
			wpcf7_plugin_url( 'admin/styles-rtl.css' ), array(), WPCF7_VERSION, 'all' );
	}
}

add_action( 'admin_print_scripts', 'wpcf7_admin_enqueue_scripts' );

function wpcf7_admin_enqueue_scripts() {
	global $plugin_page;

	if ( ! isset( $plugin_page ) || 'wpcf7' != $plugin_page )
		return;

	wp_enqueue_script( 'thickbox' );

	wp_enqueue_script( 'wpcf7-admin-taggenerator', wpcf7_plugin_url( 'admin/taggenerator.js' ),
		array( 'jquery' ), WPCF7_VERSION, true );

	wp_enqueue_script( 'wpcf7-admin', wpcf7_plugin_url( 'admin/scripts.js' ),
		array( 'jquery', 'wpcf7-admin-taggenerator' ), WPCF7_VERSION, true );
	wp_localize_script( 'wpcf7-admin', '_wpcf7L10n', array(
		'generateTag' => __( 'Generate Tag', 'wpcf7' ),
		'show' => __( "Show", 'wpcf7' ),
		'hide' => __( "Hide", 'wpcf7' ) ) );
}

add_action( 'admin_footer', 'wpcf7_admin_footer' );

function wpcf7_admin_footer() {
	global $plugin_page;

	if ( ! isset( $plugin_page ) || 'wpcf7' != $plugin_page )
		return;

?>
<script type="text/javascript">
/* <![CDATA[ */
var _wpcf7 = {
	pluginUrl: '<?php echo wpcf7_plugin_url(); ?>',
	tagGenerators: {
<?php wpcf7_print_tag_generators(); ?>
	}
};
/* ]]> */
</script>
<?php
}

function wpcf7_admin_management_page() {
	$contact_forms = wpcf7_contact_forms();

	$unsaved = false;

	if ( ! isset( $_GET['contactform'] ) )
		$_GET['contactform'] = '';

	if ( 'new' == $_GET['contactform'] ) {
		$unsaved = true;
		$current = -1;
		$cf = wpcf7_contact_form_default_pack( isset( $_GET['locale'] ) ? $_GET['locale'] : '' );
	} elseif ( $cf = wpcf7_contact_form( $_GET['contactform'] ) ) {
		$current = (int) $_GET['contactform'];
	} else {
		$first = reset( $contact_forms ); // Returns first item
		$current = $first->id;
		$cf = wpcf7_contact_form( $current );
	}

	require_once WPCF7_PLUGIN_DIR . '/admin/edit.php';
}

/* Install and default settings */

add_action( 'activate_' . WPCF7_PLUGIN_BASENAME, 'wpcf7_install' );

function wpcf7_install() {
	global $wpdb, $wpcf7;

	if ( wpcf7_table_exists() )
		return; // Exists already

	$charset_collate = '';
	if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";
	}

	$wpdb->query( "CREATE TABLE IF NOT EXISTS $wpcf7->contactforms (
		cf7_unit_id bigint(20) unsigned NOT NULL auto_increment,
		title varchar(200) NOT NULL default '',
		form text NOT NULL,
		mail text NOT NULL,
		mail_2 text NOT NULL,
		messages text NOT NULL,
		additional_settings text NOT NULL,
		PRIMARY KEY (cf7_unit_id)) $charset_collate;" );

	if ( ! wpcf7_table_exists() )
		return false; // Failed to create

	$legacy_data = get_option( 'wpcf7' );
	if ( is_array( $legacy_data )
		&& is_array( $legacy_data['contact_forms'] ) && $legacy_data['contact_forms'] ) {
		foreach ( $legacy_data['contact_forms'] as $key => $value ) {
			$wpdb->insert( $wpcf7->contactforms, array(
				'cf7_unit_id' => $key,
				'title' => $value['title'],
				'form' => maybe_serialize( $value['form'] ),
				'mail' => maybe_serialize( $value['mail'] ),
				'mail_2' => maybe_serialize( $value['mail_2'] ),
				'messages' => maybe_serialize( $value['messages'] ),
				'additional_settings' => maybe_serialize( $value['additional_settings'] )
				), array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) );
		}
	} else {
		wpcf7_load_plugin_textdomain();

		$wpdb->insert( $wpcf7->contactforms, array(
			'title' => __( 'Contact form', 'wpcf7' ) . ' 1',
			'form' => maybe_serialize( wpcf7_default_form_template() ),
			'mail' => maybe_serialize( wpcf7_default_mail_template() ),
			'mail_2' => maybe_serialize ( wpcf7_default_mail_2_template() ),
			'messages' => maybe_serialize( wpcf7_default_messages_template() ) ) );
	}
}

/* Misc */

add_filter( 'plugin_action_links', 'wpcf7_plugin_action_links', 10, 2 );

function wpcf7_plugin_action_links( $links, $file ) {
	if ( $file != WPCF7_PLUGIN_BASENAME )
		return $links;

	$url = wpcf7_admin_url( array( 'page' => 'wpcf7' ) );

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
		case 'table_created':
			$updated_message = __( "Database table created.", 'wpcf7' );
			break;
		case 'table_not_created':
			$updated_message = __( "Failed to create database table.", 'wpcf7' );
			break;
	}

	if ( ! $updated_message )
		return;

?>
<div id="message" class="updated fade"><p><?php echo esc_html( $updated_message ); ?></p></div>
<?php
}

add_action( 'wpcf7_admin_before_subsubsub', 'wpcf7_donation_link' );

function wpcf7_donation_link( &$contact_form ) {
	if ( ! WPCF7_SHOW_DONATION_LINK )
		return;

	if ( 'new' == $_GET['contactform'] || ! empty($_GET['message']) )
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

?>