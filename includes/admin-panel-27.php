<?php if (isset($updated_message)) : ?>
<div id="message" class="updated fade"><p><strong><?php echo $updated_message; ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
	<h2><?php _e('Contact Form 7', 'wpcf7'); ?></h2>

    <ul class="subsubsub">
        <?php foreach ($contact_forms as $k => $v) : ?>
        <li><a href="<?php echo $base_url . '?page=' . $page . '&contactform=' . $k ?>"<?php if ($k == $current) echo ' class="current"'; ?>>
            <?php echo $v['title']; ?></a> |</li>
        <?php endforeach; ?>

        <?php if ($this->has_edit_cap()) : ?>
        <li class="addnew">
            <a href="<?php echo $base_url . '?page=' . $page . '&contactform=new'; ?>"<?php if ($unsaved) echo ' class="current"'; ?>>
            <?php _e('Add new', 'wpcf7'); ?></a></li>
        <?php endif; ?>
    </ul>
</div>
<?php if (version_compare($wp_version, '2.7-alpha', '>=')) : ?><br class="clear" /><?php endif; ?>
<?php if ($cf) : ?>
<?php $disabled = ($this->has_edit_cap()) ? '' : ' disabled="disabled"'; ?>
<div class="wrap relative">
	<form method="post" action="<?php echo $base_url . '?page=' . $page . '&contactform=' . $current; ?>" id="wpcf7-admin-form-element">
		<?php if ($this->has_edit_cap()) wp_nonce_field('wpcf7-save_' . $current); ?>
		<input type="hidden" id="wpcf7-id" name="wpcf7-id" value="<?php echo $current; ?>" />
		<input type="text" id="wpcf7-title" name="wpcf7-title" size="40" value="<?php echo htmlspecialchars($cf['title']); ?>"<?php echo $disabled; ?> />

		<div class="cfdiv">
			<?php if (! $unsaved) : ?>
			<p class="tagcode">
			<?php _e('Copy and paste this code into your post content.', 'wpcf7'); ?><br />
			<input type="text" id="contact-form-anchor-text" onfocus="this.select();" readonly="readonly" />
			</p>
			<?php endif; ?>

        <?php if ($this->has_edit_cap()) : ?>
			<div class="fieldset" id="form-content-fieldset"><div class="legend"><?php _e('Form', 'wpcf7'); ?></div>
				<textarea id="wpcf7-form" name="wpcf7-form" cols="100" rows="16"><?php echo htmlspecialchars($cf['form']); ?></textarea>
			</div>

			<div class="fieldset"><div class="legend"><?php _e('Mail', 'wpcf7'); ?></div>
				<div class="mail-field">
					<label for="wpcf7-mail-recipient"><?php _e('To:', 'wpcf7'); ?></label><br />
					<input type="text" id="wpcf7-mail-recipient" name="wpcf7-mail-recipient" class="wide" size="70" value="<?php echo htmlspecialchars($cf['mail']['recipient']); ?>" />
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-sender"><?php _e('From:', 'wpcf7'); ?></label><br />
					<input type="text" id="wpcf7-mail-sender" name="wpcf7-mail-sender" class="wide" size="70" value="<?php echo htmlspecialchars($cf['mail']['sender']); ?>" />
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-subject"><?php _e('Subject:', 'wpcf7'); ?></label><br />
					<input type="text" id="wpcf7-mail-subject" name="wpcf7-mail-subject" class="wide" size="70" value="<?php echo htmlspecialchars($cf['mail']['subject']); ?>" />
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-body"><?php _e('Message body:', 'wpcf7'); ?></label><br />
					<textarea id="wpcf7-mail-body" name="wpcf7-mail-body" cols="100" rows="16"><?php echo htmlspecialchars($cf['mail']['body']); ?></textarea>
				</div>
			</div>

			<div class="fieldset"><div class="legend"><?php _e('Mail (2)', 'wpcf7'); ?></div>
				<input type="checkbox" id="wpcf7-mail-2-active" name="wpcf7-mail-2-active" value="1"<?php echo ($cf['mail_2']['active']) ? ' checked="checked"' : ''; ?> />
				<label for="wpcf7-mail-2-active"><?php _e('Use mail (2)', 'wpcf7'); ?></label>
                
                    <div id="mail-2-fields">
				<div class="mail-field">
					<label for="wpcf7-mail-2-recipient"><?php _e('To:', 'wpcf7'); ?></label><br />
					<input type="text" id="wpcf7-mail-2-recipient" name="wpcf7-mail-2-recipient" class="wide" size="70" value="<?php echo htmlspecialchars($cf['mail_2']['recipient']); ?>" />
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-2-sender"><?php _e('From:', 'wpcf7'); ?></label><br />
					<input type="text" id="wpcf7-mail-2-sender" name="wpcf7-mail-2-sender" class="wide" size="70" value="<?php echo htmlspecialchars($cf['mail_2']['sender']); ?>" />
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-2-subject"><?php _e('Subject:', 'wpcf7'); ?></label><br />
					<input type="text" id="wpcf7-mail-2-subject" name="wpcf7-mail-2-subject" class="wide" size="70" value="<?php echo htmlspecialchars($cf['mail_2']['subject']); ?>" />
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-2-body"><?php _e('Message body:', 'wpcf7'); ?></label><br />
					<textarea id="wpcf7-mail-2-body" name="wpcf7-mail-2-body" cols="100" rows="16"><?php echo htmlspecialchars($cf['mail_2']['body']); ?></textarea>
				</div>
                    </div>
			</div>

                <div class="fieldset">
                    <div class="legend">
                        <?php _e('Messages', 'wpcf7'); ?>
                        <span id="message-fields-toggle-switch"></span>
                    </div>
                    
                    <div id="message-fields">
                    <div class="message-field">
                        <label for="wpcf7-message-mail-sent-ok"><em># <?php _e("Sender's message was sent successfully", 'wpcf7'); ?></em></label><br />
                        <input type="text" id="wpcf7-message-mail-sent-ok" name="wpcf7-message-mail-sent-ok" class="wide" size="70" value="<?php echo htmlspecialchars($cf['messages']['mail_sent_ok']); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-mail-sent-ng"><em># <?php _e("Sender's message was failed to send", 'wpcf7'); ?></em></label><br />
                        <input type="text" id="wpcf7-message-mail-sent-ng" name="wpcf7-message-mail-sent-ng" class="wide" size="70" value="<?php echo htmlspecialchars($cf['messages']['mail_sent_ng']); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-akismet-says-spam"><em># <?php _e("Akismet judged the sending activity as spamming", 'wpcf7'); ?></em></label><br />
                        <input type="text" id="wpcf7-message-akismet-says-spam" name="wpcf7-message-akismet-says-spam" class="wide" size="70" value="<?php echo htmlspecialchars($cf['messages']['akismet_says_spam']); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-validation-error"><em># <?php _e("Validation errors occurred", 'wpcf7'); ?></em></label><br />
                        <input type="text" id="wpcf7-message-validation-error" name="wpcf7-message-validation-error" class="wide" size="70" value="<?php echo htmlspecialchars($cf['messages']['validation_error']); ?>" />
                    </div>
                    <div class="message-field" style="margin-top: 1em;">
                        <label for="wpcf7-message-invalid-required"><em># <?php _e("There is a field that sender is needed to fill in", 'wpcf7'); ?></em></label><br />
                        <input type="text" id="wpcf7-message-invalid-required" name="wpcf7-message-invalid-required" class="wide" size="70" value="<?php echo htmlspecialchars($cf['messages']['invalid_required']); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-invalid-email"><em># <?php _e("Email address that sender entered is invalid", 'wpcf7'); ?></em></label><br />
                        <input type="text" id="wpcf7-message-invalid-email" name="wpcf7-message-invalid-email" class="wide" size="70" value="<?php echo htmlspecialchars($cf['messages']['invalid_email']); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-accept-terms"><em># <?php _e("There is a field of term that sender is needed to accept", 'wpcf7'); ?></em></label><br />
                        <input type="text" id="wpcf7-message-accept-terms" name="wpcf7-message-accept-terms" class="wide" size="70" value="<?php echo htmlspecialchars($cf['messages']['accept_terms']); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-captcha-not-match"><em># <?php _e("The code that sender entered does not match the CAPTCHA", 'wpcf7'); ?></em></label><br />
                        <input type="text" id="wpcf7-message-captcha-not-match" name="wpcf7-message-captcha-not-match" class="wide" size="70" value="<?php echo htmlspecialchars($cf['messages']['captcha_not_match']); ?>" />
                    </div>
                    </div>
                </div>

			<input type="hidden" id="wpcf7-options-recipient" name="wpcf7-options-recipient" value="<?php echo htmlspecialchars($cf['options']['recipient']); ?>" />

			<div>
				<input type="submit" class="button button-highlighted" name="wpcf7-save" value="<?php _e('Save', 'wpcf7'); ?>" />
			</div>
        <?php endif; ?>
		</div>
			
		<?php if ($this->has_edit_cap() && ! $unsaved) : ?>
		<div class="delete-link"><?php $delete_nonce = wp_create_nonce('wpcf7-delete_' . $current); ?>
			<input type="submit" name="wpcf7-delete" value="<?php _e('Delete this contact form', 'wpcf7'); ?>"
				<?php echo "onclick=\"if (confirm('" . js_escape(__("You are about to delete this contact form.\n  'Cancel' to stop, 'OK' to delete.", 'wpcf7')) . "')) {this.form._wpnonce.value = '$delete_nonce'; return true;} return false;\""; ?> />
		</div>
		<?php endif; ?>
	</form>
</div>
<?php endif; ?>

<?php if (version_compare($wp_version, '2.7-alpha', '>=')) : ?><br class="clear" /><?php endif; ?>