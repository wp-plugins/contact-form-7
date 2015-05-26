<?php

class WPCF7_OAuth {

	private $service = '';
	private $authorization_endpoint = '';

	public function __construct( $service ) {
		$this->service = $service;
	}

	public function authorize() {
		set_transient( 'wpcf7_token_' . $this->service,
			'dummy token', 60 * 60 * 24 );
	}

	public function get_access_token() {
		return get_transient( 'wpcf7_token_' . $this->service );
	}

	private function refresh_token() {
	}

}
