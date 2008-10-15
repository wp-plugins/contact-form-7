<?php if (isset($updated_message)) : ?>
<div id="message" class="updated fade"><p><strong><?php echo $updated_message; ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
	<h2><?php _e('Contact Form 7', 'wpcf7'); ?></h2>
    
    <p><a href="<?php echo $base_url . '?page=' . $page; ?>">&laquo; Back</a></p>

    <h3><?php _e('Export', 'wpcf7'); ?></h3>
    
    <p><?php _e('Select contact forms for export:', 'wpcf7'); ?></p>
    
    <form method="post" action="<?php echo $base_url . '?page=' . $page . '&action=export&step=download'; ?>">
        <ul style="list-style-type: none;">
    <?php foreach ($contact_forms as $k => $v) : ?>
            <li>
            <input type="checkbox" id="wpcf7-contact-form-<?php echo $k; ?>" name="wpcf7-contact-forms[<?php echo $k; ?>]" value="1" />
            <label for="wpcf7-contact-form-<?php echo $k; ?>"><?php echo $v['title']; ?></label>
            </li>
    <?php endforeach; ?>
        </ul>
        
        <div>
        <input type="submit" class="button button-highlighted" name="wpcf7-export" value="<?php _e('Download Export File', 'wpcf7'); ?>" />
        </div>
    </form>
</div>