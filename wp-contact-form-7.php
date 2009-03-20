<?php
/*
Plugin Name: Contact Form 7
Plugin URI: http://ideasilo.wordpress.com/2007/04/30/contact-form-7/
Description: Just another contact form plugin. Simple but flexible.
Author: Takayuki Miyoshi
Version: 1.9.3
Author URI: http://ideasilo.wordpress.com/
*/

/*  Copyright 2007-2009 Takayuki Miyoshi (email: takayukister at gmail.com)

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

define('WPCF7_VERSION', '1.9.3');

if (! defined('WP_CONTENT_DIR'))
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
if (! defined('WP_CONTENT_URL'))
    define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');

if (! defined('WP_PLUGIN_DIR'))
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
if (! defined('WP_PLUGIN_URL'))
    define('WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins');

if (! defined('WPCF7_PLUGIN_DIR'))
    define('WPCF7_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__)));
if (! defined('WPCF7_PLUGIN_URL'))
    define('WPCF7_PLUGIN_URL', WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__)));

if (! defined('WPCF7_AUTOP'))
    define('WPCF7_AUTOP', true);

if (! defined('WPCF7_USE_PIPE'))
    define('WPCF7_USE_PIPE', true);

if (! function_exists('wpcf7_version')) {
    function wpcf7_version() { return WPCF7_VERSION; }
}

if (! function_exists('wpcf7_read_capability')) {
    function wpcf7_read_capability() { return 'edit_posts'; }
}

if (! function_exists('wpcf7_read_write_capability')) {
    function wpcf7_read_write_capability() { return 'publish_pages'; }
}

class tam_contact_form_seven {

	var $contact_forms;
	var $captcha;
    var $posted_data;

	function tam_contact_form_seven() {
		add_action('activate_' . plugin_basename(__FILE__), array(&$this, 'set_initial'));
		add_action('init', array(&$this, 'load_plugin_textdomain'));
		add_action('admin_menu', array(&$this, 'add_pages'));
		add_action('admin_head', array(&$this, 'admin_head'));
		add_action('wp_head', array(&$this, 'wp_head'));
		add_action('wp_print_scripts', array(&$this, 'load_js'));
		add_action('init', array(&$this, 'init_switch'), 11);
		add_filter('the_content', array(&$this, 'the_content_filter'), 9);
		add_filter('widget_text', array(&$this, 'widget_text_filter'), 9);
        
        add_shortcode('contact-form', array(&$this, 'contact_form_tag_func'));
	}

	function init_switch() {
		if ('POST' == $_SERVER['REQUEST_METHOD'] && 1 == (int) $_POST['_wpcf7_is_ajax_call']) {
			$this->ajax_json_echo();
			exit();
		} elseif (! is_admin()) {
			$this->process_nonajax_submitting();
			$this->cleanup_captcha_files();
		}
	}
	
	function ajax_json_echo() {
        $echo = '';
        
		if (isset($_POST['_wpcf7'])) {
			$id = (int) $_POST['_wpcf7'];
			$unit_tag = $_POST['_wpcf7_unit_tag'];
			$contact_forms = $this->contact_forms();
			if ($cf = $contact_forms[$id]) {
				$cf = stripslashes_deep($cf);
				$validation = $this->validate($cf);
				
                $handled_uploads = $this->handle_uploads($cf);
                if (! $handled_uploads['validation']['valid'])
                    $validation['valid'] = false;
                $validation['reason'] = array_merge($validation['reason'], $handled_uploads['validation']['reason']);
                
				$captchas = $this->refill_captcha($cf);
				if (! empty($captchas)) {
					$captchas_js = array();
					foreach ($captchas as $name => $cap) {
						$captchas_js[] = '"' . $name . '": "' . $cap . '"';
					}
					$captcha = '{ ' . join(', ', $captchas_js) . ' }';
				} else {
					$captcha = 'null';
				}
                
                $quizzes = $this->refill_quiz($cf);
                if (! empty($quizzes)) {
                    $quizzes_js = array();
                    foreach ($quizzes as $name => $q) {
                        $quizzes_js[] = '"' . $name . '": [ "' . js_escape($q[0]) . '", "' . $q[1] . '" ]';
                    }
                    $quiz = '{ ' . join(', ', $quizzes_js) . ' }';
                } else {
                    $quiz = 'null';
                }
				
				if (! $validation['valid']) { // Validation error occured
					$invalids = array();
					foreach ($validation['reason'] as $name => $reason) {
						$invalids[] = '{ into: "span.wpcf7-form-control-wrap.' . $name . '", message: "' . js_escape($reason) . '" }';
					}
					$invalids = '[' . join(', ', $invalids) . ']';
					$echo = '{ mailSent: 0, message: "' . js_escape($this->message($cf, 'validation_error')) . '", into: "#' . $unit_tag . '", invalids: ' . $invalids . ', captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
                } elseif (! $this->acceptance($cf)) { // Not accepted terms
                    $echo = '{ mailSent: 0, message: "' . js_escape($this->message($cf, 'accept_terms')) . '", into: "#' . $unit_tag . '", captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
				} elseif ($this->akismet($cf)) { // Spam!
					$echo = '{ mailSent: 0, message: "' . js_escape($this->message($cf, 'akismet_says_spam')) . '", into: "#' . $unit_tag . '", spam: 1, captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
				} elseif ($this->mail($cf, $handled_uploads['files'])) {
					$echo = '{ mailSent: 1, message: "' . js_escape($this->message($cf, 'mail_sent_ok')) . '", into: "#' . $unit_tag . '", captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
				} else {
					$echo = '{ mailSent: 0, message: "' . js_escape($this->message($cf, 'mail_sent_ng')) . '", into: "#' . $unit_tag . '", captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
				}
                
                // remove upload files
                foreach ($handled_uploads['files'] as $name => $path) {
                    @unlink($path);
                }
			}
		}
        
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            @header('Content-Type: application/json; charset=' . get_option('blog_charset'));
            echo $echo;
        } else {
            @header('Content-Type: text/html; charset=' . get_option('blog_charset'));
            echo '<textarea>' . $echo . '</textarea>';
        }
	}
    
    function handle_uploads($contact_form) {
        $files = array();
        $valid = true;
        $reason = array();
        
        $this->init_uploads(); // Confirm upload dir
        $uploads_dir = $this->upload_tmp_dir();
        
        $fes = $this->form_elements($contact_form['form'], false);
        
        foreach ($fes as $fe) {
            if ('file' != $fe['type'] && 'file*' != $fe['type'])
                continue;
            
            $name = $fe['name'];
            $options = (array) $fe['options'];

            $file = $_FILES[$name];

            if (empty($file['tmp_name']) && 'file*' == $fe['type']) {
                $valid = false;
                $reason[$name] = $this->message($contact_form, 'invalid_required');
                continue;
            }
            
            if (! is_uploaded_file($file['tmp_name']))
                continue;
            
            $filename = wp_unique_filename($uploads_dir, $file['name']);
            $new_file = trailingslashit($uploads_dir) . $filename;
            if (false === @move_uploaded_file($file['tmp_name'], $new_file)) {
                $valid = false;
                $reason[$name] = $this->message($contact_form, 'upload_failed');
                continue;
            }

            $files[$name] = $new_file;

            if ($allowed_types_options = preg_grep('%^filetypes:%', $options)) {
                $pattern = '';
                foreach ($allowed_types_options as $allowed_types_option) {
                    if (preg_match('%^filetypes:(.+)$%', $allowed_types_option, $matches)) {
                        $file_types = explode('|', $matches[1]);
                        foreach ($file_types as $file_type) {
                            $file_type = trim($file_type, '.');
                            $file_type = str_replace(array('.', '+', '*', '?'), array('\.', '\+', '\*', '\?'), $file_type);
                            $pattern .= '|' . $file_type;
                        }
                    }
                }
                $pattern = trim($pattern, '|');
                $pattern = '(' . $pattern . ')';
                $pattern = '/\.' . $pattern . '$/i';
                if (! preg_match($pattern, $file['name'])) {
                    $valid = false;
                    $reason[$name] = $this->message($contact_form, 'upload_file_type_invalid');
                    continue;
                }
            }
            
            if ($allowed_size_options = preg_grep('%^limit:%', $options)) {
                $allowed_size_option = array_shift($allowed_size_options);
                preg_match('/^limit:([1-9][0-9]*)$/', $allowed_size_option, $matches);
                $allowed_size = (int) $matches[1];
                
                if ($file['size'] > $allowed_size) {
                    $valid = false;
                    $reason[$name] = $this->message($contact_form, 'upload_file_too_large');
                    continue;
                }
            }
        }
        
        $validation = compact('valid', 'reason');
        
        return compact('files', 'validation');
    }
	
	function mail($contact_form, $files = array()) {
        global $wp_version;
        
		$contact_form = $this->upgrade($contact_form);

        $this->posted_data = $_POST;

        if (WPCF7_USE_PIPE) {
            $this->pipe_all_posted($contact_form);
        }

        if ($this->compose_and_send_mail($contact_form['mail'], $files)) {
            if ($contact_form['mail_2']['active'])
                $this->compose_and_send_mail($contact_form['mail_2'], $files);
            
            return true;
        }
        
        return false;
	}
    
    function compose_and_send_mail($mail_template, $attachments = array()) {
        $regex = '/\[\s*([a-zA-Z][0-9a-zA-Z:._-]*)\s*\]/';
        $callback = array(&$this, 'mail_callback');
		$mail_subject = preg_replace_callback($regex, $callback, $mail_template['subject']);
		$mail_sender = preg_replace_callback($regex, $callback, $mail_template['sender']);
		$mail_body = preg_replace_callback($regex, $callback, $mail_template['body']);
		$mail_recipient = preg_replace_callback($regex, $callback, $mail_template['recipient']);
		$mail_headers = "From: $mail_sender\n";

        if ($mail_template['use_html'])
            $mail_headers .= "Content-Type: text/html\n";

        if ($attachments) {
            $for_this_mail = array();
            foreach ($attachments as $name => $path) {
                if (false === strpos($mail_template['attachments'], "[${name}]"))
                    continue;
                $for_this_mail[] = $path;
            }
            return @wp_mail($mail_recipient, $mail_subject, $mail_body, $mail_headers, $for_this_mail);
        } else {
            return @wp_mail($mail_recipient, $mail_subject, $mail_body, $mail_headers);
        }
    }
    
    function mail_callback($matches) {
        if (isset($this->posted_data[$matches[1]])) {
            $submitted = $this->posted_data[$matches[1]];
            
            if (is_array($submitted))
                $submitted = join(', ', $submitted);
            return stripslashes($submitted);
        } else {
        
            // Special [wpcf7.remote_ip] tag
            if ('wpcf7.remote_ip' == $matches[1])
                return preg_replace('/[^0-9a-f.:, ]/', '', $_SERVER['REMOTE_ADDR']);
        
            return $matches[0];
        }
    }

	function akismet($contact_form) {
		global $akismet_api_host, $akismet_api_port;
		
		if (! function_exists('akismet_http_post') || ! (get_option('wordpress_api_key') || $wpcom_api_key))
			return false;

		$akismet_ready = false;
		$author = $author_email = $author_url = $content = '';
		$fes = $this->form_elements($contact_form['form'], false);
		
		foreach ($fes as $fe) {
			if (! is_array($fe['options'])) continue;
			
			if (preg_grep('%^akismet:author$%', $fe['options']) && '' == $author) {
				$author = $_POST[$fe['name']];
				$akismet_ready = true;
			}
			if (preg_grep('%^akismet:author_email$%', $fe['options']) && '' == $author_email) {
				$author_email = $_POST[$fe['name']];
				$akismet_ready = true;
			}
			if (preg_grep('%^akismet:author_url$%', $fe['options']) && '' == $author_url) {
				$author_url = $_POST[$fe['name']];
				$akismet_ready = true;
			}
			
			if ('' != $content)
				$content .= "\n\n";
			$content .= $_POST[$fe['name']];
		}
		
		if (! $akismet_ready)
			return false;
		
		$c['blog'] = get_option('home');
		$c['user_ip'] = preg_replace('/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR']);
		$c['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		$c['referrer'] = $_SERVER['HTTP_REFERER'];
		$c['comment_type'] = 'contactform7';
		if ($permalink = get_permalink())
			$c['permalink'] = $permalink;
		if ('' != $author)
			$c['comment_author'] = $author;
		if ('' != $author_email)
			$c['comment_author_email'] = $author_email;
		if ('' != $author_url)
			$c['comment_author_url'] = $author_url;
		if ('' != $content)
			$c['comment_content'] = $content;
		
		$ignore = array('HTTP_COOKIE');
		
		foreach ($_SERVER as $key => $value)
			if (! in_array($key, (array) $ignore))
				$c["$key"] = $value;
		
		$query_string = '';
		foreach ($c as $key => $data)
			$query_string .= $key . '=' . urlencode(stripslashes($data)) . '&';
		
		$response = akismet_http_post($query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port);
		if ('true' == $response[1])
			return true;
		else
			return false;
	}

    function acceptance($contact_form) {
        $fes = $this->form_elements($contact_form['form'], false);
		
        $accepted = true;
        
		foreach ($fes as $fe) {
            if ('acceptance' != $fe['type'])
                continue;
            
            $invert = (bool) preg_grep('%^invert$%', $fe['options']);
            
            if ($invert && $_POST[$fe['name']] || ! $invert && ! $_POST[$fe['name']])
                $accepted = false;
        }
        
        return $accepted;
    }

	function set_initial() {
        $this->load_plugin_textdomain();
    
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
        global $wp_version;

        if (version_compare($wp_version, '2.6', '<')) // Using old WordPress
            load_plugin_textdomain('wpcf7', 'wp-content/plugins/contact-form-7/languages');
        else
            load_plugin_textdomain('wpcf7', 'wp-content/plugins/contact-form-7/languages', 'contact-form-7/languages');
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
    
    function upgrade($contact_form) {
        if (empty($contact_form))
            return $contact_form;

        $contact_form = $this->upgrade_160($contact_form);
        $contact_form = $this->upgrade_181($contact_form);
        $contact_form = $this->upgrade_190($contact_form);
        $contact_form = $this->upgrade_192($contact_form);
        return $contact_form;
    }

	function upgrade_160($contact_form) {
		if (! isset($contact_form['mail']['recipient']))
			$contact_form['mail']['recipient'] = $contact_form['options']['recipient'];
		return $contact_form;
	}
    
    function upgrade_181($contact_form) {
		if (! isset($contact_form['messages']))
            $contact_form['messages'] = array(
                'mail_sent_ok' => $this->default_message('mail_sent_ok'),
                'mail_sent_ng' => $this->default_message('mail_sent_ng'),
                'akismet_says_spam' => $this->default_message('akismet_says_spam'),
                'validation_error' => $this->default_message('validation_error'),
                'accept_terms' => $this->default_message('accept_terms'),
                'invalid_email' => $this->default_message('invalid_email'),
                'invalid_required' => $this->default_message('invalid_required'),
                'captcha_not_match' => $this->default_message('captcha_not_match')
            );
		return $contact_form;
    }
    
    function upgrade_190($contact_form) {
        if (! isset($contact_form['messages']) || ! is_array($contact_form['messages']))
            $contact_form['messages'] = array();

        if (! isset($contact_form['messages']['upload_failed']))
            $contact_form['messages']['upload_failed'] = $this->default_message('upload_failed');

        if (! isset($contact_form['messages']['upload_file_type_invalid']))
            $contact_form['messages']['upload_file_type_invalid'] = $this->default_message('upload_file_type_invalid');

        if (! isset($contact_form['messages']['upload_file_too_large']))
            $contact_form['messages']['upload_file_too_large'] = $this->default_message('upload_file_too_large');

		return $contact_form;
    }
    
    function upgrade_192($contact_form) {
        if (! isset($contact_form['messages']) || ! is_array($contact_form['messages']))
            $contact_form['messages'] = array();

        if (! isset($contact_form['messages']['quiz_answer_not_correct']))
            $contact_form['messages']['quiz_answer_not_correct'] = $this->default_message('quiz_answer_not_correct');

		return $contact_form;
    }

/* Admin panel */

    function admin_menu_parent() {
        global $wp_version;
        if (version_compare($wp_version, '2.7', '>='))
            return 'tools.php';
        else
            return 'edit.php';
    }

	function add_pages() {
        if (function_exists('admin_url')) {
            $base_url = admin_url($this->admin_menu_parent());
        } else {
            $base_url = get_option('siteurl') . '/wp-admin/' . $this->admin_menu_parent();
        }
		$page = str_replace('\\', '%5C', plugin_basename(__FILE__));
		$contact_forms = $this->contact_forms();
		
		if (isset($_POST['wpcf7-save']) && $this->has_edit_cap()) {
			$id = $_POST['wpcf7-id'];
			check_admin_referer('wpcf7-save_' . $id);
			
			$title = trim($_POST['wpcf7-title']);
			$form = trim($_POST['wpcf7-form']);
			$mail = array(
				'subject' => trim($_POST['wpcf7-mail-subject']),
				'sender' => trim($_POST['wpcf7-mail-sender']),
				'body' => trim($_POST['wpcf7-mail-body']),
				'recipient' => trim($_POST['wpcf7-mail-recipient']),
				'attachments' => trim($_POST['wpcf7-mail-attachments']),
				'use_html' => (1 == $_POST['wpcf7-mail-use-html']) ? true : false
				);
			$mail_2 = array(
				'active' => (1 == $_POST['wpcf7-mail-2-active']) ? true : false,
				'subject' => trim($_POST['wpcf7-mail-2-subject']),
				'sender' => trim($_POST['wpcf7-mail-2-sender']),
				'body' => trim($_POST['wpcf7-mail-2-body']),
				'recipient' => trim($_POST['wpcf7-mail-2-recipient']),
				'attachments' => trim($_POST['wpcf7-mail-2-attachments']),
				'use_html' => (1 == $_POST['wpcf7-mail-2-use-html']) ? true : false
				);
            $messages = array(
                'mail_sent_ok' => trim($_POST['wpcf7-message-mail-sent-ok']),
                'mail_sent_ng' => trim($_POST['wpcf7-message-mail-sent-ng']),
                'akismet_says_spam' => trim($_POST['wpcf7-message-akismet-says-spam']),
                'validation_error' => trim($_POST['wpcf7-message-validation-error']),
                'accept_terms' => trim($_POST['wpcf7-message-accept-terms']),
                'invalid_email' => trim($_POST['wpcf7-message-invalid-email']),
                'invalid_required' => trim($_POST['wpcf7-message-invalid-required']),
                'quiz_answer_not_correct' => trim($_POST['wpcf7-message-quiz-answer-not-correct']),
                'captcha_not_match' => trim($_POST['wpcf7-message-captcha-not-match']),
                'upload_failed' => trim($_POST['wpcf7-message-upload-failed']),
                'upload_file_type_invalid' => trim($_POST['wpcf7-message-upload-file-type-invalid']),
                'upload_file_too_large' => trim($_POST['wpcf7-message-upload-file-too-large'])
                );
			$options = array(
				'recipient' => trim($_POST['wpcf7-options-recipient']) // For backward compatibility.
				);
			
			if (array_key_exists($id, $contact_forms)) {
				$contact_forms[$id] = compact('title', 'form', 'mail', 'mail_2', 'messages', 'options');
				$redirect_to = $base_url . '?page=' . $page . '&contactform=' . $id . '&message=saved';
			} else {
				$key = (empty($contact_forms)) ? 1 : max(array_keys($contact_forms)) + 1;
				$contact_forms[$key] = compact('title', 'form', 'mail', 'mail_2', 'messages', 'options');
				$redirect_to = $base_url . '?page=' . $page . '&contactform=' . $key . '&message=created';
			}
			$this->update_contact_forms($contact_forms);
			
			wp_redirect($redirect_to);
			exit();
        } elseif (isset($_POST['wpcf7-copy']) && $this->has_edit_cap()) {
			$id = $_POST['wpcf7-id'];
			check_admin_referer('wpcf7-copy_' . $id);
            
            if (array_key_exists($id, $contact_forms)) {
                $key = max(array_keys($contact_forms)) + 1;
                $contact_forms[$key] = $contact_forms[$id];
                $contact_forms[$key]['title'] .= '_copy';
                $this->update_contact_forms($contact_forms);
                $redirect_to = $base_url . '?page=' . $page . '&contactform=' . $key . '&message=created';
            } else {
                $redirect_to = $base_url . '?page=' . $page . '&contactform=' . $id;
            }
            
            wp_redirect($redirect_to);
			exit();
		} elseif (isset($_POST['wpcf7-delete']) && $this->has_edit_cap()) {
			$id = $_POST['wpcf7-id'];
			check_admin_referer('wpcf7-delete_' . $id);
			
			unset($contact_forms[$id]);
			$this->update_contact_forms($contact_forms);
			
			wp_redirect($base_url . '?page=' . $page . '&message=deleted');
			exit();
		}
	
		add_management_page(__('Contact Form 7', 'wpcf7'), __('Contact Form 7', 'wpcf7'), wpcf7_read_capability(), __FILE__, array(&$this, 'management_page'));
	}
	
	function admin_head() {
		global $plugin_page, $wp_version;
		
		if (isset($plugin_page) && $plugin_page == plugin_basename(__FILE__)) {
        
            $admin_stylesheet_url = WPCF7_PLUGIN_URL . '/admin-stylesheet.css';
            $javascript_url = WPCF7_PLUGIN_URL . '/wpcf7-admin.js';

			echo '<link rel="stylesheet" href="' . $admin_stylesheet_url . '" type="text/css" />';
            
            if ('rtl' == get_bloginfo('text_direction')) {
                $admin_stylesheet_rtl_url = WPCF7_PLUGIN_URL . '/admin-stylesheet-rtl.css';
                echo '<link rel="stylesheet" href="' . $admin_stylesheet_rtl_url . '" type="text/css" />';
            }

?>
<script type="text/javascript">
//<![CDATA[
var _wpcf7 = {
	l10n: {
		optional: "<?php echo js_escape(__('optional', 'wpcf7')); ?>",
		generateTag: "<?php echo js_escape(__('Generate Tag', 'wpcf7')); ?>",
		textField: "<?php echo js_escape(__('Text field', 'wpcf7')); ?>",
		emailField: "<?php echo js_escape(__('Email field', 'wpcf7')); ?>",
		textArea: "<?php echo js_escape(__('Text area', 'wpcf7')); ?>",
		menu: "<?php echo js_escape(__('Drop-down menu', 'wpcf7')); ?>",
		checkboxes: "<?php echo js_escape(__('Checkboxes', 'wpcf7')); ?>",
		radioButtons: "<?php echo js_escape(__('Radio buttons', 'wpcf7')); ?>",
		acceptance: "<?php echo js_escape(__('Acceptance', 'wpcf7')); ?>",
		isAcceptanceDefaultOn: "<?php echo js_escape(__("Make this checkbox checked by default?", 'wpcf7')); ?>",
		isAcceptanceInvert: "<?php echo js_escape(__("Make this checkbox work inversely?", 'wpcf7')); ?>",
		isAcceptanceInvertMeans: "<?php echo js_escape(__("* That means visitor who accepts the term unchecks it.", 'wpcf7')); ?>",
		captcha: "<?php echo js_escape(__('CAPTCHA', 'wpcf7')); ?>",
		quiz: "<?php echo js_escape(__('Quiz', 'wpcf7')); ?>",
		quizzes: "<?php echo js_escape(__('Quizzes', 'wpcf7')); ?>",
		quizFormatDesc: "<?php echo js_escape(__("* quiz|answer (e.g. 1+1=?|2)", 'wpcf7')); ?>",
		fileUpload: "<?php echo js_escape(__('File upload', 'wpcf7')); ?>",
		bytes: "<?php echo js_escape(__('bytes', 'wpcf7')); ?>",
		submit: "<?php echo js_escape(__('Submit button', 'wpcf7')); ?>",
		tagName: "<?php echo js_escape(__('Name', 'wpcf7')); ?>",
		isRequiredField: "<?php echo js_escape(__('Required field?', 'wpcf7')); ?>",
		allowsMultipleSelections: "<?php echo js_escape(__('Allow multiple selections?', 'wpcf7')); ?>",
		insertFirstBlankOption: "<?php echo js_escape(__('Insert a blank item as the first option?', 'wpcf7')); ?>",
		makeCheckboxesExclusive: "<?php echo js_escape(__('Make checkboxes exclusive?', 'wpcf7')); ?>",
		menuChoices: "<?php echo js_escape(__('Choices', 'wpcf7')); ?>",
		label: "<?php echo js_escape(__('Label', 'wpcf7')); ?>",
		defaultValue: "<?php echo js_escape(__('Default value', 'wpcf7')); ?>",
		akismet: "<?php echo js_escape(__('Akismet', 'wpcf7')); ?>",
		akismetAuthor: "<?php echo js_escape(__("This field requires author's name", 'wpcf7')); ?>",
		akismetAuthorUrl: "<?php echo js_escape(__("This field requires author's URL", 'wpcf7')); ?>",
		akismetAuthorEmail: "<?php echo js_escape(__("This field requires author's email address", 'wpcf7')); ?>",
		generatedTag: "<?php echo js_escape(__("Copy this code and paste it into the form left.", 'wpcf7')); ?>",
		fgColor: "<?php echo js_escape(__("Foreground color", 'wpcf7')); ?>",
		bgColor: "<?php echo js_escape(__("Background color", 'wpcf7')); ?>",
		imageSize: "<?php echo js_escape(__("Image size", 'wpcf7')); ?>",
		imageSizeSmall: "<?php echo js_escape(__("Small", 'wpcf7')); ?>",
		imageSizeMedium: "<?php echo js_escape(__("Medium", 'wpcf7')); ?>",
		imageSizeLarge: "<?php echo js_escape(__("Large", 'wpcf7')); ?>",
		imageSettings: "<?php echo js_escape(__("Image settings", 'wpcf7')); ?>",
		inputFieldSettings: "<?php echo js_escape(__("Input field settings", 'wpcf7')); ?>",
		tagForImage: "<?php echo js_escape(__("For image", 'wpcf7')); ?>",
		tagForInputField: "<?php echo js_escape(__("For input field", 'wpcf7')); ?>",
		oneChoicePerLine: "<?php echo js_escape(__("* One choice per line.", 'wpcf7')); ?>",
		show: "<?php echo js_escape(__("Show", 'wpcf7')); ?>",
		hide: "<?php echo js_escape(__("Hide", 'wpcf7')); ?>",
		fileSizeLimit: "<?php echo js_escape(__("File size limit", 'wpcf7')); ?>",
		acceptableFileTypes: "<?php echo js_escape(__("Acceptable file types", 'wpcf7')); ?>",
        needReallySimpleCaptcha: "<?php echo js_escape(__("Note: To use CAPTCHA, you need Really Simple CAPTCHA plugin installed.", 'wpcf7')); ?>"
	},
    captchaMod: <?php echo (class_exists('ReallySimpleCaptcha')) ? 'true' : 'false' ?>
};
//]]>
</script>
<script type='text/javascript' src='<?php echo $javascript_url; ?>'></script>
<?php
		}
	}
    
    function has_edit_cap() {
        return current_user_can(wpcf7_read_write_capability());
    }
	
	function management_page() {
        global $wp_version;
    
        if (function_exists('admin_url')) {
            $base_url = admin_url($this->admin_menu_parent());
        } else {
            $base_url = get_option('siteurl') . '/wp-admin/' . $this->admin_menu_parent();
        }
		$page = plugin_basename(__FILE__);
		
		switch ($_GET['message']) {
			case 'created':
				$updated_message = __('Contact form created.', 'wpcf7');
				break;
			case 'saved':
				$updated_message = __('Contact form saved.', 'wpcf7');
				break;
			case 'deleted':
				$updated_message = __('Contact form deleted.', 'wpcf7');
				break;
		}
        
		$contact_forms = $this->contact_forms();
		
		$id = $_POST['wpcf7-id'];
		
		if ('new' == $_GET['contactform']) {
			$unsaved = true;
			$current = -1;
			$cf = $this->default_pack(__('Untitled', 'wpcf7'), true);
		} elseif (array_key_exists($_GET['contactform'], $contact_forms)) {
			$current = (int) $_GET['contactform'];
			$cf = stripslashes_deep($contact_forms[$current]);
			$cf = $this->upgrade($cf);
		} else {
            $current = (int) array_shift(array_keys($contact_forms));
            $cf = stripslashes_deep($contact_forms[$current]);
			$cf = $this->upgrade($cf);
		}

        require_once WPCF7_PLUGIN_DIR . '/admin-panel.php';
	}

	function default_pack($title, $initial = false) {
		$cf = array('title' => $title,
			'form' => $this->default_form_template(),
			'mail' => $this->default_mail_template(),
			'mail_2' => $this->default_mail_2_template(),
            'messages' => $this->default_messages_template(),
			'options' => $this->default_options_template());
		if ($initial)
			$cf['initial'] = true;
		return $cf;
	}

	function default_form_template() {
		$template .= '<p>' . __('Your Name', 'wpcf7') . ' ' . __('(required)', 'wpcf7') . '<br />' . "\n";
		$template .= '    [text* your-name] </p>' . "\n\n";
		$template .= '<p>' . __('Your Email', 'wpcf7') . ' ' . __('(required)', 'wpcf7') . '<br />' . "\n";
		$template .= '    [email* your-email] </p>' . "\n\n";
		$template .= '<p>' . __('Subject', 'wpcf7') . '<br />' . "\n";
		$template .= '    [text your-subject] </p>' . "\n\n";
		$template .= '<p>' . __('Your Message', 'wpcf7') . '<br />' . "\n";
		$template .= '    [textarea your-message] </p>' . "\n\n";
		$template .= '<p>[submit "' . __('Send', 'wpcf7') . '"]</p>';
		return $template;
	}
	
	function default_mail_template() {
		$subject = '[your-subject]';
		$sender = '[your-name] <[your-email]>';
		$body = '[your-message]';
		$recipient = get_option('admin_email');
		return compact('subject', 'sender', 'body', 'recipient');
	}

	function default_mail_2_template() {
		$active = false;
		$subject = '[your-subject]';
		$sender = '[your-name] <[your-email]>';
		$body = '[your-message]';
		$recipient = '[your-email]';
		return compact('active', 'subject', 'sender', 'body', 'recipient');
	}

    function default_messages_template() {
        $mail_sent_ok = $this->default_message('mail_sent_ok');
        $mail_sent_ng = $this->default_message('mail_sent_ng');
        $akismet_says_spam = $this->default_message('akismet_says_spam');
        $validation_error = $this->default_message('validation_error');
        $accept_terms = $this->default_message('accept_terms');
        $invalid_email = $this->default_message('invalid_email');
        $invalid_required = $this->default_message('invalid_required');
        $quiz_answer_not_correct = $this->default_message('quiz_answer_not_correct');
        $captcha_not_match = $this->default_message('captcha_not_match');
        $upload_failed = $this->default_message('upload_failed');
        $upload_file_type_invalid = $this->default_message('upload_file_type_invalid');
        $upload_file_too_large = $this->default_message('upload_file_too_large');
		return compact('mail_sent_ok', 'mail_sent_ng', 'akismet_says_spam',
            'validation_error', 'accept_terms', 'invalid_email', 'invalid_required', 'quiz_answer_not_correct',
            'captcha_not_match', 'upload_failed', 'upload_file_type_invalid', 'upload_file_too_large');
    }

	function default_options_template() {
		$recipient = get_option('admin_email'); // For backward compatibility.
		return compact('recipient');
	}
	
    function message($contact_form, $status) {
        if (! isset($contact_form['messages']) || ! isset($contact_form['messages'][$status]))
            return $this->default_message($status);
        
        return $contact_form['messages'][$status];
    }
    
	function default_message($status) {
		switch ($status) {
			case 'mail_sent_ok':
				return __('Your message was sent successfully. Thanks.', 'wpcf7');
			case 'mail_sent_ng':
				return __('Failed to send your message. Please try later or contact administrator by other way.', 'wpcf7');
			case 'akismet_says_spam':
				return __('Failed to send your message. Please try later or contact administrator by other way.', 'wpcf7');
			case 'validation_error':
				return __('Validation errors occurred. Please confirm the fields and submit it again.', 'wpcf7');
            case 'accept_terms':
                return __('Please accept the terms to proceed.', 'wpcf7');
			case 'invalid_email':
				return __('Email address seems invalid.', 'wpcf7');
			case 'invalid_required':
				return __('Please fill the required field.', 'wpcf7');
			case 'captcha_not_match':
				return __('Your entered code is incorrect.', 'wpcf7');
			case 'quiz_answer_not_correct':
				return __('Your answer is not correct.', 'wpcf7');
			case 'upload_failed':
				return __('Failed to upload file.', 'wpcf7');
			case 'upload_file_type_invalid':
				return __('This file type is not allowed.', 'wpcf7');
			case 'upload_file_too_large':
				return __('This file is too large.', 'wpcf7');
		}
	}

	function process_nonajax_submitting() {
		if (! isset($_POST['_wpcf7']))
			return;
		
		$id = (int) $_POST['_wpcf7'];
		$contact_forms = $this->contact_forms();
		if ($cf = $contact_forms[$id]) {
			$cf = stripslashes_deep($cf);
			$validation = $this->validate($cf);
            
            $handled_uploads = $this->handle_uploads($cf);
            if (! $handled_uploads['validation']['valid'])
                $validation['valid'] = false;
            $validation['reason'] = array_merge($validation['reason'], $handled_uploads['validation']['reason']);
            
			if (! $validation['valid']) {
				$_POST['_wpcf7_validation_errors'] = array('id' => $id, 'messages' => $validation['reason']);
			} elseif (! $this->acceptance($cf)) { // Not accepted terms
				$_POST['_wpcf7_mail_sent'] = array('id' => $id, 'ok' => false, 'message' => $this->message($cf, 'accept_terms'));
			} elseif ($this->akismet($cf)) { // Spam!
				$_POST['_wpcf7_mail_sent'] = array('id' => $id, 'ok' => false, 'message' => $this->message($cf, 'akismet_says_spam'), 'spam' => true);
			} elseif ($this->mail($cf, $handled_uploads['files'])) {
				$_POST['_wpcf7_mail_sent'] = array('id' => $id, 'ok' => true, 'message' => $this->message($cf, 'mail_sent_ok'));
			} else {
				$_POST['_wpcf7_mail_sent'] = array('id' => $id, 'ok' => false, 'message' => $this->message($cf, 'mail_sent_ng'));
			}
            
            // remove upload files
            foreach ($handled_uploads['files'] as $name => $path) {
                @unlink($path);
            }
		}
	}

