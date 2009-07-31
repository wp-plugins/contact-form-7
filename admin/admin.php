<?php

function wpcf7_admin_has_edit_cap() {
	return current_user_can( WPCF7_ADMIN_READ_WRITE_CAPABILITY );
}

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
			'use_html' => ( 1 == $_POST['wpcf7-mail-use-html'] ) ? true : false
		);
		$mail_2 = array(
			'active' => ( 1 == $_POST['wpcf7-mail-2-active'] ) ? true : false,
			'subject' => trim( $_POST['wpcf7-mail-2-subject'] ),
			'sender' => trim( $_POST['wpcf7-mail-2-sender'] ),
			'body' => trim( $_POST['wpcf7-mail-2-body'] ),
			'recipient' => trim( $_POST['wpcf7-mail-2-recipient'] ),
			'additional_headers' => trim( $_POST['wpcf7-mail-2-additional-headers'] ),
			'attachments' => trim( $_POST['wpcf7-mail-2-attachments'] ),
			'use_html' => ( 1 == $_POST['wpcf7-mail-2-use-html'] ) ? true : false
		);
		$messages = array(
			'mail_sent_ok' => trim( $_POST['wpcf7-message-mail-sent-ok'] ),
			'mail_sent_ng' => trim( $_POST['wpcf7-message-mail-sent-ng'] ),
			'akismet_says_spam' => trim( $_POST['wpcf7-message-akismet-says-spam'] ),
			'validation_error' => trim( $_POST['wpcf7-message-validation-error'] ),
			'accept_terms' => trim( $_POST['wpcf7-message-accept-terms'] ),
			'invalid_email' => trim( $_POST['wpcf7-message-invalid-email'] ),
			'invalid_required' => trim( $_POST['wpcf7-message-invalid-required'] ),
			'quiz_answer_not_correct' => trim( $_POST['wpcf7-message-quiz-answer-not-correct'] ),
			'captcha_not_match' => trim( $_POST['wpcf7-message-captcha-not-match'] ),
			'upload_failed' => trim( $_POST['wpcf7-message-upload-failed'] ),
			'upload_file_type_invalid' => trim( $_POST['wpcf7-message-upload-file-type-invalid'] ),
			'upload_file_too_large' => trim( $_POST['wpcf7-message-upload-file-too-large'] )
		);
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
		$redirect_to = wpcf7_admin_url( 'admin.php', $query );
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

		$redirect_to = wpcf7_admin_url( 'admin.php', $query );
		wp_redirect( $redirect_to );
		exit();
	} elseif ( isset( $_POST['wpcf7-delete'] ) && wpcf7_admin_has_edit_cap() ) {
		$id = $_POST['wpcf7-id'];
		check_admin_referer( 'wpcf7-delete_' . $id );

		if ( $contact_form = wpcf7_contact_form( $id ) )
			$contact_form->delete();

		$redirect_to = wpcf7_admin_url( 'admin.php', array( 'message' => 'deleted' ) );
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

		wp_redirect( wpcf7_admin_url( 'admin.php', $query ) );
		exit();
	}

	add_menu_page( __( 'Contact Form 7', 'wpcf7' ), __( 'Contact', 'wpcf7' ),
		WPCF7_ADMIN_READ_CAPABILITY, __FILE__, 'wpcf7_admin_management_page' );

	add_submenu_page( __FILE__, __( 'Edit Contact Forms', 'wpcf7' ), __( 'Edit', 'wpcf7' ),
		WPCF7_ADMIN_READ_CAPABILITY, __FILE__, 'wpcf7_admin_management_page' );
}

add_action( 'admin_menu', 'wpcf7_admin_add_pages' );

function wpcf7_admin_head() {
	global $plugin_page;

	if ( isset( $plugin_page ) && $plugin_page == plugin_basename( __FILE__ ) ) {

		$admin_stylesheet_url = wpcf7_plugin_url( 'admin/admin-stylesheet.css' );
		echo '<link rel="stylesheet" href="' . $admin_stylesheet_url . '" type="text/css" />';

		if ( 'rtl' == get_bloginfo( 'text_direction' ) ) {
			$admin_stylesheet_rtl_url = wpcf7_plugin_url( 'admin/admin-stylesheet-rtl.css' );
			echo '<link rel="stylesheet" href="' . $admin_stylesheet_rtl_url . '" type="text/css" />';
		}

?>
<script type="text/javascript">
//<![CDATA[
var _wpcf7 = {
	captchaMod: <?php echo ( class_exists( 'ReallySimpleCaptcha' ) ) ? 'true' : 'false' ?>
};
//]]>
</script>
<?php
	}
}

