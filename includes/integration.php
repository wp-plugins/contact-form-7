<?php

class WPCF7_Integration {

	private static $instance;

	private $services = array();
	private $categories = array();

	private function __construct() {}

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function add_service( $name, $args = '' ) {
		$name = sanitize_key( $name );

		if ( empty( $name ) || isset( $this->services[$name] ) ) {
			return false;
		}

		$args = wp_parse_args( $args, array(
			'title' => ucwords( $name ),
			'cats' => array(),
			'link' => '',
			'callback' => '',
			'active' => false ) );

		$this->services[$name] = $args;
	}

	public function add_category( $name, $title ) {
		$name = sanitize_key( $name );

		if ( empty( $name ) || isset( $this->categories[$name] ) ) {
			return false;
		}

		$this->categories[$name] = $title;
	}

	public function service_exists( $name ) {
		return isset( $this->services[$name] );
	}

	public function list_services() {
		$services = (array) $this->services;

		if ( isset( $_GET['service'] ) ) {
			$services = array_intersect_key( $services,
				array( $_GET['service'] => '' ) );
		}

		foreach ( $services as $name => $service ) {
			$cats = array_intersect_key( $this->categories,
				array_flip( $service['cats'] ) );
?>
<div class="card<?php echo $service['active'] ? ' active' : ''; ?>" id="<?php echo esc_attr( $name ); ?>">
<h3 class="title"><?php echo esc_html( $service['title'] ); ?></h3>
<div class="infobox">
<?php echo esc_html( implode( ', ', $cats ) ); ?>
<br />
<?php echo make_clickable( $service['link'] ); ?>
</div>
<br class="clear" />

<div class="inside">
<?php
			if ( is_callable( $service['callback'] ) ) {
				call_user_func( $service['callback'] );
			}
?>
</div>
</div>
<?php
		}
	}

}
