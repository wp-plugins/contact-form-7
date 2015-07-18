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

	public function icon() {
		$icon = sprintf(
			'<img src="%1$s" alt="%2$s" width="%3$d" height="%4$d" class="icon" />',
			wpcf7_plugin_url( 'images/service-icons/getscorecard-72x72.png' ),
			esc_attr( __( 'GetScorecard Logo', 'contact-form-7' ) ),
			36, 36 );
		echo $icon;
	}

	public function link() {
		echo sprintf( '<a href="%1$s">%2$s</a>',
			'http://www.getscorecard.com?source=contact-form-7',
			'getscorecard.com' );
	}

	/**
	 * Returns an endpoint URL on the GetScorecard API server.
	 * Example: https://app.getscorecard.com/api/public/oauth
	 *
	 * @param string $ep The path to be appended to the base URL.
	 * @return string The endpoint URL.
	 */
	private function get_endpoint_url( $ep = '' ) {
		if ( empty( $ep ) ) {
			return self::APP_URL;
		} else {
			return path_join( self::APP_URL, $ep );
		}
	}

	/**
	 * Returns a menu page URL on the client WordPress site.
	 * Example: http://example.com/wp-admin/admin.php?page=wpcf7-integration&service=getscorecard&action=login
	 *
	 * @param string|array $args Additional queries to be appended to the URL.
	 * @return string The menu page URL.
	 */
	private function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wpcf7-integration', false );
		$url = add_query_arg( array( 'service' => 'getscorecard' ), $url );

		if ( ! empty( $args) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	/**
	 * Retrieves the option data from wp_options database table.
	 * The data can include these items:
	 * client_id - The client identifier issued to the client
	 * client_secret - The client secret
	 * refresh_token - The refresh token used by OAuth authorization
	 *
	 * @param string $name Optional. One of the items listed above.
	 * @return array|string An array includes all option items.
	 *         Or the value of the item specified by $name.
	 */
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

	/**
	 * Retrieves an OAuth access token from the transient data
	 * in wp-options database table.
	 *
	 * @param bool $refresh_if_expired Optional. Whether to attempt to
	 *        refresh access token when it is expired. Default true.
	 * @return string|false A string of access token.
	 *         False if the retrieval failed.
	 */
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

	private function set_access_token( $access_token, $expires_in = 0 ) {
		return set_transient( 'wpcf7_getscorecard_access_token',
			$access_token, absint( $expires_in ) );
	}

	private function delete_access_token() {
		return delete_transient( 'wpcf7_getscorecard_access_token' );
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
			? absint( $response['expires_in'] ) : 0;

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
			? absint( $response['expires_in'] ) : 0;

		$this->set_access_token( $access_token, $expires_in );

		$refresh_token = isset( $response['refresh_token'] )
			? $response['refresh_token'] : '';

		if ( '' !== $refresh_token ) {
			$this->update_option( array( 'refresh_token' => $refresh_token ) );
		}

		return $access_token;
	}

	/**
	 * Sends an API request to the GetScorecard server.
	 *
	 * @param string $resource The resource name.
	 * @param string|array $data The data submitted with a POST request.
	 * @param string $method HTTP method ('get' or 'post').
	 * @return array|false A result array. False if the request failed.
	 */
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

	private function get( $resource ) {
		return $this->request( $resource, '', 'get' );
	}

	private function post( $resource, $data = '' ) {
		return $this->request( $resource, $data, 'post' );
	}

	private function get_user_info() {
		$data = $this->get( 'custom/1?action=contactForm7getAuthorizedUserInfo' );

		if ( empty( $data ) || empty( $data['user_info'] ) ) {
			return false;
		}

		$data = wp_parse_args( $data['user_info'], array(
			'id' => 0,
			'fullname' => '',
			'email' => '' ) );

		return $data;
	}

	public function add_person( $data ) {
		return $this->post( 'custom?action=contactForm7AddRecord', $data );
	}

	public function load( $action = '' ) {
		if ( 'setup_client' == $action && 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'wpcf7-getscorecard-setup-client' );

			$client_id = isset( $_POST['client-id'] )
				? trim( $_POST['client-id'] ) : '';
			$client_secret = isset( $_POST['client-secret'] )
				? trim( $_POST['client-secret'] ) : '';

			if ( '' === $client_id || '' === $client_secret ) {
				$redirect_to = $this->menu_page_url( array(
					'action' => 'setup_client', 'message' => 'invalid_client_data' ) );

				wp_safe_redirect( $redirect_to );
				exit();
			}

			$this->update_option( array(
				'client_id' => $client_id,
				'client_secret' => $client_secret ) );

			$oauth_redirect_url = wp_nonce_url(
				$this->menu_page_url( 'action=oauth_redirect' ),
				'wpcf7-getscorecard-oauth-redirect' );

			$redirect_to = $this->get_endpoint_url( 'api/public/oauth/authorize' );
			$redirect_to = add_query_arg( array(
				'response_type' => 'code',
				'request_type' => 'contact-form-7',
				'client_id' => $client_id,
				'state' => substr( md5( time() ), 0, 8 ),
				'redirect_uri' => urlencode( $oauth_redirect_url ) ), $redirect_to );

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
		} elseif ( 'setup_client' == $action ) {
			$this->display_setup_client();
			return;
		}

		$setup_client_url = $this->menu_page_url( 'action=setup_client' );

		$signup_url = $this->get_endpoint_url( 'register.php' );
		$signup_url = add_query_arg( array(
			'registerType' => 'contact-form-7',
			'redirect_uri' => urlencode( $setup_client_url ) ), $signup_url );

?>
<p><?php echo esc_html( __( "GetScorecard is a full CRM and Sales Management system to help you manage your business or website contacts. It will capture all the information from your contact forms, help you track conversations with your contacts. Its features include Notes, Tasks, Email Tracking, Sales and Pipeline Management and Calendar.", 'contact-form-7' ) ); ?></p>

<p><?php echo esc_html( __( "It is free for up to 2 users with no Credit Card required.", 'contact-form-7' ) ); ?></p>

<p><strong><?php echo sprintf( '<a href="%1$s" class="button button-primary">%2$s</a>', esc_url( $signup_url ), esc_html( __( 'Sign Up Free', 'contact-form-7' ) ) ); ?></strong></p>

<p><?php echo esc_html( __( "If you already have a GetScorecard account, let's move on to the next step.", 'contact-form-7' ) ); ?></p>

<p><?php echo sprintf( '<a href="%1$s">%2$s</a>', esc_url( $setup_client_url ), esc_html( __( 'Connect to GetScorecard', 'contact-form-7' ) ) ); ?></p>
<?php
	}

	private function display_setup_client() {
		$setup_client_url = $this->menu_page_url( 'action=setup_client' );

		$manage_clients_url =
			$this->get_endpoint_url( 'integration/cf7/index.php' );
		$manage_clients_url = add_query_arg( array(
			'action' => 'login_redirect',
			'redirect_uri' => urlencode( $setup_client_url ) ), $manage_clients_url );

?>
<p><?php echo esc_html( __( "Client ID and Client Secret are unique strings identifying your account on GetScorecard.", 'contact-form-7' ) ); ?> <?php echo sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( $manage_clients_url ), esc_html( __( "Get them from the GetScorecard dashboard.", 'contact-form-7' ) ) ); ?></p>

