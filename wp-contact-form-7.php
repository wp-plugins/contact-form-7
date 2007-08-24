<?php
/*
Plugin Name: Contact Form 7
Plugin URI: http://ideasilo.wordpress.com/2007/04/30/contact-form-7/
Description: Just another contact form plugin. Simple but flexible.
Author: Takayuki Miyoshi
Version: 1.2
Author URI: http://ideasilo.wordpress.com/
*/

/*  Copyright 2007 Takayuki Miyoshi (email: takayukister at gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class tam_contact_form_seven {

	var $contact_forms;

	function tam_contact_form_seven() {
		// This backslash replacement for Win32 will be unnecessary. See http://trac.wordpress.org/ticket/3002
		add_action('activate_' . strtr(plugin_basename(__FILE__), '\\', '/'), array(&$this, 'set_initial'));
		add_action('plugins_loaded', array(&$this, 'load_plugin_textdomain'), 20);
		add_action('admin_menu', array(&$this, 'add_pages'));
		add_action('admin_head', array(&$this, 'admin_page_stylesheet'));
		add_action('wp_head', array(&$this, 'wp_head'));
		add_action('wp_print_scripts', array(&$this, 'load_js'));
		add_action('init', array(&$this, 'ajax_json_echo'));
		add_filter('the_content', array(&$this, 'the_content_filter'), 9);
		remove_filter('the_content', 'wpautop');
		add_filter('the_content', array(&$this, 'wpautop_substitute'));
	}
	
	// Original wpautop function has harmful effect on formatting of form elements.
	// This wpautop_substitute is a temporary substitution until original is patched.
	// See http://trac.wordpress.org/ticket/4605
	function wpautop_substitute($pee, $br = 1) {
		$pee = $pee . "\n"; // just to make things a little easier, pad the end
		$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
		// Space things out a little
		$allblocks = '(?:address|area|blockquote|caption|colgroup|dd|div|dl|dt|form|h[1-6]|li|map|math|ol|p|pre|table|tbody|td|tfoot|th|thead|tr|ul)';
		$pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
		$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
		$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
		$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
		$pee = preg_replace('/\n?(.+?)(?:\n\s*\n|\z)/s', "<p>$1</p>\n", $pee); // make paragraphs, including one at the end
		$pee = preg_replace('|<p>\s*?</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
		$pee = preg_replace('!<p>([^<]+)\s*?(</(?:div|address|form)[^>]*>)!', "<p>$1</p>$2", $pee);
		$pee = preg_replace( '|<p>|', "$1<p>", $pee );
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
		$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
		$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
		$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
		if ($br) {
			$pee = preg_replace('/<(script|style).*?<\/\\1>/se', 'str_replace("\n", "<WPPreserveNewline />", "\\0")', $pee);
			$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
			$pee = str_replace('<WPPreserveNewline />', "\n", $pee);
		}
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
		$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
		if (strpos($pee, '<pre') !== false)
			$pee = preg_replace('!(<pre.*?>)(.*?)</pre>!ise', " stripslashes('$1') .  stripslashes(clean_pre('$2'))  . '</pre>' ", $pee);
		$pee = preg_replace( "|\n</p>$|", '</p>', $pee );
		
		return $pee;
	}
	
	function ajax_json_echo() {
		if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_GET['wpcf7']) && 'json' == $_GET['wpcf7']) {
			if (isset($_POST['_wpcf7'])) {
				$id = (int) $_POST['_wpcf7'];
				$unit_tag = $_POST['_wpcf7_unit_tag'];
				$contact_forms = $this->contact_forms();
				if ($cf = $contact_forms[$id]) {
					$cf = stripslashes_deep($cf);
					$into = '#' . $unit_tag . ' div.wpcf7-response-output';
					if ($this->mail($cf)) {
						echo '{ mailSent: 1, message: "' . $this->message('mail_sent_ok') . '", into: "' . $into . '" }';
					} else {
						echo '{ mailSent: 0, message: "' . $this->message('mail_sent_ng') . '", into: "' . $into . '" }';
					}
				}
			}
			exit();
		}
	}
	
	function mail($contact_form) {
		$regex = '/\[\s*([a-zA-Z][0-9a-zA-Z:._-]*)\s*\]/';
		$callback = create_function('$matches', 'if (isset($_POST[$matches[1]])) return $_POST[$matches[1]]; else return $matches[0];');
		$mail_subject = preg_replace_callback($regex, $callback, $contact_form['mail']['subject']);
		$mail_sender = preg_replace_callback($regex, $callback, $contact_form['mail']['sender']);
		$mail_body = preg_replace_callback($regex, $callback, $contact_form['mail']['body']);
		$mail_headers = "MIME-Version: 1.0\n"
			. "From: $mail_sender\n"
			. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
		if (@wp_mail($contact_form['options']['recipient'], $mail_subject, $mail_body, $mail_headers)) {
			return true;
		} else {
			return false;
		}
	}
	
	function set_initial() {
		$wpcf7 = get_option('wpcf7');
		if (! is_array($wpcf7))
			$wpcf7 = array();

		$contact_forms = $wpcf7['contact_forms'];
		if (! is_array($contact_forms))
			$contact_forms = array();

		if (0 == count($contact_forms))
			$contact_forms[1] = $this->default_pack(__('Contact form', 'wpcf7') . ' 1');

		$wpcf7['contact_forms'] = $contact_forms;
		update_option('wpcf7', $wpcf7);
	}

	function load_plugin_textdomain() { // l10n
		load_plugin_textdomain('wpcf7', 'wp-content/plugins/contact-form-7/languages');
	}

	function contact_forms() {
		if (is_array($this->contact_forms))
			return $this->contact_forms;
		$wpcf7 = get_option('wpcf7');
		$this->contact_forms = $wpcf7['contact_forms'];
		if (! is_array($this->contact_forms))
			$this->contact_forms = array();
		return $this->contact_forms;
	}

	function update_contact_forms($contact_forms) {
		$wpcf7 = get_option('wpcf7');
		$wpcf7['contact_forms'] = $contact_forms;
		update_option('wpcf7', $wpcf7);
	}

/* Admin panel */

	function add_pages() {
		add_options_page(__('Contact Form 7', 'wpcf7'), __('Contact Form 7', 'wpcf7'), 'manage_options', __FILE__, array(&$this, 'option_page'));
	}
	
	function admin_page_stylesheet() {
		global $plugin_page;
		
		if (isset($plugin_page) && $plugin_page == plugin_basename(__FILE__)) {
			$admin_stylesheet_url = get_option('siteurl') . '/wp-content/plugins/contact-form-7/admin-stylesheet.css';
			echo '<link rel="stylesheet" href="' . $admin_stylesheet_url . '" type="text/css" />';
		}
	}
	
	function option_page() {
		$base_url = $_SERVER['PHP_SELF'] . '?page=' . plugin_basename(__FILE__);
		$contact_forms = $this->contact_forms();
		
		$id = $_POST['wpcf7-id'];
		
		if (isset($_POST['wpcf7-delete'])) {
			check_admin_referer('wpcf7-delete_' . $id);
			$updated_message = sprintf(__('Contact form "%s" deleted. ', 'wpcf7'), $contact_forms[$id]['title']);
			unset($contact_forms[$id]);
			$this->update_contact_forms($contact_forms);
		} elseif (isset($_POST['wpcf7-save'])) {
			check_admin_referer('wpcf7-save_' . $id);
			$title = trim($_POST['wpcf7-title']);
			$form = trim($_POST['wpcf7-form']);
			$mail_subject = trim($_POST['wpcf7-mail-subject']);
			$mail_sender = trim($_POST['wpcf7-mail-sender']);
			$mail_body = trim($_POST['wpcf7-mail-body']);
			$options_recipient = trim($_POST['wpcf7-options-recipient']);
			
			$mail = array('subject' => $mail_subject, 'sender' => $mail_sender, 'body' => $mail_body);
			$options = array('recipient' => $options_recipient);
			
			$contact_forms[$id] = compact('title', 'form', 'mail', 'options');
			$updated_message = sprintf(__('Contact form "%s" saved. ', 'wpcf7'), $contact_forms[$id]['title']);
			$this->update_contact_forms($contact_forms);
		}

		if ('new' == $_GET['contactform'] || 0 == count($contact_forms)) {
			$initial = true;
			$contact_forms[] = array();
			$current = max(array_keys($contact_forms));
			$contact_forms[$current] = $this->default_pack(__('Contact form', 'wpcf7') . ' ' . $current, true);
		} else {
			$current = (int) $_GET['contactform'];
			if (! array_key_exists($current, $contact_forms))
				$current = min(array_keys($contact_forms));
		}

		include 'includes/admin-panel.php';
	}

	function default_pack($title, $initial = false) {
		$cf = array('title' => $title,
			'form' => $this->default_form_template(),
			'mail' => $this->default_mail_template(),
			'options' => $this->default_options_template());
		if ($initial)
			$cf['initial'] = true;
		return $cf;
	}

	function default_form_template() {
		$template .= '<p><label>' . __('Your Name', 'wpcf7') . ' ' . __('(required)', 'wpcf7') . '<br />' . "\n";
		$template .= '    [text* your-name] </label></p>' . "\n\n";
		$template .= '<p><label>' . __('Your Email', 'wpcf7') . ' ' . __('(required)', 'wpcf7') . '<br />' . "\n";
		$template .= '    [email* your-email] </label></p>' . "\n\n";
		$template .= '<p><label>' . __('Subject', 'wpcf7') . '<br />' . "\n";
		$template .= '    [text your-subject] </label></p>' . "\n\n";
		$template .= '<p><label>' . __('Your Message', 'wpcf7') . '<br />' . "\n";
		$template .= '    [textarea your-message] </label></p>' . "\n\n";
		$template .= '<p>[submit "' . __('Send', 'wpcf7') . '"]</p>';
		return $template;
	}
	
	function default_mail_template() {
		$subject = '[your-subject]';
		$sender = '[your-name] <[your-email]>';
		$body = '[your-message]';
		return compact('subject', 'sender', 'body');
	}

	function default_options_template() {
		$recipient = get_option('admin_email');
		return compact('recipient');
	}
	
	function message($status) {
		switch ($status) {
			case 'mail_sent_ok':
				return __('Your message was sent successfully. Thanks.', 'wpcf7');
			case 'mail_sent_ng':
				return __('Failed to send your message. Please try later or contact administrator by other way.', 'wpcf7');
		}
	}

