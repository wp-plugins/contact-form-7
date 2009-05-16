<?php

class WPCF7 {

	var $contact_forms;
	var $version;

	function WPCF7() {
		$wpcf7 = get_option( 'wpcf7' );

		$this->contact_forms = $wpcf7['contact_forms'];
		if ( ! is_array( $this->contact_forms ) )
			$this->contact_forms = array();

		$this->version = $wpcf7['version'];
	}

	function save() {
		$wpcf7 = array(
			'contact_forms' => $this->contact_forms,
			'version' => $this->version
		);

		update_option( 'wpcf7', $wpcf7 );
	}

	function get_contact_forms() {
	}

}

class WPCF7_ContactForm {

	var $title;
	var $form;
	var $mail;
	var $mail_2;
	var $messages;
	var $options;
}

?>