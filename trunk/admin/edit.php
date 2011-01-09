<?php

/* No table warning */
if ( ! wpcf7_table_exists() ) {
	if ( current_user_can( 'activate_plugins' ) ) {
		$create_table_link_url = wpcf7_admin_url( array( 'wpcf7-create-table' => 1 ) );
		$create_table_link_url = wp_nonce_url( $create_table_link_url, 'wpcf7-create-table' );
		$message = sprintf(
			__( '<strong>The database table for Contact Form 7 does not exist.</strong> You must <a href="%s">create the table</a> for it to work.', 'wpcf7' ),
			$create_table_link_url );
	} else {
		$message = __( "<strong>The database table for Contact Form 7 does not exist.</strong>", 'wpcf7' );
	}
?>
	<div class="wrap">
	<?php screen_icon( 'edit-pages' ); ?>
	<h2><?php echo esc_html( __( 'Contact Form 7', 'wpcf7' ) ); ?></h2>
	<div id="message" class="updated fade">
	<p><?php echo $message; ?></p>
	</div>
	</div>
<?php
	return;
}

?><div class="wrap wpcf7">

<?php screen_icon( 'edit-pages' ); ?>

<h2><?php echo esc_html( __( 'Contact Form 7', 'wpcf7' ) ); ?></h2>

<?php do_action_ref_array( 'wpcf7_admin_before_subsubsub', array( &$cf ) ); ?>

<ul class="subsubsub">
<?php
$first = array_shift( $contact_forms );
if ( ! is_null( $first ) ) : ?>
<li><a href="<?php echo wpcf7_admin_url( array( 'contactform' => $first->id ) ); ?>"<?php if ( $first->id == $current ) echo ' class="current"'; ?>><?php echo esc_html( $first->title ); ?></a></li>
<?php endif;
foreach ( $contact_forms as $v ) : ?>
<li>| <a href="<?php echo wpcf7_admin_url( array( 'contactform' => $v->id ) ); ?>"<?php if ( $v->id == $current ) echo ' class="current"'; ?>><?php echo esc_html( $v->title ); ?></a></li>
<?php endforeach; ?>

<?php if ( wpcf7_admin_has_edit_cap() ) : ?>
<li class="addnew"><a class="thickbox<?php if ( $unsaved ) echo ' current'; ?>" href="#TB_inline?height=300&width=400&inlineId=wpcf7-lang-select-modal"><?php echo esc_html( __( 'Add new', 'wpcf7' ) ); ?></a></li>
<?php endif; ?>
</ul>

<br class="clear" />

<?php if ( $cf ) : ?>
<?php $disabled = ( wpcf7_admin_has_edit_cap() ) ? '' : ' disabled="disabled"'; ?>

<form method="post" action="<?php echo wpcf7_admin_url( array( 'contactform' => $current ) ); ?>" id="wpcf7-admin-form-element">
	<?php if ( wpcf7_admin_has_edit_cap() ) wp_nonce_field( 'wpcf7-save_' . $current ); ?>
	<input type="hidden" id="wpcf7-id" name="wpcf7-id" value="<?php echo $current; ?>" />

	<table class="widefat">
	<tbody>
	<tr>
	<td scope="col">
	<div style="position: relative;">
		<input type="text" id="wpcf7-title" name="wpcf7-title" size="40" value="<?php echo esc_attr( $cf->title ); ?>"<?php echo $disabled; ?> />

		<?php if ( ! $unsaved ) : ?>
		<p class="tagcode">
			<?php echo esc_html( __( "Copy this code and paste it into your post, page or text widget content.", 'wpcf7' ) ); ?><br />

			<input type="text" id="contact-form-anchor-text" onfocus="this.select();" readonly="readonly" />
		</p>
		<?php endif; ?>

		<?php if ( wpcf7_admin_has_edit_cap() ) : ?>
		<div class="save-contact-form">
			<input type="submit" class="button button-highlighted" name="wpcf7-save" value="<?php echo esc_attr( __( 'Save', 'wpcf7' ) ); ?>" />
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
	</td>
	</tr>
	</tbody>
	</table>

<?php do_action_ref_array( 'wpcf7_admin_after_general_settings', array( &$cf ) ); ?>