/* Post content filtering */

	var $order_in_post; // Which contact form unit now you are processing. Integer value used in $unit_tag.
	
	function the_content_filter($content) {
		// Form submitted?
		if (isset($_POST['_wpcf7'])) {
			$id = (int) $_POST['_wpcf7'];
			$contact_forms = $this->contact_forms();
			if ($cf = $contact_forms[$id]) {
				$cf = stripslashes_deep($cf);
				$fes = $this->form_elements($cf['form'], false);
				$validation = $this->validate_form_elements($fes);
				if ($validation['valid']) {
					if ($this->mail($cf)) {
						$_POST['_wpcf7_mail_sent'] = array('id' => $id, 'ok' => true, 'message' => $this->message('mail_sent_ok'));
					} else {
						$_POST['_wpcf7_mail_sent'] = array('id' => $id, 'ok' => false, 'message' => $this->message('mail_sent_ng'));
					}
				} else {
					$_POST['_wpcf7_validation_errors'] = array('id' => $id, 'messages' => $validation['reason']);
				}
			}
		}
		
		$this->order_in_post = 1;

		$regex = '/\[\s*contact-form\s+(\d+)(?:\s+.*?)?\s*\]/';
		return preg_replace_callback($regex, array(&$this, 'the_content_filter_callback'), $content);
	}
	
	var $processing_unit_tag;
	
	function the_content_filter_callback($matches) {
		$contact_forms = $this->contact_forms();

		$id = (int) $matches[1];
		if (! ($cf = $contact_forms[$id])) return $matches[0];
		
		$cf = stripslashes_deep($cf);

		$unit_tag = 'wpcf7-f' . $id . '-p' . get_the_ID() . '-o' . $this->order_in_post;
		$this->processing_unit_tag = $unit_tag;

		$form = '<div class="wpcf7" id="' . $unit_tag . '">';
		
		$url = parse_url($_SERVER['REQUEST_URI']);
		$url = $url['path'] . (empty($url['query']) ? '' : '?' . $url['query']) . '#' . $unit_tag;
		
		$form .= '<form action="' . $url . '" method="post">';
		$form .= '<input type="hidden" name="_wpcf7" value="' . $id . '" />';
		$form .= '<input type="hidden" name="_wpcf7_unit_tag" value="' . $unit_tag . '" />';
		$form .= $this->form_elements($cf['form']);
		$form .= '</form>';
		
		// Post response output for non-AJAX
		$class = 'wpcf7-response-output';
		
		if ($this->processing_unit_tag == $_POST['_wpcf7_unit_tag']) {
			if (isset($_POST['_wpcf7_mail_sent']) && $_POST['_wpcf7_mail_sent']['id'] == $id) {
				if ($_POST['_wpcf7_mail_sent']['ok']) {
					$clsss .= ' wpcf7-mail-sent-ok';
					$content = $_POST['_wpcf7_mail_sent']['message'];
				} else {
					$class .= ' wpcf7-mail-sent-ng';
					$content = $_POST['_wpcf7_mail_sent']['message'];
				}
			} elseif (isset($_POST['_wpcf7_validation_errors']) && $_POST['_wpcf7_validation_errors']['id'] == $id) {
				$class .= ' wpcf7-validation-errors';
				$content = __('Validation errors occurred. Please confirm the fields and submit it again.', 'wpcf7');
			}
		}
		
		$class = ' class="' . $class . '"';
		
		$form .= '<div' . $class . '>' . $content . '</div>';
		
		$form .= '</div>';
		
		$this->order_in_post += 1;
		$this->processing_unit_tag = null;
		return $form;
	}

	function validate_form_elements($form_elements) {
		$valid = true;
		$reason = array();

		foreach ($form_elements as $fe) {
			$type = $fe['type'];
			$name = $fe['name'];

			// Required item (*)
			if (preg_match('/^(?:text|textarea)[*]$/', $type)) {
				if (empty($_POST[$name])) {
					$valid = false;
					$reason[$name] = __('Please fill the required field.', 'wpcf7');
				}
			}

			if (preg_match('/^email[*]?$/', $type)) {
				if ('*' == substr($type, -1) && empty($_POST[$name])) {
					$valid = false;
					$reason[$name] = __('Please fill the required field.', 'wpcf7');
				} elseif (! is_email($_POST[$name])) {
					$valid = false;
					$reason[$name] = __('Email address seems invalid.', 'wpcf7');
				}
			}
		}
		return compact('valid', 'reason');
	}

	function wp_head() {
		$stylesheet_url = get_option('siteurl') . '/wp-content/plugins/contact-form-7/stylesheet.css';
		echo '<link rel="stylesheet" href="' . $stylesheet_url . '" type="text/css" />';
		
		$url = parse_url($_SERVER['REQUEST_URI']);
		if (empty($url['query']))
			$override_url = $url['path'] . '?wpcf7=json';
		else
			$override_url = $url['path'] . '?' . $url['query'] . '&wpcf7=json';
		
?>
<script type="text/javascript">
//<![CDATA[

$(document).ready(function() {
	$('div.wpcf7 > form').ajaxForm({
		beforeSubmit: validate,
		url: '<?php echo $override_url; ?>',
		dataType: 'json',
		success: processJson,
		clearForm: true,
		resetForm: true
	});
});

function validate(formData, jqForm, options) {
	var wpcf7ResponseOutput = $('div.wpcf7-response-output', jqForm.parents('div.wpcf7')).lt(1);
	clearResponseOutput();
	$('img.ajax-loader', jqForm[0]).css({ visibility: 'visible' });
	var valid = true;
	
	$('.wpcf7-validates-as-email', jqForm[0]).each(function() {
		if (! isEmail(this.value)) {
			$(this).addClass('wpcf7-email-not-valid');
			this.wpcf7InvalidMessage = '<?php _e('Email address seems invalid.', 'wpcf7'); ?>';
		}
	});

	$('.wpcf7-validates-as-required', jqForm[0]).each(function() {
		if (! this.value) {
			$(this).addClass('wpcf7-required-not-valid');
			this.wpcf7InvalidMessage = '<?php _e('Please fill the required field.', 'wpcf7'); ?>';
		}
	});
	
	$.each(jqForm[0].elements, function() {
		if (this.wpcf7InvalidMessage) {
			notValidTip(this, this.wpcf7InvalidMessage);
			valid = false;
			this.wpcf7InvalidMessage = null;
		}
	});
	
	if (! valid) {
		$('img.ajax-loader', jqForm[0]).css({ visibility: 'hidden' });
		wpcf7ResponseOutput.addClass('wpcf7-validation-errors');
		wpcf7ResponseOutput.append('<?php _e('Validation errors occurred. Please confirm the fields and submit it again.', 'wpcf7'); ?>').fadeIn('fast');
	}
	
	return valid;
}

function isEmail(user_email) {
	var chars = /^[-a-z0-9+_.]+@([-a-z0-9_]+[.])+[a-z]{2,6}$/i;
	return chars.test(user_email);
}

function notValidTip(input, message) {
	$(input).after('<span class="wpcf7-not-valid-tip">' + message + '</span>');
	$('span.wpcf7-not-valid-tip').mouseover(function() {
		$(this).fadeOut('fast');
	});
	$(input).mouseover(function() {
		$(input).siblings('.wpcf7-not-valid-tip').fadeOut('fast');
	});
}

function processJson(data) {
	var wpcf7ResponseOutput = $(data.into);
	clearResponseOutput();
	if (1 == data.mailSent) {
		wpcf7ResponseOutput.addClass('wpcf7-mail-sent-ok');
	} else {
		wpcf7ResponseOutput.addClass('wpcf7-mail-sent-ng');
	}
	wpcf7ResponseOutput.append(data.message).fadeIn('fast');
}

function clearResponseOutput() {
	$('div.wpcf7-response-output').hide().empty().removeClass('wpcf7-mail-sent-ok wpcf7-mail-sent-ng wpcf7-validation-errors');
	$('span.wpcf7-not-valid-tip').remove();
	$('img.ajax-loader').css({ visibility: 'hidden' });
}

//]]>
</script>
<?php
	}
	
	function load_js() {
		if (! is_admin())
			wp_enqueue_script('jquery-form');
	}

