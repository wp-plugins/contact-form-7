<?php

/* Form */

function wpcf7_form_meta_box( $post ) {
?>
<div class="half-left"><textarea id="wpcf7-form" name="wpcf7-form" cols="100" rows="20"><?php echo esc_html( $post->form ); ?></textarea></div>

<div class="half-right"><div id="taggenerator"></div></div>

<br class="clear" />
<?php
}

/* Mail */

function wpcf7_mail_meta_box( $post ) {
?>
<div class="half-left">
	<div class="mail-field">
	<label for="wpcf7-mail-recipient"><?php echo esc_html( __( 'To:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-recipient" name="wpcf7-mail-recipient" class="wide" size="70" value="<?php echo esc_attr( $post->mail['recipient'] ); ?>" />
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-sender"><?php echo esc_html( __( 'From:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-sender" name="wpcf7-mail-sender" class="wide" size="70" value="<?php echo esc_attr( $post->mail['sender'] ); ?>" />
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-subject"><?php echo esc_html( __( 'Subject:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-subject" name="wpcf7-mail-subject" class="wide" size="70" value="<?php echo esc_attr( $post->mail['subject'] ); ?>" />
	</div>

	<div class="pseudo-hr"></div>

	<div class="mail-field">
	<label for="wpcf7-mail-additional-headers"><?php echo esc_html( __( 'Additional headers:', 'wpcf7' ) ); ?></label><br />
	<textarea id="wpcf7-mail-additional-headers" name="wpcf7-mail-additional-headers" cols="100" rows="2"><?php echo esc_html( $post->mail['additional_headers'] ); ?></textarea>
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-attachments"><?php echo esc_html( __( 'File attachments:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-attachments" name="wpcf7-mail-attachments" class="wide" size="70" value="<?php echo esc_attr( $post->mail['attachments'] ); ?>" />
	</div>

	<div class="pseudo-hr"></div>

	<div class="mail-field">
	<input type="checkbox" id="wpcf7-mail-use-html" name="wpcf7-mail-use-html" value="1"<?php echo ( $post->mail['use_html'] ) ? ' checked="checked"' : ''; ?> />
	<label for="wpcf7-mail-use-html"><?php echo esc_html( __( 'Use HTML content type', 'wpcf7' ) ); ?></label>
	</div>
</div>

<div class="half-right">
	<div class="mail-field">
	<label for="wpcf7-mail-body"><?php echo esc_html( __( 'Message body:', 'wpcf7' ) ); ?></label><br />
	<textarea id="wpcf7-mail-body" name="wpcf7-mail-body" cols="100" rows="16"><?php echo esc_html( $post->mail['body'] ); ?></textarea>
	</div>
</div>

<br class="clear" />
<?php
}

/* Mail (2) */

function wpcf7_mail_2_meta_box( $post ) {
?>
<input type="checkbox" id="wpcf7-mail-2-active" name="wpcf7-mail-2-active" value="1"<?php echo ( $post->mail_2['active'] ) ? ' checked="checked"' : ''; ?> />
<label for="wpcf7-mail-2-active"><?php echo esc_html( __( 'Use mail (2)', 'wpcf7' ) ); ?></label>

<br class="clear" />

<div class="half-left">
	<div class="mail-field">
	<label for="wpcf7-mail-2-recipient"><?php echo esc_html( __( 'To:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-2-recipient" name="wpcf7-mail-2-recipient" class="wide" size="70" value="<?php echo esc_attr( $post->mail_2['recipient'] ); ?>" />
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-2-sender"><?php echo esc_html( __( 'From:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-2-sender" name="wpcf7-mail-2-sender" class="wide" size="70" value="<?php echo esc_attr( $post->mail_2['sender'] ); ?>" />
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-2-subject"><?php echo esc_html( __( 'Subject:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-2-subject" name="wpcf7-mail-2-subject" class="wide" size="70" value="<?php echo esc_attr( $post->mail_2['subject'] ); ?>" />
	</div>

	<div class="pseudo-hr"></div>

	<div class="mail-field">
	<label for="wpcf7-mail-2-additional-headers"><?php echo esc_html( __( 'Additional headers:', 'wpcf7' ) ); ?></label><br />
	<textarea id="wpcf7-mail-2-additional-headers" name="wpcf7-mail-2-additional-headers" cols="100" rows="2"><?php echo esc_html( $post->mail_2['additional_headers'] ); ?></textarea>
	</div>

	<div class="mail-field">
	<label for="wpcf7-mail-2-attachments"><?php echo esc_html( __( 'File attachments:', 'wpcf7' ) ); ?></label><br />
	<input type="text" id="wpcf7-mail-2-attachments" name="wpcf7-mail-2-attachments" class="wide" size="70" value="<?php echo esc_attr( $post->mail_2['attachments'] ); ?>" />
	</div>

	<div class="pseudo-hr"></div>

	<div class="mail-field">
	<input type="checkbox" id="wpcf7-mail-2-use-html" name="wpcf7-mail-2-use-html" value="1"<?php echo ( $post->mail_2['use_html'] ) ? ' checked="checked"' : ''; ?> />
	<label for="wpcf7-mail-2-use-html"><?php echo esc_html( __( 'Use HTML content type', 'wpcf7' ) ); ?></label>
	</div>
</div>

<div class="half-right">
	<div class="mail-field">
	<label for="wpcf7-mail-2-body"><?php echo esc_html( __( 'Message body:', 'wpcf7' ) ); ?></label><br />
	<textarea id="wpcf7-mail-2-body" name="wpcf7-mail-2-body" cols="100" rows="16"><?php echo esc_html( $post->mail_2['body'] ); ?></textarea>
	</div>
</div>

<br class="clear" />
<?php
}

function wpcf7_messages_meta_box( $post ) {
	foreach ( wpcf7_messages() as $key => $arr ) :
		$field_name = 'wpcf7-message-' . strtr( $key, '_', '-' );

?>
<div class="message-field">
<label for="<?php echo $field_name; ?>"><em># <?php echo esc_html( $arr['description'] ); ?></em></label><br />
<input type="text" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" class="wide" size="70" value="<?php echo esc_attr( $post->messages[$key] ); ?>" />
</div>
<?php
	endforeach;
}

function wpcf7_additional_settings_meta_box( $post ) {
?>
<textarea id="wpcf7-additional-settings" name="wpcf7-additional-settings" cols="100" rows="8"><?php echo esc_html( $post->additional_settings ); ?></textarea>
<?php
}

?>