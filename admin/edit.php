<div class="wrap">

<?php screen_icon( 'edit-pages' ); ?>

<h2><?php echo esc_html( __( 'Contact Form 7', 'wpcf7' ) ); ?></h2>

<?php do_action_ref_array( 'wpcf7_admin_before_subsubsub', array( &$cf ) ); ?>

<ul class="subsubsub">
<?php
$first = array_shift( $contact_forms );
if ( ! is_null( $first ) ) : ?>
<li><a href="<?php echo wpcf7_admin_url( array( 'contactform' => $first->ID ) ); ?>"<?php if ( $first->ID == $current ) echo ' class="current"'; ?>><?php echo esc_html( $first->post_title ); ?></a></li>
<?php endif;
foreach ( $contact_forms as $v ) : ?>
<li>| <a href="<?php echo wpcf7_admin_url( array( 'contactform' => $v->ID ) ); ?>"<?php if ( $v->ID == $current ) echo ' class="current"'; ?>><?php echo esc_html( $v->post_title ); ?></a></li>
<?php endforeach; ?>

<?php if ( wpcf7_admin_has_edit_cap() ) : ?>
<li class="addnew"><a class="button thickbox<?php if ( $unsaved ) echo ' current'; ?>" href="#TB_inline?height=300&width=400&inlineId=wpcf7-lang-select-modal"><?php echo esc_html( __( 'Add New', 'wpcf7' ) ); ?></a></li>
<?php endif; ?>
</ul>

<br class="clear" />

<?php if ( $cf ) : ?>
<?php $disabled = ( wpcf7_admin_has_edit_cap() ) ? '' : ' disabled="disabled"'; ?>

<form method="post" action="<?php echo wpcf7_admin_url( array( 'contactform' => $current ) ); ?>" id="wpcf7-admin-form-element"<?php do_action( 'wpcf7_post_edit_form_tag' ); ?>>
	<?php if ( wpcf7_admin_has_edit_cap() ) wp_nonce_field( 'wpcf7-save_' . $current ); ?>
	<input type="hidden" id="post_ID" name="post_ID" value="<?php echo (int) $current; ?>" />
	<input type="hidden" id="wpcf7-id" name="wpcf7-id" value="<?php echo (int) get_post_meta( $cf->id, '_old_cf7_unit_id', true ); ?>" />

	<div id="poststuff" class="metabox-holder">

	<div id="titlediv">
		<input type="text" id="wpcf7-title" name="wpcf7-title" size="40" value="<?php echo esc_attr( $cf->title ); ?>"<?php echo $disabled; ?> />

		<?php if ( ! $unsaved ) : ?>
		<p class="tagcode">
			<?php echo esc_html( __( "Copy this code and paste it into your post, page or text widget content.", 'wpcf7' ) ); ?><br />

			<input type="text" id="contact-form-anchor-text" onfocus="this.select();" readonly="readonly" />
		</p>

		<p class="tagcode" style="display: none;">
			<?php echo esc_html( __( "Old code is also available.", 'wpcf7' ) ); ?><br />

			<input type="text" id="contact-form-anchor-text-old" onfocus="this.select();" readonly="readonly" />
		</p>
		<?php endif; ?>

		<?php if ( wpcf7_admin_has_edit_cap() ) : ?>
		<div class="save-contact-form">
			<input type="submit" class="button" name="wpcf7-save" value="<?php echo esc_attr( __( 'Save', 'wpcf7' ) ); ?>" />
		</div>
		<?php endif; ?>

		<?php if ( wpcf7_admin_has_edit_cap() && ! $unsaved ) : ?>
		<div class="actions-link">
			<?php $copy_nonce = wp_create_nonce( 'wpcf7-copy_' . $current ); ?>
			<input type="submit" name="wpcf7-copy" class="copy" value="<?php echo esc_attr( __( 'Copy', 'wpcf7' ) ); ?>"
			<?php echo "onclick=\"this.form._wpnonce.value = '$copy_nonce'; return true;\""; ?> />
			|

			<?php $delete_nonce = wp_create_nonce( 'wpcf7-delete_' . $current ); ?>
			<input type="submit" name="wpcf7-delete" class="delete" value="<?php echo esc_attr( __( 'Delete', 'wpcf7' ) ); ?>"
			<?php echo "onclick=\"if (confirm('" .
				esc_js( __( "You are about to delete this contact form.\n  'Cancel' to stop, 'OK' to delete.", 'wpcf7' ) ) .
				"')) {this.form._wpnonce.value = '$delete_nonce'; return true;} return false;\""; ?> />
		</div>
		<?php endif; ?>
	</div>

