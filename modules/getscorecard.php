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

	public function get_access_token( $refresh_if_expired = true ) {
		$access_token = get_transient( 'wpcf7_getscorecard_access_token' );

		if ( false !== $access_token ) {
			return $access_token;
		}

		if ( $refresh_if_expired ) {
			return $this->refresh_access_token();
		}

		return false;
	}

	private function set_access_token( $access_token, $expires_in = 3600 ) {
		return set_transient( 'wpcf7_getscorecard_access_token',
			$access_token, absint( $expires_in ) );
	}

	private function delete_access_token() {
		return delete_transient( 'wpcf7_getscorecard_access_token' );
	}

	private function get_option( $name = '' ) {
		$option = get_option( 'wpcf7_getscorecard' );

		if ( false === $option || '' == $name ) {
			return $option;
		} else {
			return isset( $option[$name] ) ? $option[$name] : '';
		}
	}

	private function update_option( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$option = get_option( 'wpcf7_getscorecard' );
		$option = ( false === $option ) ? array() : (array) $option;
		$option = array_merge( $option, $args );

		update_option( 'wpcf7_getscorecard', $option, false );
	}

	private function delete_option() {
		delete_option( 'wpcf7_getscorecard' );
	}

	public function get_title() {
		return __( 'GetScorecard', 'contact-form-7' );
	}

	public function is_connected() {
		return (bool) $this->get_option();
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

			$this->update_option( array(
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'user_id' => $user_id ) );

			$oauth_redirect_url = wp_nonce_url(
				$this->menu_page_url( 'action=oauth_redirect' ),
				'wpcf7-getscorecard-oauth-redirect' );

			$redirect_to = $this->get_endpoint_url( 'api/public/oauth/authorize' );
			$redirect_to = add_query_arg( array(
				'response_type' => 'code',
				'client_id' => $client_id,
				'redirect_uri' => urlencode( $oauth_redirect_url ),
				'request_type' => 'contact-form-7' ), $redirect_to );

			wp_redirect( $redirect_to );
			exit();
		}

		if ( 'oauth_redirect' == $action ) {
			check_admin_referer( 'wpcf7-getscorecard-oauth-redirect' );

			$authorization_code = isset( $_GET['code'] )
				? trim( $_GET['code'] ) : '';

			$this->request_access_token( $authorization_code );

			$redirect_to = $this->menu_page_url(
				array( 'message' => 'auth_success' ) );

			wp_safe_redirect( $redirect_to );
			exit();
		}

		if ( 'disconnect' == $action ) {
			check_admin_referer( 'wpcf7-disconnect-getscorecard' );

			$this->delete_access_token();
			$this->delete_option();

			$redirect_to = $this->menu_page_url(
				array( 'message' => 'disconnected' ) );

			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

	public function admin_notice( $message = '' ) {
		if ( 'login_failed' == $message ) {
			echo sprintf(
				'<div class="error"><p><strong>%1$s</strong>: %2$s</p></div>',
				esc_html( __( "ERROR", 'contact-form-7' ) ),
				esc_html( __( "Invalid e-mail, or the password is incorrect.", 'contact-form-7' ) ) );
		}

		if ( 'invalid_client_data' == $message ) {
			echo sprintf(
				'<div class="error"><p><strong>%1$s</strong>: %2$s</p></div>',
				esc_html( __( "ERROR", 'contact-form-7' ) ),
				esc_html( __( "Invalid client data.", 'contact-form-7' ) ) );
		}

		if ( 'auth_success' == $message ) {
			echo sprintf( '<div class="updated"><p>%s</p></div>',
				esc_html( __( 'Connected to GetScorecard.', 'contact-form-7' ) ) );
		}

		if ( 'disconnected' == $message ) {
			echo sprintf( '<div class="updated"><p>%s</p></div>',
				esc_html( __( 'Disconnected from GetScorecard.', 'contact-form-7' ) ) );
		}
	}

	public function display( $action = '' ) {
		if ( $this->is_connected() ) {
			$this->display_connected();
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

	private function display_connected() {
		$user_info = $this->get_user_info();

		if ( false !== $user_info ) :
?>
<h4><?php echo esc_html( __( 'Your Profile on GetScorecard', 'contact-form-7' ) ); ?></h4>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><?php echo esc_html( __( 'User ID', 'contact-form-7' ) ); ?></th>
	<td><?php echo esc_html( $user_info['id'] ); ?></td>
</tr>
<tr>
	<th scope="row"><?php echo esc_html( __( 'Full Name', 'contact-form-7' ) ); ?></th>
	<td><?php echo esc_html( $user_info['fullname'] ); ?></td>
</tr>
<tr>
	<th scope="row"><?php echo esc_html( __( 'E-mail', 'contact-form-7' ) ); ?></th>
	<td><?php echo esc_html( $user_info['email'] ); ?></td>
</tr>
</tbody>
</table>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=disconnect' ) ); ?>">
	<?php wp_nonce_field( 'wpcf7-disconnect-getscorecard' ); ?>

	<p class="submit"><input type="submit" name="disconnect_getscorecard" class="button" value="<?php echo esc_attr( __( 'Disconnect from GetScorecard', 'contact-form-7' ) ); ?>" <?php echo "onclick=\"if (confirm('" . esc_js( __( "Are you sure you want to disconnect from GetScorecard?\n  'Cancel' to stop, 'OK' to disconnect.", 'contact-form-7' ) ) . "')) {return true;} return false;\""; ?> /></p>
</form>
<?php
	}

	private function request_access_token( $authorization_code ) {
		$url = $this->get_endpoint_url( 'api/public/oauth' );

		$option = $this->get_option();
		$client_id = isset( $option['client_id'] )
			? $option['client_id'] : '';
		$client_secret = isset( $option['client_secret'] )
			? $option['client_secret'] : '';

		$oauth_redirect_url = wp_nonce_url(
			$this->menu_page_url( 'action=oauth_redirect' ),
			'wpcf7-getscorecard-oauth-redirect' );

		$response = wp_safe_remote_post( $url, array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept' => 'application/json',
				'X-Getscorecard-Client-Type' => 'contact-form-7',
				'X-Getscorecard-Client-Version' => WPCF7_VERSION ),
			'body' => array(
				'grant_type' => 'authorization_code',
				'code' => $authorization_code,
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri' => $oauth_redirect_url ) ) );

		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response, true );

		$access_token = isset( $response['access_token'] )
			? $response['access_token'] : '';
		$expires_in = isset( $response['expires_in'] )
			? absint( $response['expires_in'] ) : 3600;

		$this->set_access_token( $access_token, $expires_in );

		$refresh_token = isset( $response['refresh_token'] )
			? $response['refresh_token'] : '';

		$this->update_option( array(
			'refresh_token' => $refresh_token ) );

		return $access_token;
	}

	private function refresh_access_token() {
		$url = $this->get_endpoint_url( 'api/public/oauth' );

		$option = $this->get_option();
		$client_id = isset( $option['client_id'] )
			? $option['client_id'] : '';
		$client_secret = isset( $option['client_secret'] )
			? $option['client_secret'] : '';
		$refresh_token = isset( $option['refresh_token'] )
			? $option['refresh_token'] : '';

		if ( '' === $refresh_token ) {
			return false;
		}

		$response = wp_safe_remote_post( $url, array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept' => 'application/json',
				'X-Getscorecard-Client-Type' => 'contact-form-7',
				'X-Getscorecard-Client-Version' => WPCF7_VERSION ),
			'body' => array(
				'grant_type' => 'refresh_token',
				'refresh_token' => $refresh_token,
				'client_id' => $client_id,
				'client_secret' => $client_secret ) ) );

		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response, true );

		$access_token = isset( $response['access_token'] )
			? $response['access_token'] : '';
		$expires_in = isset( $response['expires_in'] )
			? absint( $response['expires_in'] ) : 3600;

		$this->set_access_token( $access_token, $expires_in );

		$refresh_token = isset( $response['refresh_token'] )
			? $response['refresh_token'] : '';

		if ( '' !== $refresh_token ) {
			$this->update_option( array( 'refresh_token' => $refresh_token ) );
		}

		return $access_token;
	}

	private function get_user_info() {
		$user_id = (int) $this->get_option( 'user_id' );

		if ( empty( $user_id ) ) {
			return false;
		}

		$data = $this->get( sprintf( 'users/%d', $user_id ) );

		if ( empty( $data ) ) {
			return false;
		}

		$data = wp_parse_args( $data[0], array(
			'id' => 0,
			'fullname' => '',
			'email' => '' ) );

		return $data;
	}

	public function add_person( $data ) {
		return $this->post( 'people', $data );
	}

	private function get( $resource ) {
		return $this->request( $resource, '', 'get' );
	}

	private function post( $resource, $data = '' ) {
		return $this->request( $resource, $data, 'post' );
	}

	private function request( $resource, $data = '', $method = 'get' ) {
		$access_token = $this->get_access_token();

		if ( false === $access_token ) {
			return false;
		}

		$data = wp_parse_args( $data, array() );
		$data = json_encode( $data );

		$method = strtolower( $method );

		if ( 'post' != $method ) {
			$method = 'get';
		}

		$url = path_join( 'api/public', $resource );
		$url = $this->get_endpoint_url( $url );

		if ( 'get' == $method ) {
			$response = wp_safe_remote_get( $url, array(
				'headers' => array(
					'Accept' => 'application/vnd.getscorecard.v1+json',
					'Authorization' => sprintf( 'Bearer %s', $access_token ),
					'X-Getscorecard-Client-Type' => 'contact-form-7',
					'X-Getscorecard-Client-Version' => WPCF7_VERSION ) ) );
		} elseif ( 'post' == $method ) {
			$response = wp_safe_remote_post( $url, array(
				'headers' => array(
					'Content-Type' => 'application/vnd.getscorecard.v1+json',
					'Accept' => 'application/vnd.getscorecard.v1+json',
					'Authorization' => sprintf( 'Bearer %s', $access_token ),
					'X-Getscorecard-Client-Type' => 'contact-form-7',
					'X-Getscorecard-Client-Version' => WPCF7_VERSION ),
				'body' => $data ) );
		}

		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response, true );

		return $response;
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
	$getscorecard_able = ( ! $result['demo_mode']
		&& in_array( $result['status'], array( 'mail_sent', 'mail_failed' ) ) );

	$getscorecard_able = apply_filters( 'wpcf7_getscorecard_able',
		$getscorecard_able, $contact_form, $result );

	if ( ! $getscorecard_able ) {
		return;
	}

	$service = WPCF7_GetScorecard::get_instance();

	if ( $service->is_connected() ) {
		$submission = WPCF7_Submission::get_instance();

		if ( $submission && $posted_data = $submission->get_posted_data() ) {
			$fields_no_use = $contact_form->collect_mail_tags(
				array( 'include' => array( 'acceptance', 'captchar', 'quiz' ) ) );

			foreach ( $posted_data as $key => $value ) {
				if ( '_' == substr( $key, 0, 1 )
				|| in_array( $key, $fields_no_use ) ) {
					unset( $posted_data[$key] );
				}
			}

			$person_data = apply_filters( 'wpcf7_getscorecard_person_data',
				$posted_data );

			$service->add_person( $person_data );
		}
	}
}
