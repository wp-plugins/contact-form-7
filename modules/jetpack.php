<?php

add_action( 'admin_notices', 'wpcf7_jetpack_admin_notices' );

function wpcf7_jetpack_admin_notices() {
	if ( ! class_exists( 'Jetpack' )
	|| ! Jetpack::is_module( 'contact-form' )
	|| ! in_array( 'contact-form', Jetpack::get_active_modules() ) )
		return;

	$url = 'http://contactform7.com/jetpack-overrides-contact-forms';
?>
<div class="error">
<p><?php echo sprintf( __( '<strong>Jetpack causes trouble to other plugins in certain cases.</strong> <a href="%s" target="_blank">See how you can avoid it.</a>', 'wpcf7' ), $url ); ?></p>
</div>
<?php
}

?>