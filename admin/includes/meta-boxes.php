<?php

/* Form */

function wpcf7_form_meta_box( $post ) {
?>
<textarea id="wpcf7-form" name="wpcf7-form" cols="100" rows="24" class="large-text code"><?php echo esc_textarea( $post->prop( 'form' ) ); ?></textarea>
<?php
}

/* Mail */

function wpcf7_mail_meta_box( $post, $box ) {
	$args = isset( $box['args'] ) && is_array( $box['args'] )
		? $box['args'] : array();

	$args = wp_parse_args( $args, array(
		'id' => 'wpcf7-mail',
		'name' => 'mail',
		'use' => null ) );

	$id = esc_attr( $args['id'] );

	$mail = wp_parse_args( $post->prop( $args['name'] ), array(
		'active' => false, 'recipient' => '', 'sender' => '',
		'subject' => '', 'body' => '', 'additional_headers' => '',
		'attachments' => '', 'use_html' => false, 'exclude_blank' => false ) );

	if ( ! empty( $args['use'] ) ) :
?>
<label for="<?php echo $id; ?>-active"><input type="checkbox" id="<?php echo $id; ?>-active" name="<?php echo $id; ?>-active" value="1"<?php echo ( $mail['active'] ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( $args['use'] ); ?></label>
<p class="description"><?php echo esc_html( __( "Mail (2) is an additional mail template often used as an autoresponder.", 'contact-form-7' ) ); ?></p>
<?php endif; ?>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-recipient"><?php echo esc_html( __( 'To', 'contact-form-7' ) ); ?></label>
	</th>
	<td>
		<input type="text" id="<?php echo $id; ?>-recipient" name="<?php echo $id; ?>-recipient" class="large-text code" size="70" value="<?php echo esc_attr( $mail['recipient'] ); ?>" />
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-sender"><?php echo esc_html( __( 'From', 'contact-form-7' ) ); ?></label>
	</th>
	<td>
		<input type="text" id="<?php echo $id; ?>-sender" name="<?php echo $id; ?>-sender" class="large-text code" size="70" value="<?php echo esc_attr( $mail['sender'] ); ?>" />
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-subject"><?php echo esc_html( __( 'Subject', 'contact-form-7' ) ); ?></label>
	</th>
	<td>
		<input type="text" id="<?php echo $id; ?>-subject" name="<?php echo $id; ?>-subject" class="large-text code" size="70" value="<?php echo esc_attr( $mail['subject'] ); ?>" />
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-additional-headers"><?php echo esc_html( __( 'Additional Headers', 'contact-form-7' ) ); ?></label>
	</th>
	<td>
		<textarea id="<?php echo $id; ?>-additional-headers" name="<?php echo $id; ?>-additional-headers" cols="100" rows="4" class="large-text code"><?php echo esc_textarea( $mail['additional_headers'] ); ?></textarea>
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-body"><?php echo esc_html( __( 'Message Body', 'contact-form-7' ) ); ?></label>
	</th>
	<td>
		<textarea id="<?php echo $id; ?>-body" name="<?php echo $id; ?>-body" cols="100" rows="18" class="large-text code"><?php echo esc_textarea( $mail['body'] ); ?></textarea>

		<p><label for="<?php echo $id; ?>-exclude-blank"><input type="checkbox" id="<?php echo $id; ?>-exclude-blank" name="<?php echo $id; ?>-exclude-blank" value="1"<?php echo ( ! empty( $mail['exclude_blank'] ) ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( __( 'Exclude lines with blank mail-tags from output', 'contact-form-7' ) ); ?></label></p>

		<p><label for="<?php echo $id; ?>-use-html"><input type="checkbox" id="<?php echo $id; ?>-use-html" name="<?php echo $id; ?>-use-html" value="1"<?php echo ( $mail['use_html'] ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( __( 'Use HTML content type', 'contact-form-7' ) ); ?></label></p>
	</td>
	</tr>

	<tr>
	<th scope="row">
		<label for="<?php echo $id; ?>-attachments"><?php echo esc_html( __( 'File Attachments', 'contact-form-7' ) ); ?></label>
	</th>
	<td>
		<textarea id="<?php echo $id; ?>-attachments" name="<?php echo $id; ?>-attachments" cols="100" rows="4" class="large-text code"><?php echo esc_textarea( $mail['attachments'] ); ?></textarea>
	</td>
	</tr>
</tbody>
</table>
<?php
}

function wpcf7_messages_meta_box( $post ) {
	$messages = wpcf7_messages();
?>
<fieldset>
<legend class="screen-reader-text"><?php echo esc_html( __( 'Edit messages used for the following situations.', 'contact-form-7' ) ); ?></legend>
<p><?php echo esc_html( __( 'Edit messages used for the following situations.', 'contact-form-7' ) ); ?></p>
<?php

	foreach ( $messages as $key => $arr ) {
		$field_name = 'wpcf7-message-' . strtr( $key, '_', '-' );
?>
<p class="description">
<label for="<?php echo $field_name; ?>"><?php echo esc_html( $arr['description'] ); ?><br />
<input type="text" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" class="large-text" size="70" value="<?php echo esc_attr( $post->message( $key, false ) ); ?>" />
</label>
</p>
<?php
	}
?>
</fieldset>
<?php
}

function wpcf7_additional_settings_meta_box( $post ) {
?>
<textarea id="wpcf7-additional-settings" name="wpcf7-additional-settings" cols="100" rows="8" class="large-text"><?php echo esc_textarea( $post->prop( 'additional_settings' ) ); ?></textarea>
<?php
}

?>