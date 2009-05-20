<?php

function wpcf7_admin_menu_parent() {
	global $wp_version;
	if ( version_compare( $wp_version, '2.7', '>=' ) )
		return 'tools.php';
	else
		return 'edit.php';
}

function wpcf7_admin_has_edit_cap() {
	return current_user_can( WPCF7_ADMIN_READ_WRITE_CAPABILITY );
}

function wpcf7_admin_add_pages() {
	if ( function_exists( 'admin_url' ) ) {
		$base_url = admin_url( wpcf7_admin_menu_parent() );
	} else {
		$base_url = get_option( 'siteurl' ) . '/wp-admin/' . wpcf7_admin_menu_parent();
	}

	$page = str_replace( '\\', '%5C', plugin_basename( __FILE__ ) );
	$contact_forms = wpcf7_contact_forms();

	if ( isset( $_POST['wpcf7-save'] ) && wpcf7_admin_has_edit_cap() ) {
		$id = $_POST['wpcf7-id'];
		check_admin_referer( 'wpcf7-save_' . $id );

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
		$options = array(
			'recipient' => trim( $_POST['wpcf7-options-recipient'] ) // For backward compatibility.
		);

		if ( array_key_exists( $id, $contact_forms ) ) {
			$contact_forms[$id] = compact( 'title', 'form', 'mail', 'mail_2', 'messages', 'options' );
			$redirect_to = $base_url . '?page=' . $page . '&contactform=' . $id . '&message=saved';
		} else {
			$key = ( empty( $contact_forms ) ) ? 1 : max( array_keys( $contact_forms ) ) + 1;
			$contact_forms[$key] = compact( 'title', 'form', 'mail', 'mail_2', 'messages', 'options' );
			$redirect_to = $base_url . '?page=' . $page . '&contactform=' . $key . '&message=created';
		}

		wpcf7_update_contact_forms( $contact_forms );

		wp_redirect( $redirect_to );
		exit();
	} elseif ( isset( $_POST['wpcf7-copy'] ) && wpcf7_admin_has_edit_cap() ) {
		$id = $_POST['wpcf7-id'];
		check_admin_referer( 'wpcf7-copy_' . $id );

		if ( array_key_exists( $id, $contact_forms ) ) {
			$key = max( array_keys( $contact_forms ) ) + 1;
			$contact_forms[$key] = $contact_forms[$id];
			$contact_forms[$key]['title'] .= '_copy';
			wpcf7_update_contact_forms( $contact_forms );
			$redirect_to = $base_url . '?page=' . $page . '&contactform=' . $key . '&message=created';
		} else {
			$redirect_to = $base_url . '?page=' . $page . '&contactform=' . $id;
		}

		wp_redirect( $redirect_to );
		exit();
	} elseif ( isset( $_POST['wpcf7-delete'] ) && wpcf7_admin_has_edit_cap() ) {
		$id = $_POST['wpcf7-id'];
		check_admin_referer( 'wpcf7-delete_' . $id );

		unset( $contact_forms[$id] );
		wpcf7_update_contact_forms( $contact_forms );

		wp_redirect( $base_url . '?page=' . $page . '&message=deleted' );
		exit();
	}

	add_management_page( __( 'Contact Form 7', 'wpcf7' ), __( 'Contact Form 7', 'wpcf7' ), WPCF7_ADMIN_READ_CAPABILITY, __FILE__, 'wpcf7_admin_management_page' );
}

add_action( 'admin_menu', 'wpcf7_admin_add_pages' );