<?php if ( wpcf7_admin_has_edit_cap() ) : ?>

	<table class="widefat" style="margin-top: 1em;">
	<thead><tr><th scope="col" colspan="2"><?php echo esc_html( __( 'Form', 'wpcf7' ) ); ?></th></tr></thead>

	<tbody>
	<tr>

	<td scope="col" style="width: 50%;">
	<div><textarea id="wpcf7-form" name="wpcf7-form" cols="100" rows="20"><?php echo esc_html( $cf->form ); ?></textarea></div>
	</td>

	<td scope="col" style="width: 50%;">
		<div id="taggenerator"></div>
	</td>

	</tr>
	</tbody>
	</table>

<?php endif; ?>

<?php do_action_ref_array( 'wpcf7_admin_after_form', array( &$cf ) ); ?>

<?php if ( wpcf7_admin_has_edit_cap() ) : ?>

	<table class="widefat" style="margin-top: 1em;">
	<thead><tr><th scope="col" colspan="2"><?php echo esc_html( __( 'Mail', 'wpcf7' ) ); ?></th></tr></thead>

	<tbody>
	<tr>
	<td scope="col" style="width: 50%;">

	<div class="mail-field">
	<label for="wpcf7-mail-recipient"><?php echo esc_html( __( 'To:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-recipient" name="wpcf7-mail-recipient" class="wide" size="70" value="<?php echo esc_attr( $cf->mail['recipient'] ); ?>" />
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-sender"><?php echo esc_html( __( 'From:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-sender" name="wpcf7-mail-sender" class="wide" size="70" value="<?php echo esc_attr( $cf->mail['sender'] ); ?>" />
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-subject"><?php echo esc_html( __( 'Subject:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-subject" name="wpcf7-mail-subject" class="wide" size="70" value="<?php echo esc_attr( $cf->mail['subject'] ); ?>" />
	</div>

	<div class="pseudo-hr"></div>

	<div class="mail-field">
	<label for="wpcf7-mail-additional-headers"><?php echo esc_html( __( 'Additional headers:', 'wpcf7' ) ); ?></label><br />
	<textarea id="wpcf7-mail-additional-headers" name="wpcf7-mail-additional-headers" cols="100" rows="2"><?php echo esc_html( $cf->mail['additional_headers'] ); ?></textarea>
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-attachments"><?php echo esc_html( __( 'File attachments:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-attachments" name="wpcf7-mail-attachments" class="wide" size="70" value="<?php echo esc_attr( $cf->mail['attachments'] ); ?>" />
	</div>

	<div class="pseudo-hr"></div>

	<div class="mail-field">
	<input type="checkbox" id="wpcf7-mail-use-html" name="wpcf7-mail-use-html" value="1"<?php echo ( $cf->mail['use_html'] ) ? ' checked="checked"' : ''; ?> />
	<label for="wpcf7-mail-use-html"><?php echo esc_html( __( 'Use HTML content type', 'wpcf7' ) ); ?></label>
	</div>

	</td>
	<td scope="col" style="width: 50%;">

	<div class="mail-field">
	<label for="wpcf7-mail-body"><?php echo esc_html( __( 'Message body:', 'wpcf7' ) ); ?></label><br />
	<textarea id="wpcf7-mail-body" name="wpcf7-mail-body" cols="100" rows="16"><?php echo esc_html( $cf->mail['body'] ); ?></textarea>
	</div>

	</td>
	</tr>
	</tbody>
	</table>

<?php endif; ?>

<?php do_action_ref_array( 'wpcf7_admin_after_mail', array( &$cf ) ); ?>

<?php if ( wpcf7_admin_has_edit_cap() ) : ?>

	<table class="widefat" style="margin-top: 1em;">
	<thead><tr><th scope="col" colspan="2"><?php echo esc_html( __( 'Mail (2)', 'wpcf7' ) ); ?></th></tr></thead>

	<tbody>
	<tr>
	<td scope="col" colspan="2">
	<input type="checkbox" id="wpcf7-mail-2-active" name="wpcf7-mail-2-active" value="1"<?php echo ( $cf->mail_2['active'] ) ? ' checked="checked"' : ''; ?> />
	<label for="wpcf7-mail-2-active"><?php echo esc_html( __( 'Use mail (2)', 'wpcf7' ) ); ?></label>
	</td>
	</tr>

	<tr id="mail-2-fields">
	<td scope="col" style="width: 50%;">

	<div class="mail-field">
	<label for="wpcf7-mail-2-recipient"><?php echo esc_html( __( 'To:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-2-recipient" name="wpcf7-mail-2-recipient" class="wide" size="70" value="<?php echo esc_attr( $cf->mail_2['recipient'] ); ?>" />
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-2-sender"><?php echo esc_html( __( 'From:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-2-sender" name="wpcf7-mail-2-sender" class="wide" size="70" value="<?php echo esc_attr( $cf->mail_2['sender'] ); ?>" />
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-2-subject"><?php echo esc_html( __( 'Subject:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-2-subject" name="wpcf7-mail-2-subject" class="wide" size="70" value="<?php echo esc_attr( $cf->mail_2['subject'] ); ?>" />
	</div>

	<div class="pseudo-hr"></div>

	<div class="mail-field">
	<label for="wpcf7-mail-2-additional-headers"><?php echo esc_html( __( 'Additional headers:', 'wpcf7' ) ); ?></label><br />
	<textarea id="wpcf7-mail-2-additional-headers" name="wpcf7-mail-2-additional-headers" cols="100" rows="2"><?php echo esc_html( $cf->mail_2['additional_headers'] ); ?></textarea>
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-2-attachments"><?php echo esc_html( __( 'File attachments:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-2-attachments" name="wpcf7-mail-2-attachments" class="wide" size="70" value="<?php echo esc_attr( $cf->mail_2['attachments'] ); ?>" />
	</div>

	<div class="pseudo-hr"></div>

	<div class="mail-field">
	<input type="checkbox" id="wpcf7-mail-2-use-html" name="wpcf7-mail-2-use-html" value="1"<?php echo ( $cf->mail_2['use_html'] ) ? ' checked="checked"' : ''; ?> />
	<label for="wpcf7-mail-2-use-html"><?php echo esc_html( __( 'Use HTML content type', 'wpcf7' ) ); ?></label>
	</div>

	</td>
	<td scope="col" style="width: 50%;">

	<div class="mail-field">
	<label for="wpcf7-mail-2-body"><?php echo esc_html( __( 'Message body:', 'wpcf7' ) ); ?></label><br />
	<textarea id="wpcf7-mail-2-body" name="wpcf7-mail-2-body" cols="100" rows="16"><?php echo esc_html( $cf->mail_2['body'] ); ?></textarea>
	</div>

	</td>
	</tr>
	</tbody>
	</table>

<?php endif; ?>

<?php do_action_ref_array( 'wpcf7_admin_after_mail_2', array( &$cf ) ); ?>

<?php if ( wpcf7_admin_has_edit_cap() ) : ?>

	<table class="widefat" style="margin-top: 1em;">
	<thead><tr><th scope="col"><?php echo esc_html( __( 'Messages', 'wpcf7' ) ); ?> <span id="message-fields-toggle-switch"></span></th></tr></thead>

	<tbody>
	<tr>
	<td scope="col">
	<div id="message-fields">

<?php foreach ( wpcf7_messages() as $key => $arr ) :
	$field_name = 'wpcf7-message-' . strtr( $key, '_', '-' );
?>
	<div class="message-field">
	<label for="<?php echo $field_name; ?>"><em># <?php echo esc_html( $arr['description'] ); ?></em></label><br />
	<input type="text" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" class="wide" size="70" value="<?php echo esc_attr( $cf->messages[$key] ); ?>" />
	</div>

<?php endforeach; ?>

	</div>
	</td>
	</tr>
	</tbody>
	</table>

<?php endif; ?>

<?php do_action_ref_array( 'wpcf7_admin_after_messages', array( &$cf ) ); ?>

<?php if ( wpcf7_admin_has_edit_cap() ) : ?>

	<table class="widefat" style="margin-top: 1em;">
	<thead><tr><th scope="col"><?php echo esc_html( __( 'Additional Settings', 'wpcf7' ) ); ?> <span id="additional-settings-fields-toggle-switch"></span></th></tr></thead>

	<tbody>
	<tr>
	<td scope="col">
	<div id="additional-settings-fields">
	<textarea id="wpcf7-additional-settings" name="wpcf7-additional-settings" cols="100" rows="8"><?php echo esc_html( $cf->additional_settings ); ?></textarea>
	</div>
	</td>
	</tr>
	</tbody>
	</table>

<?php endif; ?>

<?php do_action_ref_array( 'wpcf7_admin_after_additional_settings', array( &$cf ) ); ?>

<?php if ( wpcf7_admin_has_edit_cap() ) : ?>

	<table class="widefat" style="margin-top: 1em;">
	<tbody>
	<tr>
	<td scope="col">
	<div class="save-contact-form">
	<input type="submit" class="button button-highlighted" name="wpcf7-save" value="<?php echo esc_attr( __( 'Save', 'wpcf7' ) ); ?>" />
	</div>
	</td>
	</tr>
	</tbody>
	</table>

<?php endif; ?>

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