add_action( 'admin_head', 'wpcf7_admin_head' );

function wpcf7_admin_load_js() {
	global $pagenow;

	if ( ! is_admin() )
		return;

	if ( 'admin.php' != $pagenow )
		return;

	if ( false === strpos( $_GET['page'], 'contact-form-7' ) )
		return;

	wp_enqueue_script( 'wpcf7-admin', wpcf7_plugin_url( 'admin/wpcf7-admin.js' ), array('jquery'), WPCF7_VERSION, true );
	wp_localize_script( 'wpcf7-admin', '_wpcf7L10n', array(
		'optional' => __( 'optional', 'wpcf7' ),
		'generateTag' => __( 'Generate Tag', 'wpcf7' ),
		'textField' => __( 'Text field', 'wpcf7' ),
		'emailField' => __( 'Email field', 'wpcf7' ),
		'textArea' => __( 'Text area', 'wpcf7' ),
		'menu' => __( 'Drop-down menu', 'wpcf7' ),
		'checkboxes' => __( 'Checkboxes', 'wpcf7' ),
		'radioButtons' => __( 'Radio buttons', 'wpcf7' ),
		'acceptance' => __( 'Acceptance', 'wpcf7' ),
		'isAcceptanceDefaultOn' => __( "Make this checkbox checked by default?", 'wpcf7' ),
		'isAcceptanceInvert' => __( "Make this checkbox work inversely?", 'wpcf7' ),
		'isAcceptanceInvertMeans' => __( "* That means visitor who accepts the term unchecks it.", 'wpcf7' ),
		'captcha' => __( 'CAPTCHA', 'wpcf7' ),
		'quiz' => __( 'Quiz', 'wpcf7' ),
		'quizzes' => __( 'Quizzes', 'wpcf7' ),
		'quizFormatDesc' => __( "* quiz|answer (e.g. 1+1=?|2)", 'wpcf7' ),
		'fileUpload' => __( 'File upload', 'wpcf7' ),
		'bytes' => __( 'bytes', 'wpcf7' ),
		'submit' => __( 'Submit button', 'wpcf7' ),
		'tagName' => __( 'Name', 'wpcf7' ),
		'isRequiredField' => __( 'Required field?', 'wpcf7' ),
		'allowsMultipleSelections' => __( 'Allow multiple selections?', 'wpcf7' ),
		'insertFirstBlankOption' => __( 'Insert a blank item as the first option?', 'wpcf7' ),
		'makeCheckboxesExclusive' => __( 'Make checkboxes exclusive?', 'wpcf7' ),
		'menuChoices' => __( 'Choices', 'wpcf7' ),
		'label' => __( 'Label', 'wpcf7' ),
		'defaultValue' => __( 'Default value', 'wpcf7' ),
		'akismet' => __( 'Akismet', 'wpcf7' ),
		'akismetAuthor' => __( "This field requires author's name", 'wpcf7' ),
		'akismetAuthorUrl' => __( "This field requires author's URL", 'wpcf7' ),
		'akismetAuthorEmail' => __( "This field requires author's email address", 'wpcf7' ),
		'generatedTag' => __( "Copy this code and paste it into the form left.", 'wpcf7' ),
		'fgColor' => __( "Foreground color", 'wpcf7' ),
		'bgColor' => __( "Background color", 'wpcf7' ),
		'imageSize' => __( "Image size", 'wpcf7' ),
		'imageSizeSmall' => __( "Small", 'wpcf7' ),
		'imageSizeMedium' => __( "Medium", 'wpcf7' ),
		'imageSizeLarge' => __( "Large", 'wpcf7' ),
		'imageSettings' => __( "Image settings", 'wpcf7' ),
		'inputFieldSettings' => __( "Input field settings", 'wpcf7' ),
		'tagForImage' => __( "For image", 'wpcf7' ),
		'tagForInputField' => __( "For input field", 'wpcf7' ),
		'oneChoicePerLine' => __( "* One choice per line.", 'wpcf7' ),
		'show' => __( "Show", 'wpcf7' ),
		'hide' => __( "Hide", 'wpcf7' ),
		'fileSizeLimit' => __( "File size limit", 'wpcf7' ),
		'acceptableFileTypes' => __( "Acceptable file types", 'wpcf7' ),
		'needReallySimpleCaptcha' => __( "Note: To use CAPTCHA, you need Really Simple CAPTCHA plugin installed.", 'wpcf7' )
	) );
}