/* Post content filtering */

	var $processing_unit_tag;
	var $processing_within;
	var $unit_count;
	var $widget_count;
	
	function the_content_filter($content) {
		$this->processing_within = 'p' . get_the_ID();
		$this->unit_count = 0;

        return $content;
	}
	
	function widget_text_filter($content) {
		$this->widget_count += 1;
		$this->processing_within = 'w' . $this->widget_count;
		$this->unit_count = 0;

		$regex = '/\[\s*contact-form\s+(\d+(?:\s+.*)?)\]/';
		return preg_replace_callback($regex, array(&$this, 'widget_text_filter_callback'), $content);
	}
    
    function widget_text_filter_callback($matches) {
        return $this->contact_form_tag_func($matches[1]);
    }

    function contact_form_tag_func($atts) {
        if (is_string($atts))
            $atts = explode(' ', $atts, 2);

        $atts = (array) $atts;

        $id = (int) array_shift($atts);
    
		$contact_forms = $this->contact_forms();

		if (! ($cf = $contact_forms[$id]))
            return '[contact-form 404 "Not Found"]';
		
		$cf = stripslashes_deep($cf);

		$this->unit_count += 1;
		$unit_tag = 'wpcf7-f' . $id . '-' . $this->processing_within . '-o' . $this->unit_count;
		$this->processing_unit_tag = $unit_tag;

		$form = '<div class="wpcf7" id="' . $unit_tag . '">';
		
		$url = parse_url($_SERVER['REQUEST_URI']);
		$url = $url['path'] . (empty($url['query']) ? '' : '?' . $url['query']) . '#' . $unit_tag;
        
        $form_elements = $this->form_elements($cf['form'], false);
        $multipart = false;
        foreach ($form_elements as $form_element) {
            if ('file' == $form_element['type']) {
                $multipart = true;
                break;
            }
        }
        $enctype = $multipart ? ' enctype="multipart/form-data"' : '';
		
		$form .= '<form action="' . $url . '" method="post" class="wpcf7-form"' . $enctype . '>';
        $form .= '<div style="display: none;">';
		$form .= '<input type="hidden" name="_wpcf7" value="' . $id . '" />';
		$form .= '<input type="hidden" name="_wpcf7_version" value="' . wpcf7_version() . '" />';
		$form .= '<input type="hidden" name="_wpcf7_unit_tag" value="' . $unit_tag . '" />';
        $form .= '</div>';
		$form .= $this->form_elements($cf['form']);
		$form .= '</form>';
		
		// Post response output for non-AJAX
		$class = 'wpcf7-response-output';
		
		if ($this->processing_unit_tag == $_POST['_wpcf7_unit_tag']) {
			if (isset($_POST['_wpcf7_mail_sent']) && $_POST['_wpcf7_mail_sent']['id'] == $id) {
				if ($_POST['_wpcf7_mail_sent']['ok']) {
					$class .= ' wpcf7-mail-sent-ok';
					$content = $_POST['_wpcf7_mail_sent']['message'];
				} else {
					$class .= ' wpcf7-mail-sent-ng';
					if ($_POST['_wpcf7_mail_sent']['spam'])
						$class .= ' wpcf7-spam-blocked';
					$content = $_POST['_wpcf7_mail_sent']['message'];
				}
			} elseif (isset($_POST['_wpcf7_validation_errors']) && $_POST['_wpcf7_validation_errors']['id'] == $id) {
				$class .= ' wpcf7-validation-errors';
				$content = $this->message($cf, 'validation_error');
			}
		}
		
		$class = ' class="' . $class . '"';
		
		$form .= '<div' . $class . '>' . $content . '</div>';
		
		$form .= '</div>';
		
		$this->processing_unit_tag = null;
        
        if (WPCF7_AUTOP)
            $form = $this->wpautop_substitute($form);
        
		return $form;
    }

    function wpautop_substitute($pee, $br = 1) {
        $pee = $pee . "\n"; // just to make things a little easier, pad the end
        $pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
        // Space things out a little
        $allblocks = '(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr)';
        $pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
        $pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
        $pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
        if ( strpos($pee, '<object') !== false ) {
            $pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
            $pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
        }
        $pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
        // make paragraphs, including one at the end
        $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
        $pee = '';
        foreach ( $pees as $tinkle )
            $pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
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
            $pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', create_function('$matches', 'return str_replace("\n", "<WPPreserveNewline />", $matches[0]);'), $pee);
            $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
            $pee = str_replace('<WPPreserveNewline />', "\n", $pee);
        }
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
        $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
        if (strpos($pee, '<pre') !== false)
            $pee = preg_replace_callback('!(<pre.*?>)(.*?)</pre>!is', 'clean_pre', $pee );
        $pee = preg_replace( "|\n</p>$|", '</p>', $pee );
        
        if (function_exists('get_shortcode_regex'))
            $pee = preg_replace('/<p>\s*?(' . get_shortcode_regex() . ')\s*<\/p>/s', '$1', $pee); // don't auto-p wrap shortcodes that stand alone
    
        return $pee;
    }
    
	function validate($contact_form) {
		$fes = $this->form_elements($contact_form['form'], false);
		$valid = true;
		$reason = array();

		foreach ($fes as $fe) {
			$type = $fe['type'];
			$name = $fe['name'];
            $values = $fe['values'];
            $raw_values = $fe['raw_values'];
            
            // Before validation corrections
            if (preg_match('/^(?:text|email|captchar|textarea)[*]?$/', $type))
                $_POST[$name] = (string) $_POST[$name];
            
            if (preg_match('/^(?:text|email)[*]?$/', $type))
                $_POST[$name] = trim(strtr($_POST[$name], "\n", " "));
            
			if (preg_match('/^(?:select|checkbox|radio)[*]?$/', $type)) {
                if (is_array($_POST[$name])) {
                    foreach ($_POST[$name] as $key => $value) {
                        $value = stripslashes($value);
                        if (! in_array($value, (array) $values)) // Not in given choices.
                            unset($_POST[$name][$key]);
                    }
                } else {
                    $value = stripslashes($_POST[$name]);
                    if (! in_array($value, (array) $values)) //  Not in given choices.
                        $_POST[$name] = '';
                }
            }
            
            if ('acceptance' == $type)
                $_POST[$name] = $_POST[$name] ? 1 : 0;
            
			// Required item (*)
			if (preg_match('/^(?:text|textarea)[*]$/', $type)) {
                if (! isset($_POST[$name]) || '' == $_POST[$name]) {
					$valid = false;
					$reason[$name] = $this->message($contact_form, 'invalid_required');
				}
			}
            
            if ('checkbox*' == $type) {
                if (empty($_POST[$name])) {
					$valid = false;
					$reason[$name] = $this->message($contact_form, 'invalid_required');
				}
            }
            
            if ('select*' == $type) {
                if (empty($_POST[$name]) ||
                        ! is_array($_POST[$name]) && '---' == $_POST[$name] ||
                        is_array($_POST[$name]) && 1 == count($_POST[$name]) && '---' == $_POST[$name][0]) {
                    $valid = false;
					$reason[$name] = $this->message($contact_form, 'invalid_required');
                }
			}

			if (preg_match('/^email[*]?$/', $type)) {
				if ('*' == substr($type, -1) && (! isset($_POST[$name]) || '' == $_POST[$name])) {
					$valid = false;
					$reason[$name] = $this->message($contact_form, 'invalid_required');
				} elseif (isset($_POST[$name]) && '' != $_POST[$name] && ! is_email($_POST[$name])) {
					$valid = false;
					$reason[$name] = $this->message($contact_form, 'invalid_email');
				}
			}

			if (preg_match('/^captchar$/', $type)) {
				$captchac = '_wpcf7_captcha_challenge_' . $name;
				if (! $this->check_captcha($_POST[$captchac], $_POST[$name])) {
					$valid = false;
					$reason[$name] = $this->message($contact_form, 'captcha_not_match');
				}
				$this->remove_captcha($_POST[$captchac]);
			}
            
            if ('quiz' == $type) {
                $answer = $this->canonicalize($_POST[$name]);
                $answer_hash = wp_hash($answer, 'wpcf7_quiz');
                $expected_hash = $_POST['_wpcf7_quiz_answer_' . $name];
                if ($answer_hash != $expected_hash) {
                    $valid = false;
                    $reason[$name] = $this->message($contact_form, 'quiz_answer_not_correct');
                }
            }
		}
		return compact('valid', 'reason');
	}

	function refill_captcha($contact_form) {
		$fes = $this->form_elements($contact_form['form'], false);
		$refill = array();
		
		foreach ($fes as $fe) {
			$type = $fe['type'];
			$name = $fe['name'];
			$options = $fe['options'];
			if ('captchac' == $type) {
				$op = $this->captchac_options($options);
				if ($filename = $this->generate_captcha($op)) {
					$captcha_url = trailingslashit($this->captcha_tmp_url()) . $filename;
					$refill[$name] = $captcha_url;
                }
			}
		}
		return $refill;
	}

    function refill_quiz($contact_form) {
		$fes = $this->form_elements($contact_form['form'], false);
		$refill = array();
		
		foreach ($fes as $fe) {
			$type = $fe['type'];
			$name = $fe['name'];
            $values = $fe['values'];
            $raw_values = $fe['raw_values'];
            
			if ('quiz' != $type)
                continue;
            
            if (count($values) == 0)
                continue;

            if (count($values) == 1)
                $question = $values[0];
            else
                $question = $values[array_rand($values)];
            
            $pipes = $this->get_pipes($raw_values);
            $answer = $this->pipe($pipes, $question);
            $answer = $this->canonicalize($answer);
                
            $refill[$name] = array($question, wp_hash($answer, 'wpcf7_quiz'));
		}
        
		return $refill;
	}

	function wp_head() {
		$stylesheet_url = WPCF7_PLUGIN_URL . '/stylesheet.css';
		echo '<link rel="stylesheet" href="' . $stylesheet_url . '" type="text/css" />';
        
        if ('rtl' == get_bloginfo('text_direction')) {
            $stylesheet_rtl_url = WPCF7_PLUGIN_URL . '/stylesheet-rtl.css';
            echo '<link rel="stylesheet" href="' . $stylesheet_rtl_url . '" type="text/css" />';
        }
		
		$javascript_url = WPCF7_PLUGIN_URL . '/contact-form-7.js';
?>
<script type='text/javascript' src='<?php echo $javascript_url; ?>'></script>
<?php
	}
	
	function load_js() {
		global $pagenow;
        if (is_admin() && $this->admin_menu_parent() == $pagenow && false !== strpos($_GET['page'], 'contact-form-7'))
			wp_enqueue_script('jquery');
		if (! is_admin())
			wp_enqueue_script('jquery-form');
	}

