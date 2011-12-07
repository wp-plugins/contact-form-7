<?php

function wpcf7_plugin_path( $path = '' ) {
	return path_join( WPCF7_PLUGIN_DIR, trim( $path, '/' ) );
}

function wpcf7_plugin_url( $path = '' ) {
	return plugins_url( $path, WPCF7_PLUGIN_BASENAME );
}

function wpcf7_admin_url( $args = array() ) {
	$defaults = array( 'page' => 'wpcf7' );
	$args = wp_parse_args( $args, $defaults );

	$url = menu_page_url( $args['page'], false );
	unset( $args['page'] );

	$url = add_query_arg( $args, $url );

	return esc_url_raw( $url );
}

function wpcf7_messages() {
	$messages = array(
		'mail_sent_ok' => array(
			'description' => __( "Sender's message was sent successfully", 'wpcf7' ),
			'default' => __( 'Your message was sent successfully. Thanks.', 'wpcf7' )
		),

		'mail_sent_ng' => array(
			'description' => __( "Sender's message was failed to send", 'wpcf7' ),
			'default' => __( 'Failed to send your message. Please try later or contact the administrator by another method.', 'wpcf7' )
		),

		'validation_error' => array(
			'description' => __( "Validation errors occurred", 'wpcf7' ),
			'default' => __( 'Validation errors occurred. Please confirm the fields and submit it again.', 'wpcf7' )
		),

		'accept_terms' => array(
			'description' => __( "There are terms that the sender must accept", 'wpcf7' ),
			'default' => __( 'Please accept the terms to proceed.', 'wpcf7' )
		),

		'invalid_email' => array(
			'description' => __( "Email address that the sender entered is invalid", 'wpcf7' ),
			'default' => __( 'Email address seems invalid.', 'wpcf7' )
		),

		'invalid_required' => array(
			'description' => __( "There is a field that the sender must fill in", 'wpcf7' ),
			'default' => __( 'Please fill the required field.', 'wpcf7' )
		)
	);

	return apply_filters( 'wpcf7_messages', $messages );
}

function wpcf7_get_default_template( $prop = 'form' ) {
	if ( 'form' == $prop )
		$template = wpcf7_default_form_template();
	elseif ( 'mail' == $prop )
		$template = wpcf7_default_mail_template();
	elseif ( 'mail_2' == $prop )
		$template = wpcf7_default_mail_2_template();
	elseif ( 'messages' == $prop )
		$template = wpcf7_default_messages_template();
	else
		$template = null;

	return apply_filters( 'wpcf7_default_template', $template, $prop );
}

function wpcf7_default_form_template() {
	$template =
		'<p>' . __( 'Your Name', 'wpcf7' ) . ' ' . __( '(required)', 'wpcf7' ) . '<br />' . "\n"
		. '    [text* your-name] </p>' . "\n\n"
		. '<p>' . __( 'Your Email', 'wpcf7' ) . ' ' . __( '(required)', 'wpcf7' ) . '<br />' . "\n"
		. '    [email* your-email] </p>' . "\n\n"
		. '<p>' . __( 'Subject', 'wpcf7' ) . '<br />' . "\n"
		. '    [text your-subject] </p>' . "\n\n"
		. '<p>' . __( 'Your Message', 'wpcf7' ) . '<br />' . "\n"
		. '    [textarea your-message] </p>' . "\n\n"
		. '<p>[submit "' . __( 'Send', 'wpcf7' ) . '"]</p>';

	return $template;
}

function wpcf7_default_mail_template() {
	$subject = '[your-subject]';
	$sender = '[your-name] <[your-email]>';
	$body = sprintf( __( 'From: %s', 'wpcf7' ), '[your-name] <[your-email]>' ) . "\n"
		. sprintf( __( 'Subject: %s', 'wpcf7' ), '[your-subject]' ) . "\n\n"
		. __( 'Message Body:', 'wpcf7' ) . "\n" . '[your-message]' . "\n\n" . '--' . "\n"
		. sprintf( __( 'This mail is sent via contact form on %1$s %2$s', 'wpcf7' ),
			get_bloginfo( 'name' ), get_bloginfo( 'url' ) );
	$recipient = get_option( 'admin_email' );
	$additional_headers = '';
	$attachments = '';
	$use_html = 0;
	return compact( 'subject', 'sender', 'body', 'recipient', 'additional_headers', 'attachments', 'use_html' );
}

function wpcf7_default_mail_2_template() {
	$active = false;
	$subject = '[your-subject]';
	$sender = '[your-name] <[your-email]>';
	$body = __( 'Message body:', 'wpcf7' ) . "\n" . '[your-message]' . "\n\n" . '--' . "\n"
		. sprintf( __( 'This mail is sent via contact form on %1$s %2$s', 'wpcf7' ),
			get_bloginfo( 'name' ), get_bloginfo( 'url' ) );
	$recipient = '[your-email]';
	$additional_headers = '';
	$attachments = '';
	$use_html = 0;
	return compact( 'active', 'subject', 'sender', 'body', 'recipient', 'additional_headers', 'attachments', 'use_html' );
}

function wpcf7_default_messages_template() {
	$messages = array();

	foreach ( wpcf7_messages() as $key => $arr ) {
		$messages[$key] = $arr['default'];
	}

	return $messages;
}

