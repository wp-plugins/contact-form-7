<?php

add_action( 'wpcf7_enqueue_scripts', 'wpcf7_recaptcha_enqueue_scripts' );

function wpcf7_recaptcha_enqueue_scripts() {
	wp_enqueue_script( 'google-recaptcha',
		'https://www.google.com/recaptcha/api.js',
		array(), '2.0', true );
}

add_action( 'wpcf7_init', 'wpcf7_recaptcha_add_shortcode_recaptcha' );

function wpcf7_recaptcha_add_shortcode_recaptcha() {
	wpcf7_add_shortcode( 'recaptcha', 'wpcf7_recaptcha_shortcode_handler' );
}

function wpcf7_recaptcha_shortcode_handler( $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	$atts = array();
	$atts['data-sitekey'] = $tag->get_option( 'sitekey', '', true );
	$atts['data-theme'] = $tag->get_option( 'theme', '(dark|light)', true );
	$atts['data-size'] = $tag->get_option( 'size', '(compact|normal)', true );
	$atts['class'] = $tag->get_class_option(
		wpcf7_form_controls_class( $tag->type, 'g-recaptcha' ) );
	$atts['id'] = $tag->get_id_option();

	$html = sprintf( '<div %1$s></div>', wpcf7_format_atts( $atts ) );
	$html .= wpcf7_recaptcha_for_noscript(
		array( 'sitekey' => $atts['data-sitekey'] ) );

	return $html;
}

function wpcf7_recaptcha_for_noscript( $args = '' ) {
	$args = wp_parse_args( $args, array(
		'sitekey' => '' ) );

	$url = add_query_arg( 'k', $args['sitekey'],
		'https://www.google.com/recaptcha/api/fallback' );

	ob_start();
?>

<noscript>
  <div style="width: 302px; height: 422px;">
    <div style="width: 302px; height: 422px; position: relative;">
      <div style="width: 302px; height: 422px; position: absolute;">
        <iframe src="<?php echo esc_url( $url ); ?>" frameborder="0" scrolling="no" style="width: 302px; height:422px; border-style: none;">
        </iframe>
      </div>
      <div style="width: 300px; height: 60px; border-style: none; bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px; background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px;">
        <textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px; height: 40px; border: 1px solid #c1c1c1; margin: 10px 25px; padding: 0px; resize: none;">
        </textarea>
      </div>
    </div>
  </div>
</noscript>
<?php
	return ob_get_clean();
}

add_filter( 'wpcf7_spam', 'wpcf7_recaptcha_check_with_google', 9 );

function wpcf7_recaptcha_check_with_google( $spam ) {
	if ( $spam ) {
		return $spam;
	}

	$contact_form = wpcf7_get_current_contact_form();

	if ( ! $contact_form ) {
		return $spam;
	}

	$tags = $contact_form->form_scan_shortcode( array( 'type' => 'recaptcha' ) );
	$tag = array_shift( $tags );

	if ( ! $tag ) {
		return $spam;
	}

	$tag = new WPCF7_Shortcode( $tag );
	$secret = $tag->get_option( 'secret', '', true );

	$url = 'https://www.google.com/recaptcha/api/siteverify';

	$response = wp_safe_remote_post( $url, array(
		'body' => array(
			'secret' => $secret,
			'response' => isset( $_POST['g-recaptcha-response'] )
				? $_POST['g-recaptcha-response'] : '',
			'remoteip' => $_SERVER['REMOTE_ADDR'] ) ) );

	if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
		return $spam;
	}

	$response = wp_remote_retrieve_body( $response );
	$response = json_decode( $response, true );

	$spam = isset( $response['success'] ) && false == $response['success'];
	return $spam;
}

add_action( 'wpcf7_admin_init', 'wpcf7_add_tag_generator_recaptcha', 45 );

function wpcf7_add_tag_generator_recaptcha() {
	$tag_generator = WPCF7_TagGenerator::get_instance();
	$tag_generator->add( 'recaptcha', __( 'reCAPTCHA', 'contact-form-7' ),
		'wpcf7_tag_generator_recaptcha', array( 'nameless' => 1 ) );
}

function wpcf7_tag_generator_recaptcha( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );

	$description = __( "Generate a form-tag for a reCAPTCHA widget. For more details, see %s.", 'contact-form-7' );

	$desc_link = wpcf7_link( __( 'http://contactform7.com/recaptcha/', 'contact-form-7' ), __( 'reCAPTCHA', 'contact-form-7' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-sitekey' ); ?>"><?php echo esc_html( __( 'Site key', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="sitekey" class="large-text option code" id="<?php echo esc_attr( $args['content'] . '-sitekey' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-secret' ); ?>"><?php echo esc_html( __( 'Secret key', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="secret" class="large-text option code" id="<?php echo esc_attr( $args['content'] . '-secret' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><?php echo esc_html( __( 'Theme', 'contact-form-7' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Theme', 'contact-form-7' ) ); ?></legend>
		<label for="<?php echo esc_attr( $args['content'] . '-theme-light' ); ?>"><input type="radio" name="theme" class="option" id="<?php echo esc_attr( $args['content'] . '-theme-light' ); ?>" value="light" checked="checked" /> <?php echo esc_html( __( 'Light', 'contact-form-7' ) ); ?></label>
		<br />
		<label for="<?php echo esc_attr( $args['content'] . '-theme-dark' ); ?>"><input type="radio" name="theme" class="option" id="<?php echo esc_attr( $args['content'] . '-theme-dark' ); ?>" value="dark" /> <?php echo esc_html( __( 'Dark', 'contact-form-7' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><?php echo esc_html( __( 'Size', 'contact-form-7' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Size', 'contact-form-7' ) ); ?></legend>
		<label for="<?php echo esc_attr( $args['content'] . '-size-normal' ); ?>"><input type="radio" name="size" class="option" id="<?php echo esc_attr( $args['content'] . '-size-normal' ); ?>" value="normal" checked="checked" /> <?php echo esc_html( __( 'Normal', 'contact-form-7' ) ); ?></label>
		<br />
		<label for="<?php echo esc_attr( $args['content'] . '-size-compact' ); ?>"><input type="radio" name="size" class="option" id="<?php echo esc_attr( $args['content'] . '-size-compact' ); ?>" value="compact" /> <?php echo esc_html( __( 'Compact', 'contact-form-7' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
	</tr>

</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="recaptcha" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
	</div>
</div>
<?php
}
