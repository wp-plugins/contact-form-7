<?php

class WPCF7_Integration {

	private static $instance;

	private $services = array();

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
?>
<div class="card<?php echo $service['active'] ? ' active' : ''; ?>" id="<?php echo esc_attr( $name ); ?>">
<h3 class="alignleft"><?php echo esc_html( $service['title'] ); ?></h3>
<p class="description alignright">
<?php echo esc_html( implode( ', ', $service['cats'] ) ); ?>
<br />
<?php echo make_clickable( $service['link'] ); ?>
</p>
<br class="clear" />

<?php
			if ( is_callable( $service['callback'] ) ) {
				call_user_func( $service['callback'] );
			}
?>
</div>
<?php
		}
	}

}
