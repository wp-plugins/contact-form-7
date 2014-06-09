<?php

class WPCF7_Submission {

	private static $instance;

	private $contact_form;

	private $result = array(
		'status' => 'init',
		'valid' => true,
		'invalid_reasons' => array(),
		'invalid_fields' => array(),
		'spam' => false,
		'message' => '',
		'mail_sent' => false,
		'scripts_on_sent_ok' => null,
		'scripts_on_submit' => null );

	private $posted_data = array();

	private function __construct() {}

	public static function get_instance( WPCF7_ContactForm $contact_form ) {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
			self::$instance->contact_form = $contact_form;
		}

		return self::$instance;
	}

	public function submit() {
		$this->setup_posted_data();
		$validation = $this->validate();

		$result = &$this->result;
		$contact_form = &$this->contact_form;

		if ( ! $validation['valid'] ) { // Validation error occured
			$result['status'] = 'validation_failed';
			$result['valid'] = false;
			$result['invalid_reasons'] = $validation['reason'];
			$result['invalid_fields'] = $validation['idref'];
			$result['message'] = $contact_form->message( 'validation_error' );

		} elseif ( ! $this->accepted() ) { // Not accepted terms
			$result['status'] = 'acceptance_missing';
			$result['message'] = $contact_form->message( 'accept_terms' );

		} elseif ( $this->spam() ) { // Spam!
			$result['status'] = 'spam';
			$result['message'] = $contact_form->message( 'spam' );
			$result['spam'] = true;

		} elseif ( $contact_form->mail() ) {
			$result['status'] = 'mail_sent';
			$result['mail_sent'] = true;
			$result['message'] = $contact_form->message( 'mail_sent_ok' );

		} else {
			$result['status'] = 'mail_failed';
			$result['message'] = $contact_form->message( 'mail_sent_ng' );
		}

		return $result;
	}

	public function get_posted_data() {
		return $this->posted_data;
	}

	private function setup_posted_data() {
		$posted_data = (array) $_POST;

		$tags = $this->contact_form->form_scan_shortcode();

		foreach ( (array) $tags as $tag ) {
			if ( empty( $tag['name'] ) ) {
				continue;
			}

			$name = $tag['name'];
			$value = '';

			if ( isset( $posted_data[$name] ) ) {
				$value = $posted_data[$name];
			}

			$pipes = $tag['pipes'];

			if ( WPCF7_USE_PIPE
			&& is_a( $pipes, 'WPCF7_Pipes' )
			&& ! $pipes->zero() ) {
				if ( is_array( $value) ) {
					$new_value = array();

					foreach ( $value as $v ) {
						$new_value[] = $pipes->do_pipe( wp_unslash( $v ) );
					}

					$value = $new_value;
				} else {
					$value = $pipes->do_pipe( wp_unslash( $value ) );
				}
			}

			$posted_data[$name] = $value;
		}

		$this->posted_data = apply_filters( 'wpcf7_posted_data', $posted_data );

		return $this->posted_data;
	}

	private function validate() {
		$tags = $this->contact_form->form_scan_shortcode();

		$result = array(
			'valid' => true,
			'reason' => array(),
			'idref' => array() );

		foreach ( $tags as $tag ) {
			$result = apply_filters( 'wpcf7_validate_' . $tag['type'],
				$result, $tag );
		}

		return apply_filters( 'wpcf7_validate', $result );
	}

	private function accepted() {
		return apply_filters( 'wpcf7_acceptance', true );
	}

	private function spam() {
		$spam = false;

		if ( WPCF7_VERIFY_NONCE && ! $this->verify_nonce() ) {
			$spam = true;
		}

		if ( $this->blacklist_check() ) {
			$spam = true;
		}

		return apply_filters( 'wpcf7_spam', $spam );
	}

	private function verify_nonce() {
		return wpcf7_verify_nonce( $_POST['_wpnonce'], $this->contact_form->id );
	}

	private function blacklist_check() {
		$target = wpcf7_array_flatten( $this->posted_data );
		$target[] = $_SERVER['REMOTE_ADDR'];
		$target[] = $_SERVER['HTTP_USER_AGENT'];

		$target = implode( "\n", $target );

		return wpcf7_blacklist_check( $target );
	}
}

?>