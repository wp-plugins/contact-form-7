<div class="wrap wpcf7">
    <?php screen_icon( 'tools' ); ?>
    <h2><?php _e( 'Contact Form 7', 'wpcf7' ); ?></h2>
    <?php wpcf7_donation_link(); ?>
    <?php if ( isset( $updated_message ) ) : ?>
    <div id="message" class="updated fade"><p><strong><?php echo $updated_message; ?></strong></p></div>
    <?php endif; ?>

    <ul class="subsubsub">
        <?php foreach ( $contact_forms as $v ) : ?>
        <li><a href="<?php echo $base_url . '?page=' . $page . '&contactform=' . $v->id ?>"<?php if ( $v->id == $current ) echo ' class="current"'; ?>>
            <?php echo $v->title; ?></a> |</li>
        <?php endforeach; ?>

        <?php if ( wpcf7_admin_has_edit_cap() ) : ?>
        <li class="addnew">
            <a href="<?php echo $base_url . '?page=' . $page . '&contactform=new'; ?>"<?php if ( $unsaved ) echo ' class="current"'; ?>>
            <?php _e( 'Add new', 'wpcf7' ); ?></a></li>
        <?php endif; ?>
    </ul>
    
    <br class="clear" />

<?php if ( $cf ) : ?>
<?php $disabled = ( wpcf7_admin_has_edit_cap() ) ? '' : ' disabled="disabled"'; ?>

