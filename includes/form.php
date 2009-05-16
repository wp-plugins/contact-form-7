<?php

function wpcf7_wp_head() {
	$stylesheet_url = WPCF7_PLUGIN_URL . '/stylesheet.css';
	echo '<link rel="stylesheet" href="' . $stylesheet_url . '" type="text/css" />';

	if ( 'rtl' == get_bloginfo( 'text_direction' ) ) {
		$stylesheet_rtl_url = WPCF7_PLUGIN_URL . '/stylesheet-rtl.css';
		echo '<link rel="stylesheet" href="' . $stylesheet_rtl_url . '" type="text/css" />';
	}
}

function wpcf7_load_js() {
	if ( ! is_admin() ) {
		wp_enqueue_script( 'contact-form-7',
			WPCF7_PLUGIN_URL . '/contact-form-7.js',
			array('jquery', 'jquery-form'), wpcf7_version(), true
		);
	}
}

?>