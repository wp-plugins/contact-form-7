<?php
/**
** Module for GetScorecard integration
** http://www.getscorecard.com/
**/

class WPCF7_GetScorecard extends WPCF7_Service {
	const AUTH_EP = 'https://app.getscorecard.com/api/public/oauth';

	public static function get_access_token() {
		return get_transient( 'wpcf7_getscorecard_access_token' );
	}

	public static function delete_access_token() {
		return delete_transient( 'wpcf7_getscorecard_access_token' );
	}

	public function get_title() {
		return __( 'GetScorecard', 'contact-form-7' );
	}

	public function is_connected() {
		return (bool) self::get_access_token();
	}

	public function is_active() {
		return $this->is_connected();
	}

	public function get_categories() {
		return array( 'crm', 'sales_management' );
	}

	public function link() {
		echo sprintf( '<a href="%1$s">%2$s</a>',
			'http://www.getscorecard.com?source=contact-form-7',
			'getscorecard.com' );
	}

	public function load( $action = '' ) {
		if ( 'disconnect' == $action ) {
			check_admin_referer( 'wpcf7-disconnect-getscorecard' );
			self::delete_access_token();

			$redirect_to = add_query_arg(
				array(
					'service' => 'getscorecard',
					'message' => 'disconnected' ),
				menu_page_url( 'wpcf7-integration', false ) );

			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

	public function display() {
		if ( $this->is_connected() ) {
			wpcf7_getscorecard_card_disconnect();
		} else {
			wpcf7_getscorecard_card_connect();
		}
	}

	public function admin_notice() {
	}
}

add_action( 'wpcf7_admin_notices', 'wpcf7_getscorecard_admin_notices' );

function wpcf7_getscorecard_admin_notices( $page ) {
	if ( 'integration' != $page || empty( $_REQUEST['message'] ) ) {
		return;
	}

	if ( ! isset( $_GET['service'] ) || 'getscorecard' != $_GET['service'] ) {
		return;
	}

	if ( 'disconnected' == $_REQUEST['message'] ) {
		$updated_message = esc_html( __( 'Disconnected from GetScorecard.', 'contact-form-7' ) );
	}

	if ( empty( $updated_message ) ) {
		return;
	}

?>
<div id="message" class="updated"><p><?php echo $updated_message; ?></p></div>
<?php
}

function wpcf7_getscorecard_card_disconnect() {
?>
<form method="post" action="<?php echo esc_url( menu_page_url( 'wpcf7-integration', false ) ); ?>">
<?php wp_nonce_field( 'wpcf7-disconnect-getscorecard' ); ?>
<input type="hidden" name="service" value="getscorecard" />
<input type="hidden" name="action" value="disconnect" />

<p class="submit"><input type="submit" name="disconnect_getscorecard" class="button" value="<?php echo esc_attr( __( 'Disconnect from GetScorecard', 'contact-form-7' ) ); ?>" <?php echo "onclick=\"if (confirm('" . esc_js( __( "Are you sure you want to disconnect from GetScorecard?\n  'Cancel' to stop, 'OK' to disconnect.", 'contact-form-7' ) ) . "')) {return true;} return false;\""; ?> /></p>
</form>
<?php
}

function wpcf7_getscorecard_card_connect() {
?>
<p><?php echo esc_html( __( "If you already have a GetScorecard account, sign in to GetScorecard.", 'contact-form-7' ) ); ?></p>

<p><a href="" class="button button-primary"><?php echo esc_html( __( 'Sign In', 'contact-form-7' ) ); ?></a></p>

<p><?php echo esc_html( __( "If you don't have a GetScorecard account, get started today!", 'contact-form-7' ) ); ?></p>

<p><a href="https://app.getscorecard.com/register.php?registerType=gc_wp_plugin" class="button"><?php echo esc_html( __( 'Register', 'contact-form-7' ) ); ?></a></p>
<?php
}
