<?php

class WPCF7_TagGenerator {

	private static $instance;

	private $panels = array();

	private function __construct() {}

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function add( $name, $title, $elm_id, $callback, $options = array() ) {
		$name = trim( $name );

		if ( '' === $name ) {
			return false;
		}

		$this->panels[$name] = array(
			'title' => $title,
			'content' => $elm_id,
			'options' => $options,
			'callback' => $callback );

		return true;
	}

	public function print_buttons() {
		echo '<span id="tag-generator-list">';

		foreach ( (array) $this->panels as $panel ) {
			echo sprintf(
				'<a href="#TB_inline?width=900&height=500&inlineId=%1$s" class="thickbox button" title="%2$s">%3$s</a>',
				esc_attr( $panel['content'] ),
				esc_attr( sprintf(
					__( 'Form-tag Generator: %s', 'contact-form-7' ),
					$panel['title'] ) ),
				esc_html( $panel['title'] ) );
		}

		echo '</span>';
	}

	public function print_panels( WPCF7_ContactForm $contact_form ) {
		foreach ( (array) $this->panels as $name => $panel ) {
			$callback = $panel['callback'];

			$options = wp_parse_args( $panel['options'], array() );
			$options = array_merge( $options, array(
				'name' => $name,
				'title' => $panel['title'],
				'content' => $panel['content'] ) );

			if ( is_callable( $callback ) ) {
				call_user_func( $callback, $contact_form, $options );
			}
		}
	}

}
