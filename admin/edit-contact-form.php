<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) )
	die( '-1' );

?><div class="wrap">

<?php screen_icon(); ?>

<h2><?php
	echo esc_html( __( 'Contact Form 7', 'wpcf7' ) );

	if ( ! $unsaved ) {
		echo ' <a href="#TB_inline?height=300&width=400&inlineId=wpcf7-lang-select-modal" class="add-new-h2 thickbox">' . esc_html( __( 'Add New', 'wpcf7' ) ) . '</a>';
	}
?></h2>

<?php do_action( 'wpcf7_admin_notices' ); ?>

<br class="clear" />

<?php
if ( $post ) :

	if ( current_user_can( 'wpcf7_edit_contact_form', $post_id ) )
		$disabled = '';
	else
		$disabled = ' disabled="disabled"';
?>

<form method="post" action="<?php echo esc_url( add_query_arg( array( 'post' => $post_id ), menu_page_url( 'wpcf7', false ) ) ); ?>" id="wpcf7-admin-form-element"<?php do_action( 'wpcf7_post_edit_form_tag' ); ?>>
	<?php if ( current_user_can( 'wpcf7_edit_contact_form', $post_id ) )
		wp_nonce_field( 'wpcf7-save-contact-form_' . $post_id ); ?>
	<input type="hidden" id="post_ID" name="post_ID" value="<?php echo (int) $post_id; ?>" />
	<input type="hidden" id="wpcf7-id" name="wpcf7-id" value="<?php echo (int) get_post_meta( $post->id, '_old_cf7_unit_id', true ); ?>" />
	<input type="hidden" id="hiddenaction" name="action" value="save" />

	<div id="poststuff" class="metabox-holder">

	<div id="titlediv">
		<input type="text" id="wpcf7-title" name="wpcf7-title" size="40" value="<?php echo esc_attr( $post->title ); ?>"<?php echo $disabled; ?> />

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

		<?php if ( current_user_can( 'wpcf7_edit_contact_form', $post_id ) ) : ?>
		<div class="save-contact-form">
			<input type="submit" class="button-primary" name="wpcf7-save" value="<?php echo esc_attr( __( 'Save', 'wpcf7' ) ); ?>" />
		</div>
		<?php endif; ?>

		<?php if ( current_user_can( 'wpcf7_edit_contact_form', $post_id ) && ! $unsaved ) : ?>
		<div class="actions-link">
			<?php $copy_nonce = wp_create_nonce( 'wpcf7-copy-contact-form_' . $post_id ); ?>
			<input type="submit" name="wpcf7-copy" class="copy" value="<?php echo esc_attr( __( 'Copy', 'wpcf7' ) ); ?>"
			<?php echo "onclick=\"this.form._wpnonce.value = '$copy_nonce'; this.form.action.value = 'copy'; return true;\""; ?> />
			|

			<?php $delete_nonce = wp_create_nonce( 'wpcf7-delete-contact-form_' . $post_id ); ?>
			<input type="submit" name="wpcf7-delete" class="delete" value="<?php echo esc_attr( __( 'Delete', 'wpcf7' ) ); ?>"
			<?php echo "onclick=\"if (confirm('" .
				esc_js( __( "You are about to delete this contact form.\n  'Cancel' to stop, 'OK' to delete.", 'wpcf7' ) ) .
				"')) {this.form._wpnonce.value = '$delete_nonce'; this.form.action.value = 'delete'; return true;} return false;\""; ?> />
		</div>
		<?php endif; ?>
	</div>

<?php

if ( current_user_can( 'wpcf7_edit_contact_form', $post_id ) ) {
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

do_action_ref_array( 'wpcf7_admin_after_general_settings', array( &$post ) );

do_meta_boxes( 'cfseven', 'form', $post );

do_action_ref_array( 'wpcf7_admin_after_form', array( &$post ) );

do_meta_boxes( 'cfseven', 'mail', $post );

do_action_ref_array( 'wpcf7_admin_after_mail', array( &$post ) );

do_meta_boxes( 'cfseven', 'mail_2', $post );

do_action_ref_array( 'wpcf7_admin_after_mail_2', array( &$post ) );

do_meta_boxes( 'cfseven', 'messages', $post );

do_action_ref_array( 'wpcf7_admin_after_messages', array( &$post ) );

do_meta_boxes( 'cfseven', 'additional_settings', $post );

do_action_ref_array( 'wpcf7_admin_after_additional_settings', array( &$post ) );

wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );

?>
	</div>

</form>

<?php endif; ?>

</div>

<?php wpcf7_admin_lang_select_modal(); ?>

<?php do_action_ref_array( 'wpcf7_admin_footer', array( &$post ) ); ?>