<form method="post" action="<?php echo $base_url . '?page=' . $page . '&contactform=' . $current; ?>" id="wpcf7-admin-form-element">
<?php if ( wpcf7_admin_has_edit_cap() ) wp_nonce_field( 'wpcf7-save_' . $current ); ?>
<input type="hidden" id="wpcf7-id" name="wpcf7-id" value="<?php echo $current; ?>" />

    <table class="widefat">
        <tbody>
            <tr>
                <td scope="col">
                    <div style="position: relative;">
                        <input type="text" id="wpcf7-title" name="wpcf7-title" size="40" value="<?php echo htmlspecialchars( $cf->title ); ?>"<?php echo $disabled; ?> />

                        <?php if ( ! $unsaved ) : ?>
                        <p class="tagcode">
                            <?php _e( 'Copy this code and paste it into your post, page or text widget content.', 'wpcf7' ); ?><br />
                            <input type="text" id="contact-form-anchor-text" onfocus="this.select();" readonly="readonly" />
                        </p>
                        <?php endif; ?>

                        <?php if ( wpcf7_admin_has_edit_cap() ) : ?>
                        <div class="save-contact-form">
                            <input type="submit" class="button button-highlighted" name="wpcf7-save" value="<?php _e( 'Save', 'wpcf7' ); ?>" />
                        </div>
                        <?php endif; ?>

                        <?php if ( wpcf7_admin_has_edit_cap() && ! $unsaved ) : ?>
                        <div class="actions-link">
                            <?php $copy_nonce = wp_create_nonce( 'wpcf7-copy_' . $current ); ?>
                            <input type="submit" name="wpcf7-copy" class="copy" value="<?php _e( 'Copy', 'wpcf7' ); ?>"
                                <?php echo "onclick=\"this.form._wpnonce.value = '$copy_nonce'; return true;\""; ?> />
                            |
                            <?php $delete_nonce = wp_create_nonce( 'wpcf7-delete_' . $current ); ?>
                            <input type="submit" name="wpcf7-delete" class="delete" value="<?php _e( 'Delete', 'wpcf7' ); ?>"
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

    <?php if ( wpcf7_admin_has_edit_cap() ) : ?>
    
    <table class="widefat" style="margin-top: 1em;">
        <thead>
            <tr>
                <th scope="col" colspan="2"><?php _e( 'Form', 'wpcf7' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td scope="col" style="width: 50%;">
                    <div>
                        <textarea id="wpcf7-form" name="wpcf7-form" cols="100" rows="20"><?php echo htmlspecialchars( $cf->form ); ?></textarea>
                    </div>
                </td>
                <td scope="col" style="width: 50%;">
                    <div id="tag-generator-div"></div>
                </td>
            </tr>
        </tbody>
    </table>
    
    <table class="widefat" style="margin-top: 1em;">
        <thead>
            <tr>
                <th scope="col" colspan="2"><?php _e( 'Mail', 'wpcf7' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td scope="col" style="width: 50%;">
				<div class="mail-field">
					<label for="wpcf7-mail-recipient"><?php _e( 'To:', 'wpcf7' ); ?></label><br />
					<input type="text" id="wpcf7-mail-recipient" name="wpcf7-mail-recipient" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->mail['recipient'] ); ?>" />
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-sender"><?php _e( 'From:', 'wpcf7' ); ?></label><br />
					<input type="text" id="wpcf7-mail-sender" name="wpcf7-mail-sender" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->mail['sender'] ); ?>" />
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-subject"><?php _e( 'Subject:', 'wpcf7' ); ?></label><br />
					<input type="text" id="wpcf7-mail-subject" name="wpcf7-mail-subject" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->mail['subject'] ); ?>" />
				</div>
                    <div class="pseudo-hr"></div>
				<div class="mail-field">
					<label for="wpcf7-mail-additional-headers"><?php _e( 'Additional headers:', 'wpcf7' ); ?></label><br />
					<textarea id="wpcf7-mail-additional-headers" name="wpcf7-mail-additional-headers" cols="100" rows="2"><?php echo htmlspecialchars( $cf->mail['additional_headers'] ); ?></textarea>
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-attachments"><?php _e( 'File attachments:', 'wpcf7' ); ?></label>
                        <br />
					<input type="text" id="wpcf7-mail-attachments" name="wpcf7-mail-attachments" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->mail['attachments'] ); ?>" />
				</div>
                    <div class="pseudo-hr"></div>
				<div class="mail-field">
                        <input type="checkbox" id="wpcf7-mail-use-html" name="wpcf7-mail-use-html" value="1"<?php echo ( $cf->mail['use_html'] ) ? ' checked="checked"' : ''; ?> />
                        <label for="wpcf7-mail-use-html"><?php _e( 'Use HTML content type', 'wpcf7' ); ?></label>
                    </div>
                </td>
                <td scope="col" style="width: 50%;">
				<div class="mail-field">
					<label for="wpcf7-mail-body"><?php _e( 'Message body:', 'wpcf7' ); ?></label><br />
					<textarea id="wpcf7-mail-body" name="wpcf7-mail-body" cols="100" rows="16"><?php echo htmlspecialchars( $cf->mail['body'] ); ?></textarea>
				</div>
                </td>
            </tr>
        </tbody>
    </table>
    
    <table class="widefat" style="margin-top: 1em;">
        <thead>
            <tr>
                <th scope="col" colspan="2"><?php _e( 'Mail (2)', 'wpcf7' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td scope="col" colspan="2">
				<input type="checkbox" id="wpcf7-mail-2-active" name="wpcf7-mail-2-active" value="1"<?php echo ( $cf->mail_2['active'] ) ? ' checked="checked"' : ''; ?> />
				<label for="wpcf7-mail-2-active"><?php _e( 'Use mail (2)', 'wpcf7' ); ?></label>
                </td>
            </tr>
            <tr id="mail-2-fields">
                <td scope="col" style="width: 50%;">
				<div class="mail-field">
					<label for="wpcf7-mail-2-recipient"><?php _e( 'To:', 'wpcf7' ); ?></label><br />
					<input type="text" id="wpcf7-mail-2-recipient" name="wpcf7-mail-2-recipient" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->mail_2['recipient'] ); ?>" />
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-2-sender"><?php _e( 'From:', 'wpcf7' ); ?></label><br />
					<input type="text" id="wpcf7-mail-2-sender" name="wpcf7-mail-2-sender" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->mail_2['sender'] ); ?>" />
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-2-subject"><?php _e( 'Subject:', 'wpcf7' ); ?></label><br />
					<input type="text" id="wpcf7-mail-2-subject" name="wpcf7-mail-2-subject" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->mail_2['subject'] ); ?>" />
				</div>
                    <div class="pseudo-hr"></div>
				<div class="mail-field">
					<label for="wpcf7-mail-2-additional-headers"><?php _e( 'Additional headers:', 'wpcf7' ); ?></label><br />
					<textarea id="wpcf7-mail-2-additional-headers" name="wpcf7-mail-2-additional-headers" cols="100" rows="2"><?php echo htmlspecialchars( $cf->mail_2['additional_headers'] ); ?></textarea>
				</div>
				<div class="mail-field">
					<label for="wpcf7-mail-2-attachments"><?php _e( 'File attachments:', 'wpcf7' ); ?></label>
                        <br />
					<input type="text" id="wpcf7-mail-2-attachments" name="wpcf7-mail-2-attachments" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->mail_2['attachments'] ); ?>" />
				</div>
                    <div class="pseudo-hr"></div>
				<div class="mail-field">
                        <input type="checkbox" id="wpcf7-mail-2-use-html" name="wpcf7-mail-2-use-html" value="1"<?php echo ( $cf->mail_2['use_html'] ) ? ' checked="checked"' : ''; ?> />
                        <label for="wpcf7-mail-2-use-html"><?php _e( 'Use HTML content type', 'wpcf7' ); ?></label>
                    </div>
                </td>
                <td scope="col" style="width: 50%;">
				<div class="mail-field">
					<label for="wpcf7-mail-2-body"><?php _e( 'Message body:', 'wpcf7' ); ?></label><br />
					<textarea id="wpcf7-mail-2-body" name="wpcf7-mail-2-body" cols="100" rows="16"><?php echo htmlspecialchars( $cf->mail_2['body'] ); ?></textarea>
				</div>
                </td>
            </tr>
        </tbody>
    </table>
    
    <table class="widefat" style="margin-top: 1em;">
        <thead>
            <tr>
                <th scope="col"><?php _e( 'Messages', 'wpcf7' ); ?> <span id="message-fields-toggle-switch"></span></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td scope="col">
                    <div id="message-fields">
                    <div class="message-field">
                        <label for="wpcf7-message-mail-sent-ok"><em># <?php _e( "Sender's message was sent successfully", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-mail-sent-ok" name="wpcf7-message-mail-sent-ok" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['mail_sent_ok'] ); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-mail-sent-ng"><em># <?php _e( "Sender's message was failed to send", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-mail-sent-ng" name="wpcf7-message-mail-sent-ng" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['mail_sent_ng'] ); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-akismet-says-spam"><em># <?php _e( "Akismet judged the sending activity as spamming", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-akismet-says-spam" name="wpcf7-message-akismet-says-spam" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['akismet_says_spam'] ); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-validation-error"><em># <?php _e( "Validation errors occurred", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-validation-error" name="wpcf7-message-validation-error" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['validation_error'] ); ?>" />
                    </div>
                    <div class="message-field" style="margin-top: 1em;">
                        <label for="wpcf7-message-invalid-required"><em># <?php _e( "There is a field that sender is needed to fill in", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-invalid-required" name="wpcf7-message-invalid-required" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['invalid_required'] ); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-invalid-email"><em># <?php _e( "Email address that sender entered is invalid", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-invalid-email" name="wpcf7-message-invalid-email" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['invalid_email'] ); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-accept-terms"><em># <?php _e( "There is a field of term that sender is needed to accept", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-accept-terms" name="wpcf7-message-accept-terms" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['accept_terms'] ); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-quiz-answer-not-correct"><em># <?php _e( "Sender doesn't enter the correct answer to the quiz", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-quiz-answer-not-correct" name="wpcf7-message-quiz-answer-not-correct" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['quiz_answer_not_correct'] ); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-captcha-not-match"><em># <?php _e( "The code that sender entered does not match the CAPTCHA", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-captcha-not-match" name="wpcf7-message-captcha-not-match" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['captcha_not_match'] ); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-upload-failed"><em># <?php _e( "Uploading a file fails for any reason", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-upload-failed" name="wpcf7-message-upload-failed" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['upload_failed'] ); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-upload-file-type-invalid"><em># <?php _e( "Uploaded file is not allowed file type", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-upload-file-type-invalid" name="wpcf7-message-upload-file-type-invalid" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['upload_file_type_invalid'] ); ?>" />
                    </div>
                    <div class="message-field">
                        <label for="wpcf7-message-upload-file-too-large"><em># <?php _e( "Uploaded file is too large", 'wpcf7' ); ?></em></label><br />
                        <input type="text" id="wpcf7-message-upload-file-too-large" name="wpcf7-message-upload-file-too-large" class="wide" size="70" value="<?php echo htmlspecialchars( $cf->messages['upload_file_too_large'] ); ?>" />
                    </div>
                    </div>

                </td>
            </tr>
        </tbody>
    </table>

    <table class="widefat" style="margin-top: 1em;">
        <thead>
            <tr>
                <th scope="col"><?php _e( 'Additional Settings', 'wpcf7' ); ?> <span id="additional-settings-fields-toggle-switch"></span></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td scope="col">
                    <div id="additional-settings-fields">
					<textarea id="wpcf7-additional-settings" name="wpcf7-additional-settings" cols="100" rows="8"><?php echo htmlspecialchars( $cf->additional_settings ); ?></textarea>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="widefat" style="margin-top: 1em;">
        <tbody>
            <tr>
                <td scope="col">
                    <div class="save-contact-form">
                        <input type="submit" class="button button-highlighted" name="wpcf7-save" value="<?php _e( 'Save', 'wpcf7' ); ?>" />
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <?php endif; ?>

</form>

<?php endif; ?>
</div>