<?php

if ( wpcf7_admin_has_edit_cap() ) {
	add_meta_box( 'formdiv', __( 'Form', 'wpcf7' ),
		'wpcf7_form_meta_box', 'cfseven', 'form', 'core' );

	add_meta_box( 'maildiv', __( 'Mail', 'wpcf7' ),
		'wpcf7_mail_meta_box', 'cfseven', 'mail', 'core' );

	add_meta_box( 'mail2div', __( 'Mail (2)', 'wpcf7' ),
		'wpcf7_mail_meta_box', 'cfseven', 'mail_2', 'core',
		array(
			'id' => 'wpcf7-mail-2',
			'name' => 'mail_2',
			'use' => __( 'Use mail (2)', 'wpcf7' ) ) );

	add_meta_box( 'messagesdiv', __( 'Messages', 'wpcf7' ),
		'wpcf7_messages_meta_box', 'cfseven', 'messages', 'core' );

	add_meta_box( 'additionalsettingsdiv', __( 'Additional Settings', 'wpcf7' ),
		'wpcf7_additional_settings_meta_box', 'cfseven', 'additional_settings', 'core' );
}

do_action_ref_array( 'wpcf7_admin_after_general_settings', array( &$cf ) );

do_meta_boxes( 'cfseven', 'form', $cf );

do_action_ref_array( 'wpcf7_admin_after_form', array( &$cf ) );

do_meta_boxes( 'cfseven', 'mail', $cf );

do_action_ref_array( 'wpcf7_admin_after_mail', array( &$cf ) );

do_meta_boxes( 'cfseven', 'mail_2', $cf );

do_action_ref_array( 'wpcf7_admin_after_mail_2', array( &$cf ) );

do_meta_boxes( 'cfseven', 'messages', $cf );

do_action_ref_array( 'wpcf7_admin_after_messages', array( &$cf ) );

do_meta_boxes( 'cfseven', 'additional_settings', $cf );

do_action_ref_array( 'wpcf7_admin_after_additional_settings', array( &$cf ) );

wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );

?>
	</div>

</form>

<?php endif; ?>

</div>

<div id="wpcf7-lang-select-modal" class="hidden">
<?php
	$available_locales = wpcf7_l10n();
	$default_locale = get_locale();

	if ( ! isset( $available_locales[$default_locale] ) )
		$default_locale = 'en_US';

?>
<h4><?php echo esc_html( sprintf( __( 'Use the default language (%s)', 'wpcf7' ), $available_locales[$default_locale] ) ); ?></h4>
<p><a href="<?php echo wpcf7_admin_url( array( 'contactform' => 'new' ) ); ?>" class="button" /><?php echo esc_html( __( 'Add New', 'wpcf7' ) ); ?></a></p>

<?php unset( $available_locales[$default_locale] ); ?>
<h4><?php echo esc_html( __( 'Or', 'wpcf7' ) ); ?></h4>
<form action="" method="get">
<input type="hidden" name="page" value="wpcf7" />
<input type="hidden" name="contactform" value="new" />
<select name="locale">
<option value="" selected="selected"><?php echo esc_html( __( '(select language)', 'wpcf7' ) ); ?></option>
<?php foreach ( $available_locales as $code => $locale ) : ?>
<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $locale ); ?></option>
<?php endforeach; ?>
</select>
<input type="submit" class="button" value="<?php echo esc_attr( __( 'Add New', 'wpcf7' ) ); ?>" />
</form>
</div>

<?php do_action_ref_array( 'wpcf7_admin_footer', array( &$cf ) ); ?>
