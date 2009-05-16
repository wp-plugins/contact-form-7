<?php

class WPCF7 {

	var $contact_forms;
	var $version;

	function WPCF7() {
		$wpcf7 = get_option( 'wpcf7' );

		$contact_forms = $wpcf7['contact_forms'];
		if ( ! is_array( $contact_forms ) )
			$contact_forms = array();

		foreach ( $contact_forms as $key => $value ) {
			$contact_form = new WPCF7_ContactForm( $value );
			$this->contact_forms[$key] = $contact_form;
		}

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
		return $this->contact_forms();
	}

}

class WPCF7_ContactForm {

	var $title;
	var $form;
	var $mail;
	var $mail_2;
	var $messages;
	var $options;

	function WPCF7_ContactForm( $data ) {
		$this->title = $data[$title];
		$this->form = $data[$form];
		$this->mail = $data[$mail];
		$this->mail_2 = $data[$mail_2];
		$this->messages = $data[$messages];
		$this->options = $data[$options];
	}
}

?>