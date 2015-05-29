<?php
/**
** Module for GetScorecard integration
** http://www.getscorecard.com/
**/

class WPCF7_GetScorecard {
	const AUTH_EP = 'https://app.getscorecard.com/api/public/oauth';
}

add_action( 'wpcf7_load_integration_page', 'wpcf7_getscorecard_add_service' );

function wpcf7_getscorecard_add_service( $integration ) {
	$integration->add_service( 'getscorecard', array(
		'title' => __( 'GetScorecard', 'contact-form-7' ),
		'callback' => 'wpcf7_getscorecard_card',
		'link' => 'http://www.getscorecard.com',
		'cats' => array(
			'crm' => __( 'CRM', 'contact-form-7' ),
			'sales_management' => __( 'Sales Management', 'contact-form-7' ) ) ) );
}

function wpcf7_getscorecard_card() {
?>
<p><?php echo esc_html( __( "If you already have a GetScorecard account, sign in to GetScorecard.", 'contact-form-7' ) ); ?></p>

<p><a href="" class="button button-primary"><?php echo esc_html( __( 'Sign In', 'contact-form-7' ) ); ?></a></p>

<p><?php echo esc_html( __( "If you don't have a GetScorecard account, get started today!", 'contact-form-7' ) ); ?></p>

<p><a href="https://app.getscorecard.com/register.php?registerType=gc_wp_plugin" class="button"><?php echo esc_html( __( 'Register', 'contact-form-7' ) ); ?></a></p>
<?php
}