<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=setup_client' ) ); ?>">
<?php wp_nonce_field( 'wpcf7-getscorecard-setup-client' ); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="client-id"><?php echo esc_html( __( 'Client ID', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" aria-required="true" value="" id="client-id" name="client-id" class="regular-text code" /></td>
</tr>
<tr>
	<th scope="row"><label for="client-secret"><?php echo esc_html( __( 'Client Secret', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" aria-required="true" value="" id="client-secret" name="client-secret" class="regular-text code" /></td>
</tr>
</tbody>
</table>

<p class="submit"><input type="submit" class="button button-primary" value="<?php echo esc_attr( __( 'Connect to GetScorecard', 'contact-form-7' ) ); ?>" name="submit" /></p>
</form>
<?php
	}

	private function display_connected() {
		$client_info = wp_parse_args( $this->get_option(),
			array( 'client_id' => '', 'client_secret' => '' ) );
		$user_info = $this->get_user_info();

?>
<h4><?php echo esc_html( __( 'Your Profile on GetScorecard', 'contact-form-7' ) ); ?></h4>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><?php echo esc_html( __( 'Client ID', 'contact-form-7' ) ); ?></th>
	<td class="code"><?php echo esc_html( $client_info['client_id'] ); ?></td>
</tr>
<tr>
	<th scope="row"><?php echo esc_html( __( 'Client Secret', 'contact-form-7' ) ); ?></th>
	<td class="code"><?php echo esc_html( wpcf7_mask_password( $client_info['client_secret'] ) ); ?></td>
</tr>
<tr>
	<th scope="row"><?php echo esc_html( __( 'User ID', 'contact-form-7' ) ); ?></th>
	<td class="code"><?php echo esc_html( $user_info['id'] ); ?></td>
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

<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=disconnect' ) ); ?>">
	<?php wp_nonce_field( 'wpcf7-disconnect-getscorecard' ); ?>

	<p class="submit"><input type="submit" name="disconnect_getscorecard" class="button" value="<?php echo esc_attr( __( 'Disconnect from GetScorecard', 'contact-form-7' ) ); ?>" <?php echo "onclick=\"if (confirm('" . esc_js( __( "Are you sure you want to disconnect from GetScorecard?\n  'Cancel' to stop, 'OK' to disconnect.", 'contact-form-7' ) ) . "')) {return true;} return false;\""; ?> /></p>
</form>
<?php
	}
}

add_action( 'wpcf7_init', 'wpcf7_getscorecard_register_service' );

function wpcf7_getscorecard_register_service() {
	if ( wpcf7_is_localhost() ) {
		return;
	}

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

add_action( 'wpcf7_submit', 'wpcf7_getscorecard_submit', 10, 2 );

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

			$defaults = array( 'title' => '', 'firstname' => '', 'lastname' => '',
				'address' => '', 'state' => '', 'zipcode' => '', 'country' => '' );

			foreach ( $defaults as $dkey => $dval ) {
				if ( ! isset( $person_data[$dkey] )
				&& isset( $person_data['your-' . $dkey] ) ) {
					$defaults[$dkey] = $person_data['your-' . $dkey];
					unset( $person_data['your-' . $dkey] );
				} elseif ( 'firstname' == $dkey
				&& isset( $person_data['your-name'] ) ) {
					$defaults[$dkey] = $person_data['your-name'];
				}
			}

			$person_data = wp_parse_args( $person_data, $defaults );

			$service->add_person( $person_data );
		}
	}
}
