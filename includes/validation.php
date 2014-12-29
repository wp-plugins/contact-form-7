<?php

class WPCF7_Validation implements ArrayAccess {
	private $invalid_fields = array();
	private $container = array();

	public function __construct() {
		$this->container = array(
			'valid' => true,
			'reason' => array(),
			'idref' => array() );
	}

	public function invalidate( $name, $message ) {
		if ( empty( $name ) || ! wpcf7_is_name( $name ) ) {
			return;
		}

		if ( ! isset( $this->invalid_fields[$name] ) ) {
			if ( $tags = wpcf7_scan_shortcode( array( 'name' => $name ) ) ) {
				$tag = new WPCF7_Shortcode( $tags[0] );
				$id = $tag->get_id_option();
			}

			if ( empty( $id ) || ! wpcf7_is_name( $id ) ) {
				$id = null;
			}

			$this->invalid_fields[$name] = array(
				'reason' => (string) $message,
				'idref' => $id );
		}
	}

	public function is_valid() {
		return empty( $this->invalid_fields );
	}

	public function get_invalid_fields() {
		return $this->invalid_fields;
	}

	public function offsetSet( $offset, $value ) {
		if ( isset( $this->container[$offset] ) ) {
			$this->container[$offset] = $value;
		}

		if ( 'reason' == $offset && is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$this->invalidate( $k, $v );
			}
		}
	}

	public function offsetGet( $offset ) {
		if ( isset( $this->container[$offset] ) ) {
			return $this->container[$offset];
		}
	}

	public function offsetExists( $offset ) {
		return isset( $this->container[$offset] );
	}

	public function offsetUnset( $offset ) {
	}
}

?>