<?php
/**
** Module for GetScorecard integration
** http://www.getscorecard.com/
**/

class WPCF7_GetScorecard extends WPCF7_Service {

	const APP_URL = 'https://app.getscorecard.com';

	private static $instance;

	private function __construct() {}

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function get_endpoint_url( $ep = '' ) {
		if ( empty( $ep ) ) {
			return self::APP_URL;
		} else {
			return path_join( self::APP_URL, $ep );
		}
	}

	private function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wpcf7-integration', false );
		$url = add_query_arg( array( 'service' => 'getscorecard' ), $url );

		if ( ! empty( $args) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	public function get_access_token() {
		return get_transient( 'wpcf7_getscorecard_access_token' );
	}

	public function delete_access_token() {
		return delete_transient( 'wpcf7_getscorecard_access_token' );
	}

	public function get_title() {
		return __( 'GetScorecard', 'contact-form-7' );
	}

	public function is_connected() {
		return (bool) $this->get_access_token();
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
		if ( 'login_callback' == $action ) {
			check_admin_referer( 'wpcf7-getscorecard-login-callback' );

			if ( isset( $_GET['error'] ) ) {
				$redirect_to = $this->menu_page_url( array(
					'action' => 'login', 'message' => 'login_failed' ) );

				wp_safe_redirect( $redirect_to );
				exit();
			}

			$client_id = isset( $_GET['client_id'] )
				? trim( $_GET['client_id'] ) : '';
			$client_secret = isset( $_GET['client_secret'] )
				? trim( $_GET['client_secret'] ) : '';
			$user_id = isset( $_GET['user_id'] )
				? trim( $_GET['user_id'] ) : '';

			if ( '' === $client_id || '' === $client_secret || '' === $user_id ) {
				$redirect_to = $this->menu_page_url( array(
					'action' => 'login', 'message' => 'invalid_client_data' ) );

				wp_safe_redirect( $redirect_to );
				exit();
			}

			$option = (array) get_option( 'wpcf7_getscorecard' );

			$option = array_merge( $option, array(
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'user_id' => $user_id ) );

			update_option( 'wpcf7_getscorecard', $option, false );

			$oauth_redirect_url = wp_nonce_url(
				$this->menu_page_url( 'action=oauth_redirect' ),
				'wpcf7-getscorecard-oauth-redirect' );

			$redirect_to = $this->get_endpoint_url( 'api/public/oauth/authorize' );
			$redirect_to = add_query_arg( array(
				'response_type' => 'code',
				'client_id' => $client_id,
				'redirect_uri' => urlencode( $oauth_redirect_url ),
				'state' => '',
				'request_type' => 'contact-form-7' ), $redirect_to );

			wp_redirect( $redirect_to );
			exit();
		}

		if ( 'disconnect' == $action ) {
			check_admin_referer( 'wpcf7-disconnect-getscorecard' );
			$this->delete_access_token();

			$redirect_to = $this->menu_page_url(
				array( 'message' => 'disconnected' ) );

			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

	public function admin_notice() {
		if ( empty( $_REQUEST['message'] ) ) {
			return;
		}

		if ( 'login_failed' == $_REQUEST['message'] ) {
			echo sprintf(
				'<div class="error"><p><strong>%1$s</strong>: %2$s</p></div>',
				esc_html( __( "ERROR", 'contact-form-7' ) ),
				esc_html( __( "Invalid e-mail, or the password is incorrect.", 'contact-form-7' ) ) );
		}

		if ( 'invalid_client_data' == $_REQUEST['message'] ) {
			echo sprintf(
				'<div class="error"><p><strong>%1$s</strong>: %2$s</p></div>',
				esc_html( __( "ERROR", 'contact-form-7' ) ),
				esc_html( __( "Invalid client data.", 'contact-form-7' ) ) );
		}

		if ( 'disconnected' == $_REQUEST['message'] ) {
			echo sprintf( '<div class="updated"><p>%s</p></div>',
				esc_html( __( 'Disconnected from GetScorecard.', 'contact-form-7' ) ) );
		}
	}

	public function display( $action = '' ) {
		if ( $this->is_connected() ) {
			$this->display_disconnect();
			return;
		} elseif ( 'login' == $action ) {
			$this->display_login();
			return;
		}

?>
<p><?php echo esc_html( __( "If you already have a GetScorecard account, sign in to GetScorecard.", 'contact-form-7' ) ); ?></p>

<p><?php echo sprintf( '<a href="%1$s" class="button button-primary">%2$s</a>', esc_url( $this->menu_page_url( 'action=login' ) ), esc_html( __( 'Sign In', 'contact-form-7' ) ) ); ?></p>

<p><?php echo esc_html( __( "If you don't have a GetScorecard account, sign up today.", 'contact-form-7' ) ); ?></p>

<p><strong><?php echo sprintf( '<a href="%1$s">%2$s</a>', esc_url( $this->get_endpoint_url( 'register.php?registerType=contact-form-7' ) ), esc_html( __( 'Sign Up', 'contact-form-7' ) ) ); ?></strong></p>
<?php
	}

	private function display_login() {
		$login_callback_url = wp_nonce_url(
			$this->menu_page_url( 'action=login_callback' ),
			'wpcf7-getscorecard-login-callback' );

		$oauth_redirect_url = wp_nonce_url(
			$this->menu_page_url( 'action=oauth_redirect' ),
			'wpcf7-getscorecard-oauth-redirect' );

?>
<form method="post" action="<?php echo esc_url( $this->get_endpoint_url( 'login-process.php' ) ); ?>">
<input type="hidden" name="plugin_signIn" value="1" />
<input type="hidden" name="plugin_type" value="contact-form-7">
<input type="hidden" name="callback_uri" value="<?php echo esc_url( $login_callback_url ); ?>" />
<input type="hidden" name="oauth_redirect_uri" value="<?php echo esc_url( $oauth_redirect_url ); ?>" />

<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="getscorecard-login-email"><?php echo esc_html( __( 'E-mail', 'contact-form-7' ) ); ?></label></th>
	<td><input type="email" aria-required="true" value="" id="getscorecard-login-email" name="email" class="regular-text ltr" /></td>
</tr>
<tr>
	<th scope="row"><label for="getscorecard-login-password"><?php echo esc_html( __( 'Password', 'contact-form-7' ) ); ?></label></th>
	<td><input type="password" aria-required="true" value="" id="getscorecard-login-password" name="password" class="regular-text" /></td>
</tr>
</tbody>
</table>

<p class="submit"><input type="submit" class="button button-primary" value="<?php echo esc_attr( __( 'Sign In', 'contact-form-7' ) ); ?>" name="submit" /></p>
</form>
<?php
	}

	private function display_disconnect() {
?>
<form method="post" action="<?php echo esc_url( menu_page_url( 'wpcf7-integration', false ) ); ?>">
	<?php wp_nonce_field( 'wpcf7-disconnect-getscorecard' ); ?>
	<input type="hidden" name="service" value="getscorecard" />
	<input type="hidden" name="action" value="disconnect" />

	<p class="submit"><input type="submit" name="disconnect_getscorecard" class="button" value="<?php echo esc_attr( __( 'Disconnect from GetScorecard', 'contact-form-7' ) ); ?>" <?php echo "onclick=\"if (confirm('" . esc_js( __( "Are you sure you want to disconnect from GetScorecard?\n  'Cancel' to stop, 'OK' to disconnect.", 'contact-form-7' ) ) . "')) {return true;} return false;\""; ?> /></p>
</form>
<?php
	}

	public function add_person( $data ) {
		return $this->request( 'people', $data );
	}

	private function request( $resource, $data = '' ) {
		if ( ! $this->is_connected() ) {
			return;
		}

		$data = wp_parse_args( $data, array() );
		$data = json_encode( $data );

		$url = $this->get_endpoint_url( path_join( 'api/public', $resource) );

		return wp_safe_remote_post( $url, array(
			'headers' => array(
				'Content-Type' => 'application/vnd.getscorecard.v1+json',
				'Accept' => 'application/vnd.getscorecard.v1+json',
				'Authorization' => sprintf( 'Bearer %s', $this->get_access_token() ),
				'X-Getscorecard-Client-Type' => 'contact-form-7',
				'X-Getscorecard-Client-Version' => WPCF7_VERSION ),
			'body' => $data ) );
	}
}

add_action( 'wpcf7_init', 'wpcf7_getscorecard_register_service' );

function wpcf7_getscorecard_register_service() {
//	if ( wpcf7_is_localhost() ) {
//		return;
//	}

	$integration = WPCF7_Integration::get_instance();

	$categories = array(
		'crm' => __( 'CRM', 'contact-form-7' ),
		'sales_management' => __( 'Sales Management', 'contact-form-7' ) );

	foreach ( $categories as $name => $category ) {
		$integration->add_category( $name, $category );
	}

	$services = array(
		'getscorecard' => WPCF7_GetScorecard::get_instance() );

	foreach ( $services as $name => $service ) {
		$integration->add_service( $name, $service );
	}
}

add_action( 'wpcf7_submit', 'wpcf7_getscorecard_submit' );

function wpcf7_getscorecard_submit( $contact_form, $result ) {
	if ( ! in_array( $result['status'], array( 'mail_sent', 'mail_failed' ) )
	|| $result['demo_mode'] ) {
		return;
	}

	$service = WPCF7_GetScorecard::get_instance();

	if ( $service->is_connected() ) {
		$submission = WPCF7_Submission::get_instance();

		if ( $submission && $posted_data = $submission->get_posted_data() ) {
			$service->add_person( $posted_data );
		}
	}
}
