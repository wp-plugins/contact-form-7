<?php
/**
** Module for GetScorecard integration
** http://www.getscorecard.com/
**/

add_action( 'wpcf7_admin_integration_page',
	'wpcf7_getscorecard_integration_page' );

function wpcf7_getscorecard_integration_page() {
?>
<div class="card" id="getscorecard">
	<h3><?php echo esc_html( __( 'GetScorecard', 'contact-form-7' ) ); ?></h3>

	<p><?php echo esc_html( __( "If you already have a GetScorecard account, sign in to GetScorecard.", 'contact-form-7' ) ); ?></p>

	<p><a href="" class="button button-primary"><?php echo esc_html( __( 'Sign In', 'contact-form-7' ) ); ?></a></p>

	<p><?php echo esc_html( __( "If you don't have a GetScorecard account, get started today!", 'contact-form-7' ) ); ?></p>

	<p><a href="https://app.getscorecard.com/register.php?registerType=gc_wp_plugin" class="button"><?php echo esc_html( __( 'Register', 'contact-form-7' ) ); ?></a></p>

</div>
<?php
}
