<?php
/*
Plugin Name: Contact Form 7
Plugin URI: http://ideasilo.wordpress.com/2007/04/30/contact-form-7/
Description: Just another contact form plugin. Simple but flexible.
Author: Takayuki Miyoshi
Version: 1.1
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
		add_action('activate_' . strtr(plugin_basename(__FILE__), '\\', '/'), array(&$this, 'set_initial'));
		add_action('plugins_loaded', array(&$this, 'load_plugin_textdomain'), 20);
		add_action('admin_menu', array(&$this, 'add_pages'));
		add_action('admin_head', array(&$this, 'admin_page_stylesheet'));
		add_action('wp_head', array(&$this, 'stylesheet'));
		add_filter('the_content', array(&$this, 'the_content_filter'));
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
		$template .= '    [text* your-name "' . __('Your Name', 'wpcf7') . '"] </label></p>' . "\n\n";
		$template .= '<p><label>' . __('Your Email', 'wpcf7') . ' ' . __('(required)', 'wpcf7') . '<br />' . "\n";
		$template .= '    [email* your-email "' . __('Your Email', 'wpcf7') . '"] </label></p>' . "\n\n";
		$template .= '<p><label>' . __('Subject', 'wpcf7') . '<br />' . "\n";
		$template .= '    [text your-subject "' . __('Subject', 'wpcf7') . '"] </label></p>' . "\n\n";
		$template .= '<p><label>' . __('Your Message', 'wpcf7') . '<br />' . "\n";
		$template .= '    [textarea your-message "' . __('Your Message', 'wpcf7') . '"] </label></p>' . "\n\n";
		$template .= '[submit "' . __('Send', 'wpcf7') . '"]';
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

/* Post content filtering */

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
					$regex = '/\[\s*([a-zA-Z][0-9a-zA-Z:._-]*)\s*\]/';
					$callback = create_function('$matches', 'if (isset($_POST[$matches[1]])) return $_POST[$matches[1]]; else return $matches[0];');
					$mail_subject = preg_replace_callback($regex, $callback, $cf['mail']['subject']);
					$mail_sender = preg_replace_callback($regex, $callback, $cf['mail']['sender']);
					$mail_body = preg_replace_callback($regex, $callback, $cf['mail']['body']);
					$mail_headers = "MIME-Version: 1.0\n"
						. "From: $mail_sender\n"
						. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
					if (@wp_mail($cf['options']['recipient'], $mail_subject, $mail_body, $mail_headers)) {
						$_POST['_wpcf7_mail_sent'] = array('id' => $id, 'ok' => true, 'message' => $this->default_mail_result_message(true));
					} else {
						$_POST['_wpcf7_mail_sent'] = array('id' => $id, 'ok' => false, 'message' => $this->default_mail_result_message(false));
					}
				} else {
					$_POST['_wpcf7_validation_errors'] = array('id' => $id, 'messages' => $validation['reason']);
				}
			}
		}

		$regex = '/\[\s*contact-form\s+(\d+)(?:\s+.*?)?\s*\]/';
		return preg_replace_callback($regex, array(&$this, 'the_content_filter_callback'), $content);
	}
	
	function the_content_filter_callback($matches) {
		$contact_forms = $this->contact_forms();

		$id = (int) $matches[1];
		if (! ($cf = $contact_forms[$id])) return $matches[0];

		if (isset($_POST['_wpcf7'])) {
			if ((int) $_POST['_wpcf7'] == $id)
				$_POST['_wpcf7_submitted'] = 1;
			else
				unset($_POST['_wpcf7_submitted']);
		}

		$cf = stripslashes_deep($cf);

		$form_content = $this->form_elements($cf['form']);

		$form = '<div class="wpcf7" id="wpcf7_' . $id . '">';
		if (isset($_POST['_wpcf7_mail_sent']) && $_POST['_wpcf7_mail_sent']['id'] == $id) {
			if ($_POST['_wpcf7_mail_sent']['ok'])
				$form .= '<div class="wpcf7-mail-sent-ok"><ul><li>' . $_POST['_wpcf7_mail_sent']['message'] . '</li></ul></div>';
			else
				$form .= '<div class="wpcf7-mail-sent-ng"><ul><li>' . $_POST['_wpcf7_mail_sent']['message'] . '</li></ul></div>';
		} elseif (isset($_POST['_wpcf7_validation_errors']) && $_POST['_wpcf7_validation_errors']['id'] == $id) {
			$form .= '<div class="wpcf7-validation-errors"><ul>';
			foreach ($_POST['_wpcf7_validation_errors']['messages'] as $err) {
				$form .= '<li>' . $err . '</li>';
			}
			$form .= '</ul></div>';
		}
		$form .= '<form action="' . get_permalink() . '#wpcf7_' . $id . '" method="post">';
		$form .= '<input type="hidden" name="_wpcf7" value="' . $id . '" />';
		$form .= $form_content;
		$form .= '</form></div>';
		return $form;
	}

	function validate_form_elements($form_elements) {
		$valid = true;
		$reason = array();

		foreach ($form_elements as $fe) {
			$type = $fe['type'];
			$name = $fe['name'];
			$title = $fe['title'];

			// Required item (*)
			if (preg_match('/^(?:text|textarea)[*]$/', $type)) {
				if (empty($_POST[$name])) {
					$valid = false;
					$reason[] = sprintf(__('Please fill the required field: %s ', 'wpcf7'), $title);
				}
			}

			if (preg_match('/^email[*]?$/', $type)) {
				if ('*' == substr($type, -1) && empty($_POST[$name])) {
					$valid = false;
					$reason[] = sprintf(__('Please fill the required field: %s ', 'wpcf7'), $title);
				} elseif (! is_email($_POST[$name])) {
					$valid = false;
					$reason[] = sprintf(__('Email address seems invalid: %s ', 'wpcf7'), $title);
				}
			}
		}
		return compact('valid', 'reason');
	}

	function default_mail_result_message($ok = true) {
		if ($ok)
			return __('Your message was sent successfully. Thanks.', 'wpcf7');
		else
			return __('Failed to send your message. Please try later or contact administrator by other way.', 'wpcf7');
	}

	function stylesheet() {
		$stylesheet_url = get_option('siteurl') . '/wp-content/plugins/contact-form-7/admin-stylesheet.css';
		echo '<link rel="stylesheet" href="' . $stylesheet_url . '" type="text/css" />';
	}

