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
    foreach ( (array) $this->panels as $panel ) {
      echo sprintf(
        '<a href="#TB_inline?width=600&height=550&inlineId=%1$s" class="thickbox button">%2$s</a>',
        $panel['content'], $panel['title'] );
    }
  }

  public function print_panels( WPCF7_ContactForm $contact_form ) {
    foreach ( (array) $this->panels as $panel ) {
      $callback = $panel['callback'];

      if ( is_callable( $callback ) ) {
        call_user_func( $callback, $contact_form );
      }
    }
  }

}