add_action( 'wp_print_scripts', 'wpcf7_admin_load_js' );

function wpcf7_admin_management_page() {
	global $wp_version;

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

	$contact_forms = wpcf7_contact_forms();

	$id = $_POST['wpcf7-id'];

	if ( 'new' == $_GET['contactform'] ) {
		$unsaved = true;
		$current = -1;
		$cf = wpcf7_contact_form_default_pack();
	} elseif ( $cf = wpcf7_contact_form( $_GET['contactform'] ) ) {
		$current = (int) $_GET['contactform'];
	} else {
		$first = reset( $contact_forms ); // Returns first item
		$current = $first->id;
		$cf = wpcf7_contact_form( $current );
	}

	require_once WPCF7_PLUGIN_DIR . '/admin/admin-panel.php';
}

/* Install and default settings */

function wpcf7_install() {
	global $wpdb;

	if ( wpcf7_table_exists() )
		return; // Exists already

	$table_name = wpcf7_table_name();

	$charset_collate = '';
	if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";
	}

	$wpdb->query( "CREATE TABLE IF NOT EXISTS $table_name (
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
	if ( is_array( $legacy_data ) ) {
		foreach ( $legacy_data['contact_forms'] as $key => $value ) {
			$wpdb->insert( $table_name, array(
				'cf7_unit_id' => $key,
				'title' => $value['title'],
				'form' => maybe_serialize( $value['form'] ),
				'mail' => maybe_serialize( $value['mail'] ),
				'mail_2' => maybe_serialize( $value['mail_2'] ),
				'messages' => maybe_serialize( $value['messages'] ),
				'additional_settings' => maybe_serialize( $value['additional_settings'] )
				), array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) );
		}

		// delete_option( 'wpcf7' ); // Comment out for downgrading case for a while
	} else {
		wpcf7_load_plugin_textdomain();

		$wpdb->insert( $table_name, array(
			'title' => __( 'Contact form', 'wpcf7' ) . ' 1',
			'form' => maybe_serialize( wpcf7_default_form_template() ),
			'mail' => maybe_serialize( wpcf7_default_mail_template() ),
			'mail_2' => maybe_serialize ( wpcf7_default_mail_2_template() ),
			'messages' => maybe_serialize( wpcf7_default_messages_template() ) ) );
	}
}

add_action( 'activate_' . WPCF7_PLUGIN_BASENAME, 'wpcf7_install' );

/* Misc */

function wpcf7_admin_url( $file, $query = array() ) {
	$file = trim( $file, ' /' );
	if ( 'admin/' != substr( $file, 0, 6 ) )
		$file = 'admin/' . $file;

	$path = 'admin.php';
	$path .= '?page=' . WPCF7_PLUGIN_NAME . '/' . $file;

	if ( $query = build_query( $query ) )
		$path .= '&' . $query;

	$url = admin_url( $path );

	return $url;
}

function wpcf7_plugin_action_links( $links, $file ) {
	if ( $file != WPCF7_PLUGIN_BASENAME )
		return $links;

	$url = wpcf7_admin_url( 'admin.php' );

	$settings_link = '<a href="' . $url . '">' . esc_html( __( 'Settings', 'wpcf7' ) ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

add_filter( 'plugin_action_links', 'wpcf7_plugin_action_links', 10, 2 );

function wpcf7_donation_link() {
	if ( ! WPCF7_SHOW_DONATION_LINK )
		return;

	if ( 'new' == $_GET['contactform'] || ! empty($_GET['message']) )
		return;

	$num = mt_rand(0, 99);
	if ($num >= 10) // 90%
		return;

	$texts = array(
		__( "Contact Form 7 needs your support. Please donate today.", 'wpcf7' ),
		__( "Is this plugin useful for you? If you like it, please help the developer.", 'wpcf7' ),
		__( "Your contribution is needed for making this plugin better.", 'wpcf7' ),
		__( "Developing a plugin and providing user support is really hard work. Please help.", 'wpcf7' ) );

	$text = $texts[array_rand( $texts )];

?>
<div class="donation">
<p><a href="http://www.pledgie.com/campaigns/3117">
<img alt="Click here to lend your support to: Support Contact Form 7 and make a donation at www.pledgie.com !" src="http://www.pledgie.com/campaigns/3117.png?skin_name=chrome" border="0" width="149" height="37" /></a>
<em><?php echo esc_html( $text ); ?></em>
</p>
</div>
<?php
}

?>