/* Processing form element placeholders */

	function form_elements($form, $replace = true) {
		$regex = '%\[\s*([a-z]+[*]?)(\s+[a-zA-Z][0-9a-zA-Z:._-]*)(\s*(?:"[^"]*"|\'[^\']*\'))([-0-9a-zA-Z:_/\s]*)?(\s*(?:"[^"]*"|\'[^\']*\'))*\]%';
		if ($replace) {
			$form = preg_replace_callback($regex, array(&$this, 'form_element_replace_callback'), $form);
			// Submit button
			$form = preg_replace_callback('/\[\s*submit(\s+(?:"[^"]*"|\'[^\']*\'))?\s*\]/', array(&$this, 'submit_replace_callback'), $form);
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
		extract((array) $this->form_element_parse($matches));
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
			if ($class_att)
				$atts .= ' class="' . trim($class_att) . '"';
		}
		$type = preg_replace('/[*]$/', '', $type);
		switch ($type) {
			case 'text':
			case 'email':
				if (is_array($options)) {
					$size_maxlength_array = preg_grep('%^[0-9]*/[0-9]*$%', $options);
					if ($size_maxlength = array_shift($size_maxlength_array)) {
						preg_match('%^([0-9]*)/([0-9]*)$%', $size_maxlength, $sm_matches);
						if ($size = (int) $sm_matches[1])
							$atts .= ' size="' . $size . '"';
						if ($maxlength = (int) $sm_matches[2])
							$atts .= ' maxlength="' . $maxlength . '"';
					}
				}
				if (isset($_POST['_wpcf7_mail_sent']) && $_POST['_wpcf7_mail_sent']['ok']) {
					$value = '';
				} elseif (isset($_POST['_wpcf7_submitted'])) {
					$value = $_POST[$name];
				} else {
					$value = array_shift($values);
				}
				return '<input type="text" name="' . $name . '" value="' . $value . '"' . $atts . ' />';
				break;
			case 'textarea':
				if (is_array($options)) {
					$cols_rows_array = preg_grep('%^[0-9]*x[0-9]*$%', $options);
					if ($cols_rows = array_shift($cols_rows_array)) {
						preg_match('%^([0-9]*)x([0-9]*)$%', $cols_rows, $cr_matches);
						if ($cols = (int) $cr_matches[1])
							$atts .= ' cols="' . $cols . '"';
						if ($rows = (int) $cr_matches[2])
							$atts .= ' rows="' . $rows . '"';
					}
				}
				if (isset($_POST['_wpcf7_mail_sent']) && $_POST['_wpcf7_mail_sent']['ok']) {
					$value = '';
				} elseif (isset($_POST['_wpcf7_submitted'])) {
					$value = $_POST[$name];
				} else {
					$value = array_shift($values);
				}
				return '<textarea name="' . $name . '"' . $atts . '>' . $value . '</textarea>';
				break;
		}
	}

	function submit_replace_callback($matches) {
		if ($matches[1])
			$value = $this->strip_quote($matches[1]);
		if (empty($value))
			$value = __('Send', 'wpcf7');
		return '<input type="submit" value="' . $value . '" />';
	}

	function form_element_parse($element) {
		$type = trim($element[1]);
		$name = trim($element[2]);
		$title = $this->strip_quote($element[3]);
		$options = preg_split('/[\s]+/', trim($element[4]));
		$values = $this->strip_quote_deep(array_slice($element, 5));
		return compact('type', 'name', 'title', 'options', 'values');
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