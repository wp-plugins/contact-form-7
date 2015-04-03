<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

?><div class="wrap">

<h2><?php
	if ( $post->initial() ) {
		echo esc_html( __( 'Add New Contact Form', 'contact-form-7' ) );
	} else {
		echo esc_html( __( 'Edit Contact Form', 'contact-form-7' ) );

		echo ' <a href="' . esc_url( menu_page_url( 'wpcf7-new', false ) ) . '" class="add-new-h2">' . esc_html( __( 'Add New', 'contact-form-7' ) ) . '</a>';
	}
?></h2>

<?php // do_action( 'wpcf7_admin_notices' ); ?>

<?php
if ( $post ) :

	if ( current_user_can( 'wpcf7_edit_contact_form', $post_id ) ) {
		$disabled = '';
	} else {
		$disabled = ' disabled="disabled"';
	}
?>

<form method="post" action="<?php echo esc_url( add_query_arg( array( 'post' => $post_id ), menu_page_url( 'wpcf7', false ) ) ); ?>" id="wpcf7-admin-form-element"<?php do_action( 'wpcf7_post_edit_form_tag' ); ?>>
<?php
	if ( current_user_can( 'wpcf7_edit_contact_form', $post_id ) ) {
		wp_nonce_field( 'wpcf7-save-contact-form_' . $post_id );
	}
?>
<input type="hidden" id="post_ID" name="post_ID" value="<?php echo (int) $post_id; ?>" />
<input type="hidden" id="wpcf7-locale" name="wpcf7-locale" value="<?php echo esc_attr( $post->locale ); ?>" />
<input type="hidden" id="hiddenaction" name="action" value="save" />

<div id="poststuff">
<div id="post-body" class="metabox-holder columns-2">
<div id="post-body-content">
<div id="titlediv">
<div id="titlewrap">
	<label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo esc_html( __( 'Enter title here', 'contact-form-7' ) ); ?></label>
	<input type="text" name="post_title" size="30" value="<?php echo esc_attr( $post->title() ); ?>" id="title" spellcheck="true" autocomplete="off" />
</div><!-- #titlewrap -->

<div class="inside">
<?php
	if ( ! $post->initial() ) :
?>
	<p class="description">
	<label for="wpcf7-shortcode">
	<?php echo esc_html( __( "Copy this shortcode and paste it into your post, page, or text widget content:", 'contact-form-7' ) ); ?>
	<input type="text" id="wpcf7-shortcode" onfocus="this.select();" readonly="readonly" class="wp-ui-text-highlight code shortcode" value="<?php echo esc_attr( $post->shortcode() ); ?>" />
	</label>
	</p>
<?php
		if ( $old_shortcode = $post->shortcode( array( 'use_old_format' => true ) ) ) :
?>
	<p class="description">
	<label for="wpcf7-shortcode-old">
	<?php echo esc_html( __( "You can also use this old-style shortcode:", 'contact-form-7' ) ); ?>
	<input type="text" id="wpcf7-shortcode-old" onfocus="this.select();" readonly="readonly" class="wp-ui-text-highlight code shortcode" value="<?php echo esc_attr( $old_shortcode ); ?>" />
	</label>
	</p>
<?php
		endif;
	endif;
?>
</div>
</div><!-- #titlediv -->
</div><!-- #post-body-content -->

<div id="postbox-container-1" class="postbox-container">
<?php if ( current_user_can( 'wpcf7_edit_contact_form', $post_id ) ) : ?>
<div id="submitdiv" class="postbox">
<div class="inside">
<div class="submitbox" id="submitpost">

<div id="minor-publishing-actions">
<?php
	if ( ! $post->initial() ) :
		$copy_nonce = wp_create_nonce( 'wpcf7-copy-contact-form_' . $post_id );
?>
	<input type="submit" name="wpcf7-copy" class="copy button" value="<?php echo esc_attr( __( 'Duplicate', 'contact-form-7' ) ); ?>" <?php echo "onclick=\"this.form._wpnonce.value = '$copy_nonce'; this.form.action.value = 'copy'; return true;\""; ?> />
<?php endif; ?>
</div><!-- #minor-publishing-actions -->

<div id="major-publishing-actions">

<?php
	if ( ! $post->initial() ) :
		$delete_nonce = wp_create_nonce( 'wpcf7-delete-contact-form_' . $post_id );
?>
<div id="delete-action">
	<input type="submit" name="wpcf7-delete" class="delete submitdelete" value="<?php echo esc_attr( __( 'Delete', 'contact-form-7' ) ); ?>" <?php echo "onclick=\"if (confirm('" . esc_js( __( "You are about to delete this contact form.\n  'Cancel' to stop, 'OK' to delete.", 'contact-form-7' ) ) . "')) {this.form._wpnonce.value = '$delete_nonce'; this.form.action.value = 'delete'; return true;} return false;\""; ?> />
</div><!-- #delete-action -->
<?php endif; ?>

<div class="save-contact-form textright">
<input type="submit" class="button-primary" name="wpcf7-save" value="<?php echo esc_attr( __( 'Save', 'contact-form-7' ) ); ?>" />
</div>
</div><!-- #major-publishing-actions -->
</div><!-- #submitpost -->
</div>
</div><!-- #submitdiv -->
<?php endif; ?>
</div><!-- #postbox-container-1 -->

<div id="postbox-container-2" class="postbox-container">

<ul id="contact-form-edit-tabs">
<li><a href="#form-sortables"><?php echo esc_html( __( 'Form', 'contact-form-7' ) ); ?></a></li>
<li><a href="#mail-sortables"><?php echo esc_html( __( 'Mail', 'contact-form-7' ) ); ?></a></li>
<li><a href="#mail_2-sortables"><?php echo esc_html( __( 'Mail (2)', 'contact-form-7' ) ); ?></a></li>
<li><a href="#messages-sortables"><?php echo esc_html( __( 'Messages', 'contact-form-7' ) ); ?></a></li>
<li><a href="#additional_settings-sortables"><?php echo esc_html( __( 'Additional Settings', 'contact-form-7' ) ); ?></a></li>
</ul>

<?php 

do_meta_boxes( null, 'form', $post );

do_meta_boxes( null, 'mail', $post );

do_meta_boxes( null, 'mail_2', $post );

do_meta_boxes( null, 'messages', $post );

do_meta_boxes( null, 'additional_settings', $post );

?>

</div><!-- #postbox-container-2 -->

<?php if ( current_user_can( 'wpcf7_edit_contact_form', $post_id ) ) : ?>
<p class="submit"><input type="submit" class="button-primary" name="wpcf7-save" value="<?php echo esc_attr( __( 'Save', 'contact-form-7' ) ); ?>" /></p>
<?php endif; ?>

</div><!-- #post-body -->
<br class="clear" />
</div><!-- #poststuff -->
</form>

<?php endif; ?>

</div><!-- .wrap -->

<?php do_action( 'wpcf7_admin_footer', $post ); ?>