function wpcf7_upload_dir( $type = false ) {
	global $switched;

	$siteurl = get_option( 'siteurl' );
	$upload_path = trim( get_option( 'upload_path' ) );

	$main_override = is_multisite() && defined( 'MULTISITE' ) && is_main_site();

	if ( empty( $upload_path ) ) {
		$dir = WP_CONTENT_DIR . '/uploads';
	} else {
		$dir = $upload_path;

		if ( 'wp-content/uploads' == $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos( $dir, ABSPATH ) ) {
			// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			$dir = path_join( ABSPATH, $dir );
		}
	}

	if ( ! $url = get_option( 'upload_url_path' ) ) {
		if ( empty( $upload_path )
		|| ( 'wp-content/uploads' == $upload_path )
		|| ( $upload_path == $dir ) )
			$url = WP_CONTENT_URL . '/uploads';
		else
			$url = trailingslashit( $siteurl ) . $upload_path;
	}

	if ( defined( 'UPLOADS' ) && ! $main_override
	&& ( ! isset( $switched ) || $switched === false ) ) {
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	if ( is_multisite() && ! $main_override
	&& ( ! isset( $switched ) || $switched === false ) ) {

		if ( defined( 'BLOGUPLOADDIR' ) )
			$dir = untrailingslashit( BLOGUPLOADDIR );

		$url = str_replace( UPLOADS, 'files', $url );
	}

	$uploads = apply_filters( 'wpcf7_upload_dir', array( 'dir' => $dir, 'url' => $url ) );

	if ( 'dir' == $type )
		return $uploads['dir'];
	if ( 'url' == $type )
		return $uploads['url'];

	return $uploads;
}

function wpcf7_l10n() {
	$l10n = array(
		'af' => __( 'Afrikaans', 'wpcf7' ),
		'sq' => __( 'Albanian', 'wpcf7' ),
		'ar' => __( 'Arabic', 'wpcf7' ),
		'hy_AM' => __( 'Armenian', 'wpcf7' ),
		'bn_BD' => __( 'Bangla', 'wpcf7' ),
		'be_BY' => __( 'Belarusian', 'wpcf7' ),
		'bs' => __( 'Bosnian', 'wpcf7' ),
		'pt_BR' => __( 'Brazilian Portuguese', 'wpcf7' ),
		'bg_BG' => __( 'Bulgarian', 'wpcf7' ),
		'ca' => __( 'Catalan', 'wpcf7' ),
		'zh_CN' => __( 'Chinese (Simplified)', 'wpcf7' ),
		'zh_TW' => __( 'Chinese (Traditional)', 'wpcf7' ),
		'hr' => __( 'Croatian', 'wpcf7' ),
		'cs_CZ' => __( 'Czech', 'wpcf7' ),
		'da_DK' => __( 'Danish', 'wpcf7' ),
		'nl_NL' => __( 'Dutch', 'wpcf7' ),
		'en_US' => __( 'English', 'wpcf7' ),
		'eo_EO' => __( 'Esperanto', 'wpcf7' ),
		'et' => __( 'Estonian', 'wpcf7' ),
		'fi' => __( 'Finnish', 'wpcf7' ),
		'fr_FR' => __( 'French', 'wpcf7' ),
		'gl_ES' => __( 'Galician', 'wpcf7' ),
		'ka_GE' => __( 'Georgian', 'wpcf7' ),
		'de_DE' => __( 'German', 'wpcf7' ),
		'el' => __( 'Greek', 'wpcf7' ),
		'he_IL' => __( 'Hebrew', 'wpcf7' ),
		'hi_IN' => __( 'Hindi', 'wpcf7' ),
		'hu_HU' => __( 'Hungarian', 'wpcf7' ),
		'id_ID' => __( 'Indonesian', 'wpcf7' ),
		'it_IT' => __( 'Italian', 'wpcf7' ),
		'ja' => __( 'Japanese', 'wpcf7' ),
		'ko_KR' => __( 'Korean', 'wpcf7' ),
		'lv' => __( 'Latvian', 'wpcf7' ),
		'lt_LT' => __( 'Lithuanian', 'wpcf7' ),
		'mk_MK' => __( 'Macedonian', 'wpcf7' ),
		'ms_MY' => __( 'Malay', 'wpcf7' ),
		'ml_IN' => __( 'Malayalam', 'wpcf7' ),
		'mt_MT' => __( 'Maltese', 'wpcf7' ),
		'nb_NO' => __( 'Norwegian', 'wpcf7' ),
		'fa_IR' => __( 'Persian', 'wpcf7' ),
		'pl_PL' => __( 'Polish', 'wpcf7' ),
		'pt_PT' => __( 'Portuguese', 'wpcf7' ),
		'ru_RU' => __( 'Russian', 'wpcf7' ),
		'ro_RO' => __( 'Romanian', 'wpcf7' ),
		'sr_RS' => __( 'Serbian', 'wpcf7' ),
		'si_LK' => __( 'Sinhala', 'wpcf7' ),
		'sk_SK' => __( 'Slovak', 'wpcf7' ),
		'sl_SI' => __( 'Slovene', 'wpcf7' ),
		'es_ES' => __( 'Spanish', 'wpcf7' ),
		'sv_SE' => __( 'Swedish', 'wpcf7' ),
		'ta' => __( 'Tamil', 'wpcf7' ),
		'th' => __( 'Thai', 'wpcf7' ),
		'tl' => __( 'Tagalog', 'wpcf7' ),
		'tr_TR' => __( 'Turkish', 'wpcf7' ),
		'uk' => __( 'Ukrainian', 'wpcf7' ),
		'vi' => __( 'Vietnamese', 'wpcf7' )
	);

	return $l10n;
}

function wpcf7_is_rtl() {
	if ( function_exists( 'is_rtl' ) )
		return is_rtl();

	return false;
}

?>