/* Processing form element placeholders */

	function form_elements($form, $replace = true) {
		$regex = '%\[\s*((?:text|email|textarea|select)[*]?)(\s+[a-zA-Z][0-9a-zA-Z:._-]*)([-0-9a-zA-Z:_/\s]*)?((?:\s*(?:"[^"]*"|\'[^\']*\'))*)?\s*\]%';
		$submit_regex = '/\[\s*submit(\s+(?:"[^"]*"|\'[^\']*\'))?\s*\]/';
		if ($replace) {
			$form = preg_replace_callback($regex, array(&$this, 'form_element_replace_callback'), $form);
			// Submit button
			$form = preg_replace_callback($submit_regex, array(&$this, 'submit_replace_callback'), $form);
			return $form;
		} else {
			$results = array();
			preg_match_all($regex, $form, $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$results[] = (array) $this->form_element_parse($match);
			}
			return $results;
		}
	}

	function form_element_replace_callback($matches) {
		extract((array) $this->form_element_parse($matches)); // $type, $name, $options, $values
		
		if ($this->processing_unit_tag == $_POST['_wpcf7_unit_tag']) {
			$validation_error = $_POST['_wpcf7_validation_errors']['messages'][$name];
			$validation_error = $validation_error ? '<span class="wpcf7-not-valid-tip-no-ajax">' . $validation_error . '</span>' : '';
		} else {
			$validation_error = '';
		}
		
		$atts = '';
		if (is_array($options)) {
			$id_array = preg_grep('%^id:[-0-9a-zA-Z_]+$%', $options);
			if ($id = array_shift($id_array)) {
				preg_match('%^id:([-0-9a-zA-Z_]+)$%', $id, $id_matches);
				if ($id = $id_matches[1])
					$atts .= ' id="' . $id . '"';
			}
			
			$class_att = "";
			$class_array = preg_grep('%^class:[-0-9a-zA-Z_]+$%', $options);
			foreach ($class_array as $class) {
				preg_match('%^class:([-0-9a-zA-Z_]+)$%', $class, $class_matches);
				if ($class = $class_matches[1])
					$class_att .= ' ' . $class;
			}
			
			if (preg_match('/^email[*]?$/', $type))
				$class_att .= ' wpcf7-validates-as-email';
			if (preg_match('/[*]$/', $type))
				$class_att .= ' wpcf7-validates-as-required';
				
			if ($class_att)
				$atts .= ' class="' . trim($class_att) . '"';
		}
		
		// Value.
		if ($this->processing_unit_tag == $_POST['_wpcf7_unit_tag']) {
			if (isset($_POST['_wpcf7_mail_sent']) && $_POST['_wpcf7_mail_sent']['ok'])
				$value = '';
			else
				$value = $_POST[$name];
		} else {
			$value = $values[0];
		}
		
		$type = preg_replace('/[*]$/', '', $type);
		switch ($type) {
			case 'text':
			case 'email':
				if (is_array($options)) {
					$size_maxlength_array = preg_grep('%^[0-9]*[/x][0-9]*$%', $options);
					if ($size_maxlength = array_shift($size_maxlength_array)) {
						preg_match('%^([0-9]*)[/x]([0-9]*)$%', $size_maxlength, $sm_matches);
						if ($size = (int) $sm_matches[1])
							$atts .= ' size="' . $size . '"';
						if ($maxlength = (int) $sm_matches[2])
							$atts .= ' maxlength="' . $maxlength . '"';
					}
				}
				$html = '<input type="text" name="' . $name . '" value="' . attribute_escape($value) . '"' . $atts . ' />';
				$html = '<span style="position: relative;">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'textarea':
				if (is_array($options)) {
					$cols_rows_array = preg_grep('%^[0-9]*[x/][0-9]*$%', $options);
					if ($cols_rows = array_shift($cols_rows_array)) {
						preg_match('%^([0-9]*)[x/]([0-9]*)$%', $cols_rows, $cr_matches);
						if ($cols = (int) $cr_matches[1])
							$atts .= ' cols="' . $cols . '"';
						if ($rows = (int) $cr_matches[2])
							$atts .= ' rows="' . $rows . '"';
					}
				}
				$html = '<textarea name="' . $name . '"' . $atts . '>' . $value . '</textarea>';
				$html = '<span style="position: relative;">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'select':
				if (empty($values))
					array_push($values, '---');
				$html = '';
				foreach ($values as $value) {
					$html .= '<option value="' . attribute_escape($value) . '">' . $value . '</option>';
				}
				$html = '<select name="' . $name . '"' . $atts . '>' . $html . '</select>';
				$html = '<span style="position: relative;">' . $html . $validation_error . '</span>';
				return $html;
				break;
		}
	}

	function submit_replace_callback($matches) {
		if ($matches[1])
			$value = $this->strip_quote($matches[1]);
		if (empty($value))
			$value = __('Send', 'wpcf7');
		$ajax_loader_image_url = get_option('siteurl') . '/wp-content/plugins/contact-form-7/images/ajax-loader.gif';
		return '<input type="submit" value="' . $value . '" /> <img class="ajax-loader" style="visibility: hidden;" src="' . $ajax_loader_image_url . '" />';
	}

	function form_element_parse($element) {
		$type = trim($element[1]);
		$name = trim($element[2]);
		$options = preg_split('/[\s]+/', trim($element[3]));
		
		preg_match_all('/"[^"]*"|\'[^\']*\'/', $element[4], $matches);
		$values = $this->strip_quote_deep($matches[0]);
		
		return compact('type', 'name', 'options', 'values');
	}

	function strip_quote($text) {
		$text = trim($text);
		if (preg_match('/^"(.*)"$/', $text, $matches))
			$text = $matches[1];
		elseif (preg_match("/^'(.*)'$/", $text, $matches))
			$text = $matches[1];
		return $text;
	}

	function strip_quote_deep($arr) {
		if (is_string($arr))
			return $this->strip_quote($arr);
		if (is_array($arr)) {
			$result = array();
			foreach ($arr as $key => $text) {
				$result[$key] = $this->strip_quote($text);
			}
			return $result;
		}
	}
	
}

new tam_contact_form_seven();

?>