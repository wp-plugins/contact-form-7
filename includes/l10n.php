<?php

function wpcf7_l10n() {
	static $l10n = array();

	if ( ! empty( $l10n ) ) {
		return $l10n;
	}

	$l10n = array(
		'af' => __( 'Afrikaans', 'contact-form-7' ),
		'sq' => __( 'Albanian', 'contact-form-7' ),
		'ar' => __( 'Arabic', 'contact-form-7' ),
		'hy_AM' => __( 'Armenian', 'contact-form-7' ),
		'az' => __( 'Azerbaijani', 'contact-form-7' ),
		'bn_BD' => __( 'Bangla', 'contact-form-7' ),
		'eu' => __( 'Basque', 'contact-form-7' ),
		'be_BY' => __( 'Belarusian', 'contact-form-7' ),
		'bs_BA' => __( 'Bosnian', 'contact-form-7' ),
		'bg_BG' => __( 'Bulgarian', 'contact-form-7' ),
		'ca' => __( 'Catalan', 'contact-form-7' ),
		'ckb' => __( 'Central Kurdish', 'contact-form-7' ),
		'zh_CN' => __( 'Chinese (China)', 'contact-form-7' ),
		'zh_TW' => __( 'Chinese (Taiwan)', 'contact-form-7' ),
		'hr' => __( 'Croatian', 'contact-form-7' ),
		'cs_CZ' => __( 'Czech', 'contact-form-7' ),
		'da_DK' => __( 'Danish', 'contact-form-7' ),
		'nl_NL' => __( 'Dutch', 'contact-form-7' ),
		'en_US' => __( 'English (United States)', 'contact-form-7' ),
		'eo_EO' => __( 'Esperanto', 'contact-form-7' ),
		'et' => __( 'Estonian', 'contact-form-7' ),
		'fi' => __( 'Finnish', 'contact-form-7' ),
		'fr_FR' => __( 'French (France)', 'contact-form-7' ),
		'gl_ES' => __( 'Galician', 'contact-form-7' ),
		'gu_IN' => __( 'Gujarati', 'contact-form-7' ),
		'ka_GE' => __( 'Georgian', 'contact-form-7' ),
		'de_DE' => __( 'German', 'contact-form-7' ),
		'el' => __( 'Greek', 'contact-form-7' ),
		'ht' => __( 'Haitian', 'contact-form-7' ),
		'he_IL' => __( 'Hebrew', 'contact-form-7' ),
		'hi_IN' => __( 'Hindi', 'contact-form-7' ),
		'hu_HU' => __( 'Hungarian', 'contact-form-7' ),
		'bn_IN' => __( 'Indian Bengali', 'contact-form-7' ),
		'id_ID' => __( 'Indonesian', 'contact-form-7' ),
		'ga_IE' => __( 'Irish', 'contact-form-7' ),
		'it_IT' => __( 'Italian', 'contact-form-7' ),
		'ja' => __( 'Japanese', 'contact-form-7' ),
		'ko_KR' => __( 'Korean', 'contact-form-7' ),
		'lv' => __( 'Latvian', 'contact-form-7' ),
		'lt_LT' => __( 'Lithuanian', 'contact-form-7' ),
		'mk_MK' => __( 'Macedonian', 'contact-form-7' ),
		'ms_MY' => __( 'Malay', 'contact-form-7' ),
		'ml_IN' => __( 'Malayalam', 'contact-form-7' ),
		'mt_MT' => __( 'Maltese', 'contact-form-7' ),
		'nb_NO' => __( 'Norwegian (Bokmål)', 'contact-form-7' ),
		'fa_IR' => __( 'Persian', 'contact-form-7' ),
		'pl_PL' => __( 'Polish', 'contact-form-7' ),
		'pt_BR' => __( 'Portuguese (Brazil)', 'contact-form-7' ),
		'pt_PT' => __( 'Portuguese (Portugal)', 'contact-form-7' ),
		'pa_IN' => __( 'Punjabi', 'contact-form-7' ),
		'ru_RU' => __( 'Russian', 'contact-form-7' ),
		'ro_RO' => __( 'Romanian', 'contact-form-7' ),
		'sr_RS' => __( 'Serbian', 'contact-form-7' ),
		'si_LK' => __( 'Sinhala', 'contact-form-7' ),
		'sk_SK' => __( 'Slovak', 'contact-form-7' ),
		'sl_SI' => __( 'Slovene', 'contact-form-7' ),
		'es_ES' => __( 'Spanish (Spain)', 'contact-form-7' ),
		'sv_SE' => __( 'Swedish', 'contact-form-7' ),
		'ta' => __( 'Tamil', 'contact-form-7' ),
		'th' => __( 'Thai', 'contact-form-7' ),
		'tl' => __( 'Tagalog', 'contact-form-7' ),
		'tr_TR' => __( 'Turkish', 'contact-form-7' ),
		'uk' => __( 'Ukrainian', 'contact-form-7' ),
		'vi' => __( 'Vietnamese', 'contact-form-7' )
	);

	return $l10n;
}

function wpcf7_is_valid_locale( $locale ) {
	$l10n = wpcf7_l10n();
	return isset( $l10n[$locale] );
}

function wpcf7_is_rtl( $locale = '' ) {
	if ( empty( $locale ) ) {
		return function_exists( 'is_rtl' ) ? is_rtl() : false;
	}

	$rtl_locales = array(
		'ar' => 'Arabic',
		'he_IL' => 'Hebrew',
		'fa_IR' => 'Persian' );

	return isset( $rtl_locales[$locale] );
}

function wpcf7_load_textdomain( $locale = null ) {
	global $l10n;

	$domain = 'contact-form-7';

	if ( get_locale() == $locale ) {
		$locale = null;
	}

	if ( empty( $locale ) ) {
		if ( is_textdomain_loaded( $domain ) ) {
			return true;
		} else {
			return load_plugin_textdomain( $domain, false, $domain . '/languages' );
		}
	} else {
		$mo_orig = $l10n[$domain];
		unload_textdomain( $domain );

		$mofile = $domain . '-' . $locale . '.mo';
		$path = WP_PLUGIN_DIR . '/' . $domain . '/languages';

		if ( $loaded = load_textdomain( $domain, $path . '/'. $mofile ) ) {
			return $loaded;
		} else {
			$mofile = WP_LANG_DIR . '/plugins/' . $mofile;
			return load_textdomain( $domain, $mofile );
		}

		$l10n[$domain] = $mo_orig;
	}

	return false;
}