/* Processing form element placeholders */

	function form_elements($form, $replace = true) {
		$types = 'text[*]?|email[*]?|textarea[*]?|select[*]?|checkbox[*]?|radio|acceptance|captchac|captchar|file[*]?|quiz';
		$regex = '%\[\s*(' . $types . ')(\s+[a-zA-Z][0-9a-zA-Z:._-]*)([-0-9a-zA-Z:#_/|\s]*)?((?:\s*(?:"[^"]*"|\'[^\']*\'))*)?\s*\]%';
		$submit_regex = '%\[\s*submit(\s[-0-9a-zA-Z:#_/\s]*)?(\s+(?:"[^"]*"|\'[^\']*\'))?\s*\]%';
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
		extract((array) $this->form_element_parse($matches)); // $type, $name, $options, $values, $raw_values
		
		if ($this->processing_unit_tag == $_POST['_wpcf7_unit_tag']) {
			$validation_error = $_POST['_wpcf7_validation_errors']['messages'][$name];
			$validation_error = $validation_error ? '<span class="wpcf7-not-valid-tip-no-ajax">' . $validation_error . '</span>' : '';
		} else {
			$validation_error = '';
		}
		
		$atts = '';
        $options = (array) $options;
        
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
        
        if (preg_match('/^checkbox[*]?$/', $type))
            $class_att .= ' wpcf7-checkbox';
        
        if ('radio' == $type)
            $class_att .= ' wpcf7-radio';
        
        if (preg_match('/^captchac$/', $type))
            $class_att .= ' wpcf7-captcha-' . $name;
        
        if ('acceptance' == $type) {
            $class_att .= ' wpcf7-acceptance';
            if (preg_grep('%^invert$%', $options))
                $class_att .= ' wpcf7-invert';
        }
        
        if ($class_att)
            $atts .= ' class="' . trim($class_att) . '"';
		
		// Value.
		if ($this->processing_unit_tag == $_POST['_wpcf7_unit_tag']) {
			if (isset($_POST['_wpcf7_mail_sent']) && $_POST['_wpcf7_mail_sent']['ok'])
				$value = '';
			elseif ('captchar' == $type)
				$value = '';
			else
				$value = $_POST[$name];
		} else {
			$value = $values[0];
		}
        
        // Default selected/checked for select/checkbox/radio
        if (preg_match('/^(?:select|checkbox|radio)[*]?$/', $type)) {
            $scr_defaults = array_values(preg_grep('/^default:/', $options));
            preg_match('/^default:([0-9_]+)$/', $scr_defaults[0], $scr_default_matches);
            $scr_default = explode('_', $scr_default_matches[1]);
        }
		
		switch ($type) {
			case 'text':
			case 'text*':
			case 'email':
			case 'email*':
			case 'captchar':
				if (is_array($options)) {
					$size_maxlength_array = preg_grep('%^[0-9]*[/x][0-9]*$%', $options);
					if ($size_maxlength = array_shift($size_maxlength_array)) {
						preg_match('%^([0-9]*)[/x]([0-9]*)$%', $size_maxlength, $sm_matches);
						if ($size = (int) $sm_matches[1])
							$atts .= ' size="' . $size . '"';
                        else
                            $atts .= ' size="40"';
						if ($maxlength = (int) $sm_matches[2])
							$atts .= ' maxlength="' . $maxlength . '"';
					} else {
                        $atts .= ' size="40"';
                    }
				}
				$html = '<input type="text" name="' . $name . '" value="' . attribute_escape($value) . '"' . $atts . ' />';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'textarea':
			case 'textarea*':
				if (is_array($options)) {
					$cols_rows_array = preg_grep('%^[0-9]*[x/][0-9]*$%', $options);
					if ($cols_rows = array_shift($cols_rows_array)) {
						preg_match('%^([0-9]*)[x/]([0-9]*)$%', $cols_rows, $cr_matches);
						if ($cols = (int) $cr_matches[1])
							$atts .= ' cols="' . $cols . '"';
                        else
                            $atts .= ' cols="40"';
						if ($rows = (int) $cr_matches[2])
							$atts .= ' rows="' . $rows . '"';
                        else
                            $atts .= ' rows="10"';
					} else {
                        $atts .= ' cols="40" rows="10"';
                    }
				}
				$html = '<textarea name="' . $name . '"' . $atts . '>' . $value . '</textarea>';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'select':
			case 'select*':
                $multiple = (preg_grep('%^multiple$%', $options)) ? true : false;
                $include_blank = preg_grep('%^include_blank$%', $options);
                
				if ($empty_select = empty($values) || $include_blank)
					array_unshift($values, '---');
                
				$html = '';
                foreach ($values as $key => $value) {
                    $selected = '';
                    if (! $empty_select && in_array($key + 1, (array) $scr_default))
                        $selected = ' selected="selected"';
                    if ($this->processing_unit_tag == $_POST['_wpcf7_unit_tag'] && (
                            $multiple && in_array($value, (array) $_POST[$name]) ||
                            ! $multiple && $_POST[$name] == $value))
                        $selected = ' selected="selected"';
					$html .= '<option value="' . attribute_escape($value) . '"' . $selected . '>' . $value . '</option>';
                }
                
                if ($multiple)
                    $atts .= ' multiple="multiple"';
                
				$html = '<select name="' . $name . ($multiple ? '[]' : '') . '"' . $atts . '>' . $html . '</select>';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
            case 'checkbox':
            case 'checkbox*':
            case 'radio':
                $multiple = (preg_match('/^checkbox[*]?$/', $type) && ! preg_grep('%^exclusive$%', $options)) ? true : false;
                $html = '';
                
                if (preg_match('/^checkbox[*]?$/', $type) && ! $multiple)
                    $onclick = ' onclick="wpcf7ExclusiveCheckbox(this);"';
                
                $input_type = rtrim($type, '*');
                
                foreach ($values as $key => $value) {
                    $checked = '';
                    if (in_array($key + 1, (array) $scr_default))
                        $checked = ' checked="checked"';
                    if ($this->processing_unit_tag == $_POST['_wpcf7_unit_tag'] && (
                            $multiple && in_array($value, (array) $_POST[$name]) ||
                            ! $multiple && $_POST[$name] == $value))
                        $checked = ' checked="checked"';
                    if (preg_grep('%^label[_-]?first$%', $options)) { // put label first, input last
                        $item = '<span class="wpcf7-list-item-label">' . $value . '</span>&nbsp;';
                        $item .= '<input type="' . $input_type . '" name="' . $name . ($multiple ? '[]' : '') . '" value="' . attribute_escape($value) . '"' . $checked . $onclick . ' />';
                    } else {
                        $item = '<input type="' . $input_type . '" name="' . $name . ($multiple ? '[]' : '') . '" value="' . attribute_escape($value) . '"' . $checked . $onclick . ' />';
                        $item .= '&nbsp;<span class="wpcf7-list-item-label">' . $value . '</span>';
                    }
                    $item = '<span class="wpcf7-list-item">' . $item . '</span>';
                    $html .= $item;
                }
                
                $html = '<span' . $atts . '>' . $html . '</span>';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
            case 'quiz':
                if (count($raw_values) == 0 && count($values) == 0) { // default quiz
                    $raw_values[] = '1+1=?|2';
                    $values[] = '1+1=?';
                }
                    
                $pipes = $this->get_pipes($raw_values);
                
                if (count($values) == 0) {
                    break;
                } elseif (count($values) == 1) {
                    $value = $values[0];
                } else {
                    $value = $values[array_rand($values)];
                }
                
                $answer = $this->pipe($pipes, $value);
                $answer = $this->canonicalize($answer);
                
				if (is_array($options)) {
					$size_maxlength_array = preg_grep('%^[0-9]*[/x][0-9]*$%', $options);
					if ($size_maxlength = array_shift($size_maxlength_array)) {
						preg_match('%^([0-9]*)[/x]([0-9]*)$%', $size_maxlength, $sm_matches);
						if ($size = (int) $sm_matches[1])
							$atts .= ' size="' . $size . '"';
                        else
                            $atts .= ' size="40"';
						if ($maxlength = (int) $sm_matches[2])
							$atts .= ' maxlength="' . $maxlength . '"';
					} else {
                        $atts .= ' size="40"';
                    }
				}
                
                $html = '<span class="wpcf7-quiz-label">' . $value . '</span>&nbsp;';
                $html .= '<input type="text" name="' . $name . '"' . $atts . ' />';
                $html .= '<input type="hidden" name="_wpcf7_quiz_answer_' . $name . '" value="' . wp_hash($answer, 'wpcf7_quiz') . '" />';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
                break;
            case 'acceptance':
                $invert = (bool) preg_grep('%^invert$%', $options);
                $default = (bool) preg_grep('%^default:on$%', $options);
                
                $onclick = ' onclick="wpcf7ToggleSubmit(this.form);"';
                $checked = $default ? ' checked="checked"' : '';
                $html = '<input type="checkbox" name="' . $name . '" value="1"' . $atts . $onclick . $checked . ' />';
                return $html;
                break;
			case 'captchac':
                if (! class_exists('ReallySimpleCaptcha')) {
                    return '<em>' . __('To use CAPTCHA, you need <a href="http://wordpress.org/extend/plugins/really-simple-captcha/">Really Simple CAPTCHA</a> plugin installed.', 'wpcf7') . '</em>';
                    break;
                }

				$op = array();
				// Default
				$op['img_size'] = array(72, 24);
				$op['base'] = array(6, 18);
				$op['font_size'] = 14;
				$op['font_char_width'] = 15;
				
				$op = array_merge($op, $this->captchac_options($options));
				
				if (! $filename = $this->generate_captcha($op)) {
					return '';
					break;
				}
				if (is_array($op['img_size']))
					$atts .= ' width="' . $op['img_size'][0] . '" height="' . $op['img_size'][1] . '"';
				$captcha_url = trailingslashit($this->captcha_tmp_url()) . $filename;
				$html = '<img alt="captcha" src="' . $captcha_url . '"' . $atts . ' />';
				$ref = substr($filename, 0, strrpos($filename, '.'));
				$html = '<input type="hidden" name="_wpcf7_captcha_challenge_' . $name . '" value="' . $ref . '" />' . $html;
				return $html;
				break;
            case 'file':
            case 'file*':
                $html = '<input type="file" name="' . $name . '"' . $atts . ' value="1" />';
                $html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
                return $html;
                break;
		}
	}

	function submit_replace_callback($matches) {
        $atts = '';
        $options = preg_split('/[\s]+/', trim($matches[1]));
        
        $id_array = preg_grep('%^id:[-0-9a-zA-Z_]+$%', $options);
        if ($id = array_shift($id_array)) {
            preg_match('%^id:([-0-9a-zA-Z_]+)$%', $id, $id_matches);
            if ($id = $id_matches[1])
                $atts .= ' id="' . $id . '"';
        }
        
        $class_att = '';
        $class_array = preg_grep('%^class:[-0-9a-zA-Z_]+$%', $options);
        foreach ($class_array as $class) {
            preg_match('%^class:([-0-9a-zA-Z_]+)$%', $class, $class_matches);
            if ($class = $class_matches[1])
                $class_att .= ' ' . $class;
        } 
        
        if ($class_att)
            $atts .= ' class="' . trim($class_att) . '"';
        
		if ($matches[2])
			$value = $this->strip_quote($matches[2]);
		if (empty($value))
			$value = __('Send', 'wpcf7');
		$ajax_loader_image_url = WPCF7_PLUGIN_URL . '/images/ajax-loader.gif';
        
        $html = '<input type="submit" value="' . $value . '"' . $atts . ' />';
        $html .= ' <img class="ajax-loader" style="visibility: hidden;" alt="ajax loader" src="' . $ajax_loader_image_url . '" />';
		return $html;
	}
    
    function canonicalize($text) {
        if (function_exists('mb_convert_kana') && 'UTF-8' == get_option('blog_charset'))
            $text = mb_convert_kana($text, 'asKV', 'UTF-8');

        $text = strtolower($text);
        $text = trim($text);
        return $text;
    }

	function form_element_parse($element) {
		$type = trim($element[1]);
		$name = trim($element[2]);
		$options = preg_split('/[\s]+/', trim($element[3]));
		
		preg_match_all('/"[^"]*"|\'[^\']*\'/', $element[4], $matches);
		$raw_values = $this->strip_quote_deep($matches[0]);
        
        if (WPCF7_USE_PIPE && preg_match('/^(select[*]?|checkbox[*]?|radio)$/', $type) || 'quiz' == $type) {
            $pipes = $this->get_pipes($raw_values);
            $values = $this->get_pipe_ins($pipes);
        } else {
            $values =& $raw_values;
        }
        
		return compact('type', 'name', 'options', 'values', 'raw_values');
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

    function init_uploads() {
        $dir = $this->upload_tmp_dir();
        wp_mkdir_p(trailingslashit($dir));
        @chmod($dir, 0733);
    }
    
    function init_captcha() {
        if (! class_exists('ReallySimpleCaptcha'))
            return false;

        if (! is_object($this->captcha))
			$this->captcha = new ReallySimpleCaptcha();
		$captcha =& $this->captcha;
        
        $captcha->tmp_dir = trailingslashit($this->captcha_tmp_dir());
        wp_mkdir_p($captcha->tmp_dir);
        return true;
    }

	function generate_captcha($options = null) {
        if (! $this->init_captcha())
            return false;
        $captcha =& $this->captcha;
		
		if (! is_dir($captcha->tmp_dir) || ! is_writable($captcha->tmp_dir))
			return false;
		
		$img_type = imagetypes();
		if ($img_type & IMG_PNG)
			$captcha->img_type = 'png';
		elseif ($img_type & IMG_GIF)
			$captcha->img_type = 'gif';
		elseif ($img_type & IMG_JPG)
			$captcha->img_type = 'jpeg';
		else
			return false;
		
		if (is_array($options)) {
			if (isset($options['img_size']))
				$captcha->img_size = $options['img_size'];
			if (isset($options['base']))
				$captcha->base = $options['base'];
			if (isset($options['font_size']))
				$captcha->font_size = $options['font_size'];
			if (isset($options['font_char_width']))
				$captcha->font_char_width = $options['font_char_width'];
			if (isset($options['fg']))
				$captcha->fg = $options['fg'];
			if (isset($options['bg']))
				$captcha->bg = $options['bg'];
		}
		
		$prefix = mt_rand();
		$captcha_word = $captcha->generate_random_word();
		return $captcha->generate_image($prefix, $captcha_word);
	}

	function check_captcha($prefix, $response) {
        if (! $this->init_captcha())
            return false;
        $captcha =& $this->captcha;
		
		return $captcha->check($prefix, $response);
	}

	function remove_captcha($prefix) {
        if (! $this->init_captcha())
            return false;
        $captcha =& $this->captcha;
		
		$captcha->remove($prefix);
	}

	function cleanup_captcha_files() {
        if (! $this->init_captcha())
            return false;
        $captcha =& $this->captcha;

		$tmp_dir = $captcha->tmp_dir;
		
		if (! is_dir($tmp_dir) || ! is_writable($tmp_dir))
			return false;
		
		if ($handle = opendir($tmp_dir)) {
			while (false !== ($file = readdir($handle))) {
				if (! preg_match('/^[0-9]+\.(php|png|gif|jpeg)$/', $file))
					continue;
				$stat = stat($tmp_dir . $file);
				if ($stat['mtime'] + 21600 < time()) // 21600 secs == 6 hours
					@ unlink($tmp_dir . $file);
			}
			closedir($handle);
		}
	}

	function captchac_options($options) {
		if (! is_array($options))
			return array();
		
		$op = array();
		$image_size_array = preg_grep('%^size:[smlSML]$%', $options);
		if ($image_size = array_shift($image_size_array)) {
			preg_match('%^size:([smlSML])$%', $image_size, $is_matches);
			switch (strtolower($is_matches[1])) {
				case 's':
					$op['img_size'] = array(60, 20);
					$op['base'] = array(6, 15);
					$op['font_size'] = 11;
					$op['font_char_width'] = 13;
					break;
				case 'l':
					$op['img_size'] = array(84, 28);
					$op['base'] = array(6, 20);
					$op['font_size'] = 17;
					$op['font_char_width'] = 19;
					break;
				case 'm':
				default:
					$op['img_size'] = array(72, 24);
					$op['base'] = array(6, 18);
					$op['font_size'] = 14;
					$op['font_char_width'] = 15;
			}
		}
		$fg_color_array = preg_grep('%^fg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $options);
		if ($fg_color = array_shift($fg_color_array)) {
			preg_match('%^fg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $fg_color, $fc_matches);
			if (3 == strlen($fc_matches[1])) {
				$r = substr($fc_matches[1], 0, 1);
				$g = substr($fc_matches[1], 1, 1);
				$b = substr($fc_matches[1], 2, 1);
				$op['fg'] = array(hexdec($r . $r), hexdec($g . $g), hexdec($b . $b));
			} elseif (6 == strlen($fc_matches[1])) {
				$r = substr($fc_matches[1], 0, 2);
				$g = substr($fc_matches[1], 2, 2);
				$b = substr($fc_matches[1], 4, 2);
				$op['fg'] = array(hexdec($r), hexdec($g), hexdec($b));
			}
		}
		$bg_color_array = preg_grep('%^bg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $options);
		if ($bg_color = array_shift($bg_color_array)) {
			preg_match('%^bg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $bg_color, $bc_matches);
			if (3 == strlen($bc_matches[1])) {
				$r = substr($bc_matches[1], 0, 1);
				$g = substr($bc_matches[1], 1, 1);
				$b = substr($bc_matches[1], 2, 1);
				$op['bg'] = array(hexdec($r . $r), hexdec($g . $g), hexdec($b . $b));
			} elseif (6 == strlen($bc_matches[1])) {
				$r = substr($bc_matches[1], 0, 2);
				$g = substr($bc_matches[1], 2, 2);
				$b = substr($bc_matches[1], 4, 2);
				$op['bg'] = array(hexdec($r), hexdec($g), hexdec($b));
			}
		}
		
		return $op;
	}

    function pipe($pipes, $value) {
        if (is_array($value)) {
            $results = array();
            foreach ($value as $k => $v) {
                $results[$k] = $this->pipe($pipes, $v);
            }
            return $results;
        }

        foreach ($pipes as $p) {
            if ($p[0] == $value)
                return $p[1];
        }

        return $value;
    }

    function get_pipe_ins($pipes) {
        $ins = array();
        foreach ($pipes as $pipe) {
            $in = $pipe[0];
            if (! in_array($in, $ins))
                $ins[] = $in;
        }
        return $ins;
    }

    function get_pipes($values) {
        $pipes = array();
        
        foreach ($values as $value) {
            $pipe_pos = strpos($value, '|');
            if (false === $pipe_pos) {
                $before = $after = $value;
            } else {
                $before = substr($value, 0, $pipe_pos);
                $after = substr($value, $pipe_pos + 1);
            }

            $pipes[] = array($before, $after);
        }
        
        return $pipes;
    }

    function pipe_all_posted($contact_form) {
        $all_pipes = array();

        $fes = $this->form_elements($contact_form['form'], false);
        foreach ($fes as $fe) {
            $type = $fe['type'];
            $name = $fe['name'];
            $raw_values = $fe['raw_values'];
            
            if (! preg_match('/^(select[*]?|checkbox[*]?|radio)$/', $type))
                continue;
            
            $pipes = $this->get_pipes($raw_values);
            
            $all_pipes[$name] = array_merge($pipes, (array) $all_pipes[$name]);
        }
        
        foreach ($all_pipes as $name => $pipes) {
            if (isset($this->posted_data[$name]))
                $this->posted_data[$name] = $this->pipe($pipes, $this->posted_data[$name]);
        }
    }

    function captcha_tmp_dir() {
        if (defined('WPCF7_CAPTCHA_TMP_DIR'))
            return WPCF7_CAPTCHA_TMP_DIR;
        else
            return $this->upload_dir('dir') . '/wpcf7_captcha';
    }

    function captcha_tmp_url() {
        if (defined('WPCF7_CAPTCHA_TMP_URL'))
            return WPCF7_CAPTCHA_TMP_URL;
        else
            return $this->upload_dir('url') . '/wpcf7_captcha';
    }

    function upload_tmp_dir() {
        if (defined('WPCF7_UPLOADS_TMP_DIR'))
            return WPCF7_UPLOADS_TMP_DIR;
        else
            return $this->upload_dir('dir') . '/wpcf7_uploads';
    }

    function upload_dir($type = false) {
        $siteurl = get_option('siteurl');
        $upload_path = trim(get_option('upload_path'));
        if (empty($upload_path))
            $dir = WP_CONTENT_DIR . '/uploads';
        else
            $dir = $upload_path;

        $dir = path_join(ABSPATH, $dir);

        if (! $url = get_option('upload_url_path')) {
            if (empty($upload_path) || $upload_path == $dir)
                $url = WP_CONTENT_URL . '/uploads';
            else
                $url = trailingslashit($siteurl) . $upload_path;
        }

        if (defined('UPLOADS')) {
            $dir = ABSPATH . UPLOADS;
            $url = trailingslashit($siteurl) . UPLOADS;
        }

        if ('dir' == $type)
            return $dir;
        if ('url' == $type)
            return $url;
        return array('dir' => $dir, 'url' => $url);
    }
}

$wpcf7 = new tam_contact_form_seven();

?>