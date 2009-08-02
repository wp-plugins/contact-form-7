<?php

function wpcf7_load_plugin_textdomain() {
	load_plugin_textdomain( 'wpcf7',
		'wp-content/plugins/contact-form-7/languages', 'contact-form-7/languages' );
}

add_action( 'init', 'wpcf7_load_plugin_textdomain' );

?>