function wpcf7_admin_head() {
	global $plugin_page;

	if ( isset( $plugin_page ) && $plugin_page == plugin_basename( __FILE__ ) ) {

		$admin_stylesheet_url = WPCF7_PLUGIN_URL . '/admin/admin-stylesheet.css';
		echo '<link rel="stylesheet" href="' . $admin_stylesheet_url . '" type="text/css" />';

		if ( 'rtl' == get_bloginfo( 'text_direction' ) ) {
			$admin_stylesheet_rtl_url = WPCF7_PLUGIN_URL . '/admin/admin-stylesheet-rtl.css';
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

	if ( wpcf7_admin_menu_parent() != $pagenow )
		return;

	if ( false === strpos( $_GET['page'], 'contact-form-7' ) )
		return;

	wp_enqueue_script( 'wpcf7-admin', WPCF7_PLUGIN_URL . '/admin/wpcf7-admin.js', array('jquery'), WPCF7_VERSION, true );
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

	if ( function_exists( 'admin_url' ) ) {
		$base_url = admin_url( wpcf7_admin_menu_parent() );
	} else {
		$base_url = get_option( 'siteurl' ) . '/wp-admin/' . wpcf7_admin_menu_parent();
	}

	$page = plugin_basename( __FILE__ );

	switch ( $_GET['message'] ) {
		case 'created':
			$updated_message = __( 'Contact form created.', 'wpcf7' );
			break;
		case 'saved':
			$updated_message = __( 'Contact form saved.', 'wpcf7' );
			break;
		case 'deleted':
			$updated_message = __( 'Contact form deleted.', 'wpcf7' );
			break;
	}

	$contact_forms = wpcf7_contact_forms();

	$id = $_POST['wpcf7-id'];

	if ( 'new' == $_GET['contactform'] ) {
		$unsaved = true;
		$current = -1;
		$cf = wpcf7_contact_form( wpcf7_default_pack( __( 'Untitled', 'wpcf7' ), true ) );
	} elseif ( array_key_exists( $_GET['contactform'], $contact_forms ) ) {
		$current = (int) $_GET['contactform'];
		$cf = wpcf7_contact_form( $contact_forms[$current] );
	} else {
		$current = (int) array_shift( array_keys( $contact_forms ) );
		$cf = wpcf7_contact_form( $contact_forms[$current] );
	}

	require_once WPCF7_PLUGIN_DIR . '/admin/admin-panel.php';
}

function wpcf7_default_form_template() {
	$template .= '<p>' . __( 'Your Name', 'wpcf7' ) . ' ' . __( '(required)', 'wpcf7' ) . '<br />' . "\n";
	$template .= '    [text* your-name] </p>' . "\n\n";
	$template .= '<p>' . __( 'Your Email', 'wpcf7' ) . ' ' . __( '(required)', 'wpcf7' ) . '<br />' . "\n";
	$template .= '    [email* your-email] </p>' . "\n\n";
	$template .= '<p>' . __( 'Subject', 'wpcf7' ) . '<br />' . "\n";
	$template .= '    [text your-subject] </p>' . "\n\n";
	$template .= '<p>' . __( 'Your Message', 'wpcf7' ) . '<br />' . "\n";
	$template .= '    [textarea your-message] </p>' . "\n\n";
	$template .= '<p>[submit "' . __( 'Send', 'wpcf7' ) . '"]</p>';
	return $template;
}

function wpcf7_default_mail_template() {
	$subject = '[your-subject]';
	$sender = '[your-name] <[your-email]>';
	$body = '[your-message]';
	$recipient = get_option( 'admin_email' );
	return compact( 'subject', 'sender', 'body', 'recipient' );
}

function wpcf7_default_mail_2_template() {
	$active = false;
	$subject = '[your-subject]';
	$sender = '[your-name] <[your-email]>';
	$body = '[your-message]';
	$recipient = '[your-email]';
	return compact( 'active', 'subject', 'sender', 'body', 'recipient' );
}

function wpcf7_default_messages_template() {
	$mail_sent_ok = wpcf7_default_message( 'mail_sent_ok' );
	$mail_sent_ng = wpcf7_default_message( 'mail_sent_ng' );
	$akismet_says_spam = wpcf7_default_message( 'akismet_says_spam' );
	$validation_error = wpcf7_default_message( 'validation_error' );
	$accept_terms = wpcf7_default_message( 'accept_terms' );
	$invalid_email = wpcf7_default_message( 'invalid_email' );
	$invalid_required = wpcf7_default_message( 'invalid_required' );
	$quiz_answer_not_correct = wpcf7_default_message( 'quiz_answer_not_correct' );
	$captcha_not_match = wpcf7_default_message( 'captcha_not_match' );
	$upload_failed = wpcf7_default_message( 'upload_failed' );
	$upload_file_type_invalid = wpcf7_default_message( 'upload_file_type_invalid' );
	$upload_file_too_large = wpcf7_default_message( 'upload_file_too_large' );

	return compact( 'mail_sent_ok', 'mail_sent_ng', 'akismet_says_spam',
		'validation_error', 'accept_terms', 'invalid_email', 'invalid_required', 'quiz_answer_not_correct',
		'captcha_not_match', 'upload_failed', 'upload_file_type_invalid', 'upload_file_too_large' );
}

function wpcf7_default_options_template() {
	$recipient = get_option( 'admin_email' ); // For backward compatibility.
	return compact( 'recipient' );
}

function wpcf7_default_pack( $title, $initial = false ) {
	$cf = array(
		'title' => $title,
		'form' => wpcf7_default_form_template(),
		'mail' => wpcf7_default_mail_template(),
		'mail_2' => wpcf7_default_mail_2_template(),
		'messages' => wpcf7_default_messages_template(),
		'options' => wpcf7_default_options_template()
	);

	if ( $initial )
		$cf['initial'] = true;

	return $cf;
}

function wpcf7_plugin_action_links( $links, $file ) {
	if ( $file != WPCF7_PLUGIN_BASENAME )
		return $links;

	if ( function_exists( 'admin_url' ) ) {
		$base_url = admin_url( wpcf7_admin_menu_parent() );
	} else {
		$base_url = get_option( 'siteurl' ) . '/wp-admin/' . wpcf7_admin_menu_parent();
	}

	$url = $base_url . '?page=' . plugin_basename( __FILE__ );

	$settings_link = '<a href="' . $url . '">' . __('Settings') . '</a>';

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

?>
<div class="donation">
<p><a href="http://www.pledgie.com/campaigns/3117">
<img alt="Click here to lend your support to: Support Contact Form 7 and make a donation at www.pledgie.com !" src="http://www.pledgie.com/campaigns/3117.png?skin_name=chrome" border="0" width="149" height="37" /></a>
<em><?php _e( "To keep developing good plugin needs user's support at any time.", 'wpcf7' ); ?></em>
</p>
</div>
<?php
}

?>