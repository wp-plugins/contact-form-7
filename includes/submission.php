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

	public function submit( $ajax = false ) {
		$this->setup_posted_data();
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

}

?>