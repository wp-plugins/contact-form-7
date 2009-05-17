<?php

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