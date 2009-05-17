<?php
/*
Plugin Name: Contact Form 7
Plugin URI: http://ideasilo.wordpress.com/2007/04/30/contact-form-7/
Description: Just another contact form plugin. Simple but flexible.
Author: Takayuki Miyoshi
Version: 1.9.5.1
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

define( 'WPCF7_VERSION', '1.9.5.1' );

if ( ! defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

if ( ! defined( 'WP_CONTENT_URL' ) )
	define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );

if ( ! defined( 'WP_PLUGIN_DIR' ) )
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

if ( ! defined( 'WP_PLUGIN_URL' ) )
	define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );

if ( ! defined( 'WPCF7_PLUGIN_DIR' ) )
	define( 'WPCF7_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) );

if ( ! defined( 'WPCF7_PLUGIN_URL' ) )
	define( 'WPCF7_PLUGIN_URL', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) );

if ( ! defined( 'WPCF7_PLUGIN_BASENAME' ) )
	define( 'WPCF7_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! defined( 'WPCF7_AUTOP' ) )
	define( 'WPCF7_AUTOP', true );

if ( ! defined( 'WPCF7_USE_PIPE' ) )
	define( 'WPCF7_USE_PIPE', true );

if ( ! function_exists( 'wpcf7_version' ) ) {
	function wpcf7_version() { return WPCF7_VERSION; }
}

if ( ! function_exists( 'wpcf7_read_capability' ) ) {
	function wpcf7_read_capability() { return 'edit_posts'; }
}

if ( ! function_exists( 'wpcf7_read_write_capability' ) ) {
	function wpcf7_read_write_capability() { return 'publish_pages'; }
}

class tam_contact_form_seven {

	var $contact_forms;
	var $captcha;
	var $posted_data;

	function tam_contact_form_seven() {
	}

	function ajax_json_echo() {
		$echo = '';

		if ( isset( $_POST['_wpcf7'] ) ) {
			$id = (int) $_POST['_wpcf7'];
			$unit_tag = $_POST['_wpcf7_unit_tag'];
			$contact_forms = $this->contact_forms();

			if ( $cf = $contact_forms[$id] ) {
				$cf = stripslashes_deep( $cf );
				$validation = $this->validate( $cf );

				$handled_uploads = wpcf7_handle_uploads( $cf );
				if ( ! $handled_uploads['validation']['valid'] )
					$validation['valid'] = false;

				$validation['reason'] = array_merge( $validation['reason'], $handled_uploads['validation']['reason'] );

				$captchas = wpcf7_refill_captcha( $cf );
				if ( ! empty( $captchas ) ) {
					$captchas_js = array();
					foreach ( $captchas as $name => $cap ) {
						$captchas_js[] = '"' . $name . '": "' . $cap . '"';
					}
					$captcha = '{ ' . join( ', ', $captchas_js ) . ' }';
				} else {
					$captcha = 'null';
				}

				$quizzes = $this->refill_quiz( $cf );
				if ( ! empty( $quizzes ) ) {
					$quizzes_js = array();
					foreach ( $quizzes as $name => $q ) {
						$quizzes_js[] = '"' . $name . '": [ "' . js_escape( $q[0] ) . '", "' . $q[1] . '" ]';
					}
					$quiz = '{ ' . join( ', ', $quizzes_js ) . ' }';
				} else {
					$quiz = 'null';
				}

				if ( ! $validation['valid'] ) { // Validation error occured
					$invalids = array();
					foreach ( $validation['reason'] as $name => $reason ) {
						$invalids[] = '{ into: "span.wpcf7-form-control-wrap.' . $name . '", message: "' . js_escape( $reason ) . '" }';
					}
					$invalids = '[' . join( ', ', $invalids ) . ']';
					$echo = '{ mailSent: 0, message: "' . js_escape( wpcf7_message( $cf, 'validation_error' ) ) . '", into: "#' . $unit_tag . '", invalids: ' . $invalids . ', captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
				} elseif ( ! $this->acceptance( $cf ) ) { // Not accepted terms
					$echo = '{ mailSent: 0, message: "' . js_escape( wpcf7_message( $cf, 'accept_terms' ) ) . '", into: "#' . $unit_tag . '", captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
				} elseif ( $this->akismet( $cf ) ) { // Spam!
					$echo = '{ mailSent: 0, message: "' . js_escape( wpcf7_message( $cf, 'akismet_says_spam' ) ) . '", into: "#' . $unit_tag . '", spam: 1, captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
				} elseif ( wpcf7_mail( $cf, $handled_uploads['files'] ) ) {
					$echo = '{ mailSent: 1, message: "' . js_escape( wpcf7_message( $cf, 'mail_sent_ok' ) ) . '", into: "#' . $unit_tag . '", captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
				} else {
					$echo = '{ mailSent: 0, message: "' . js_escape( wpcf7_message( $cf, 'mail_sent_ng' ) ) . '", into: "#' . $unit_tag . '", captcha: ' . $captcha . ', quiz: ' . $quiz . ' }';
				}

				// remove upload files
				foreach ( $handled_uploads['files'] as $name => $path ) {
					@unlink( $path );
				}
			}
		}

		if ( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) {
			@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
			echo $echo;
		} else {
			@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
			echo '<textarea>' . $echo . '</textarea>';
		}
	}

	function akismet( $contact_form ) {
		global $akismet_api_host, $akismet_api_port;

		if ( ! function_exists( 'akismet_http_post' ) || ! ( get_option( 'wordpress_api_key' ) || $wpcom_api_key ) )
			return false;

		$akismet_ready = false;
		$author = $author_email = $author_url = $content = '';
		$fes = $this->form_elements( $contact_form['form'], false );

		foreach ( $fes as $fe ) {
			if ( ! is_array( $fe['options'] ) ) continue;

			if ( preg_grep( '%^akismet:author$%', $fe['options'] ) && '' == $author ) {
				$author = $_POST[$fe['name']];
				$akismet_ready = true;
			}

			if ( preg_grep( '%^akismet:author_email$%', $fe['options'] ) && '' == $author_email ) {
				$author_email = $_POST[$fe['name']];
				$akismet_ready = true;
			}

			if ( preg_grep( '%^akismet:author_url$%', $fe['options'] ) && '' == $author_url ) {
				$author_url = $_POST[$fe['name']];
				$akismet_ready = true;
			}

			if ( '' != $content )
				$content .= "\n\n";

			$content .= $_POST[$fe['name']];
		}

		if ( ! $akismet_ready )
			return false;

		$c['blog'] = get_option( 'home' );
		$c['user_ip'] = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
		$c['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		$c['referrer'] = $_SERVER['HTTP_REFERER'];
		$c['comment_type'] = 'contactform7';
		if ( $permalink = get_permalink() )
			$c['permalink'] = $permalink;
		if ( '' != $author )
			$c['comment_author'] = $author;
		if ( '' != $author_email )
			$c['comment_author_email'] = $author_email;
		if ( '' != $author_url )
			$c['comment_author_url'] = $author_url;
		if ( '' != $content )
			$c['comment_content'] = $content;

		$ignore = array( 'HTTP_COOKIE' );

		foreach ( $_SERVER as $key => $value )
			if ( ! in_array( $key, (array) $ignore ) )
				$c["$key"] = $value;

		$query_string = '';
		foreach ( $c as $key => $data )
			$query_string .= $key . '=' . urlencode( stripslashes( $data ) ) . '&';

		$response = akismet_http_post( $query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port );
		if ( 'true' == $response[1] )
			return true;
		else
			return false;
	}

	function acceptance( $contact_form ) {
		$fes = $this->form_elements( $contact_form['form'], false );

		$accepted = true;

		foreach ( $fes as $fe ) {
			if ( 'acceptance' != $fe['type'] )
				continue;

			$invert = (bool) preg_grep( '%^invert$%', $fe['options'] );

			if ( $invert && $_POST[$fe['name']] || ! $invert && ! $_POST[$fe['name']] )
				$accepted = false;
		}

		return $accepted;
	}

	function contact_forms() {
		if ( is_array( $this->contact_forms ) )
			return $this->contact_forms;

		$wpcf7 = get_option( 'wpcf7' );
		$this->contact_forms = $wpcf7['contact_forms'];
		if ( ! is_array( $this->contact_forms ) )
			$this->contact_forms = array();
		return $this->contact_forms;
	}

	function update_contact_forms( $contact_forms ) {
		$wpcf7 = get_option( 'wpcf7' );
		$wpcf7['contact_forms'] = $contact_forms;

		update_option( 'wpcf7', $wpcf7 );
	}

	function upgrade( $contact_form ) {
		if ( empty( $contact_form ) )
			return $contact_form;

		$contact_form = $this->upgrade_160( $contact_form );
		$contact_form = $this->upgrade_181( $contact_form );
		$contact_form = $this->upgrade_190( $contact_form );
		$contact_form = $this->upgrade_192( $contact_form );
		return $contact_form;
	}

	function upgrade_160( $contact_form ) {
		if ( ! isset( $contact_form['mail']['recipient'] ) )
			$contact_form['mail']['recipient'] = $contact_form['options']['recipient'];
		return $contact_form;
	}

	function upgrade_181( $contact_form ) {
		if ( ! isset( $contact_form['messages'] ) )
			$contact_form['messages'] = array(
				'mail_sent_ok' => wpcf7_default_message( 'mail_sent_ok' ),
				'mail_sent_ng' => wpcf7_default_message( 'mail_sent_ng' ),
				'akismet_says_spam' => wpcf7_default_message( 'akismet_says_spam' ),
				'validation_error' => wpcf7_default_message( 'validation_error' ),
				'accept_terms' => wpcf7_default_message( 'accept_terms' ),
				'invalid_email' => wpcf7_default_message( 'invalid_email' ),
				'invalid_required' => wpcf7_default_message( 'invalid_required' ),
				'captcha_not_match' => wpcf7_default_message( 'captcha_not_match' )
			);
		return $contact_form;
	}

	function upgrade_190( $contact_form ) {
		if ( ! isset( $contact_form['messages'] ) || ! is_array( $contact_form['messages'] ) )
			$contact_form['messages'] = array();

		if ( ! isset( $contact_form['messages']['upload_failed'] ) )
			$contact_form['messages']['upload_failed'] = wpcf7_default_message( 'upload_failed' );

		if ( ! isset( $contact_form['messages']['upload_file_type_invalid'] ) )
			$contact_form['messages']['upload_file_type_invalid'] = wpcf7_default_message( 'upload_file_type_invalid' );

		if ( ! isset( $contact_form['messages']['upload_file_too_large'] ) )
			$contact_form['messages']['upload_file_too_large'] = wpcf7_default_message( 'upload_file_too_large' );

		return $contact_form;
	}

	function upgrade_192( $contact_form ) {
		if ( ! isset( $contact_form['messages'] ) || ! is_array( $contact_form['messages'] ) )
			$contact_form['messages'] = array();

		if ( ! isset( $contact_form['messages']['quiz_answer_not_correct'] ) )
			$contact_form['messages']['quiz_answer_not_correct'] = wpcf7_default_message( 'quiz_answer_not_correct' );

		return $contact_form;
	}

	function process_nonajax_submitting() {
		if ( ! isset($_POST['_wpcf7'] ) )
			return;

		$id = (int) $_POST['_wpcf7'];
		$contact_forms = $this->contact_forms();
		if ( $cf = $contact_forms[$id] ) {
			$cf = stripslashes_deep( $cf );
			$validation = $this->validate( $cf );

			$handled_uploads = wpcf7_handle_uploads( $cf );
			if ( ! $handled_uploads['validation']['valid'] )
				$validation['valid'] = false;
			$validation['reason'] = array_merge( $validation['reason'], $handled_uploads['validation']['reason'] );

			if ( ! $validation['valid'] ) {
				$_POST['_wpcf7_validation_errors'] = array( 'id' => $id, 'messages' => $validation['reason'] );
			} elseif ( ! $this->acceptance( $cf ) ) { // Not accepted terms
				$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => wpcf7_message( $cf, 'accept_terms' ) );
			} elseif ( $this->akismet( $cf ) ) { // Spam!
				$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => wpcf7_message( $cf, 'akismet_says_spam' ), 'spam' => true );
			} elseif ( wpcf7_mail( $cf, $handled_uploads['files'] ) ) {
				$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => true, 'message' => wpcf7_message( $cf, 'mail_sent_ok' ) );
			} else {
				$_POST['_wpcf7_mail_sent'] = array( 'id' => $id, 'ok' => false, 'message' => wpcf7_message( $cf, 'mail_sent_ng' ) );
			}

			// remove upload files
			foreach ( $handled_uploads['files'] as $name => $path ) {
				@unlink( $path );
			}
		}
	}

/* Post content filtering */

	var $processing_unit_tag;
	var $processing_within;
	var $unit_count;
	var $widget_count;

	function the_content_filter( $content ) {
		$this->processing_within = 'p' . get_the_ID();
		$this->unit_count = 0;

		return $content;
	}

	function widget_text_filter( $content ) {
		$this->widget_count += 1;
		$this->processing_within = 'w' . $this->widget_count;
		$this->unit_count = 0;

		$regex = '/\[\s*contact-form\s+(\d+(?:\s+.*)?)\]/';
		return preg_replace_callback( $regex, array( &$this, 'widget_text_filter_callback' ), $content );
	}

	function widget_text_filter_callback( $matches ) {
		return $this->contact_form_tag_func( $matches[1] );
	}

	function contact_form_tag_func( $atts ) {
		if ( is_string( $atts ) )
			$atts = explode( ' ', $atts, 2 );

		$atts = (array) $atts;

		$id = (int) array_shift( $atts );

		$contact_forms = $this->contact_forms();

		if ( ! ( $cf = $contact_forms[$id] ) )
			return '[contact-form 404 "Not Found"]';

		$cf = stripslashes_deep( $cf );

		$this->unit_count += 1;
		$unit_tag = 'wpcf7-f' . $id . '-' . $this->processing_within . '-o' . $this->unit_count;
		$this->processing_unit_tag = $unit_tag;

		$form = '<div class="wpcf7" id="' . $unit_tag . '">';

		$url = parse_url( $_SERVER['REQUEST_URI'] );
		$url = $url['path'] . ( empty( $url['query'] ) ? '' : '?' . $url['query'] ) . '#' . $unit_tag;

		$form_elements = $this->form_elements( $cf['form'], false );
		$multipart = false;
		foreach ( $form_elements as $form_element ) {
			if ( preg_match( '/^file[*]?$/', $form_element['type'] ) ) {
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
		$form .= $this->form_elements( $cf['form'] );
		$form .= '</form>';

		// Post response output for non-AJAX
		$class = 'wpcf7-response-output';

		if ( $this->processing_unit_tag == $_POST['_wpcf7_unit_tag'] ) {
			if ( isset( $_POST['_wpcf7_mail_sent'] ) && $_POST['_wpcf7_mail_sent']['id'] == $id ) {
				if ( $_POST['_wpcf7_mail_sent']['ok'] ) {
					$class .= ' wpcf7-mail-sent-ok';
					$content = $_POST['_wpcf7_mail_sent']['message'];
				} else {
					$class .= ' wpcf7-mail-sent-ng';
					if ( $_POST['_wpcf7_mail_sent']['spam'] )
						$class .= ' wpcf7-spam-blocked';
					$content = $_POST['_wpcf7_mail_sent']['message'];
				}
			} elseif ( isset( $_POST['_wpcf7_validation_errors'] ) && $_POST['_wpcf7_validation_errors']['id'] == $id ) {
				$class .= ' wpcf7-validation-errors';
				$content = wpcf7_message( $cf, 'validation_error' );
			}
		}

		$class = ' class="' . $class . '"';

		$form .= '<div' . $class . '>' . $content . '</div>';

		$form .= '</div>';

		$this->processing_unit_tag = null;

		if ( WPCF7_AUTOP )
			$form = wpcf7_wpautop_substitute( $form );

		return $form;
	}

	function validate( $contact_form ) {
		$fes = $this->form_elements( $contact_form['form'], false );
		$valid = true;
		$reason = array();

		foreach ( $fes as $fe ) {
			$type = $fe['type'];
			$name = $fe['name'];
			$values = $fe['values'];
			$raw_values = $fe['raw_values'];

			// Before validation corrections
			if ( preg_match( '/^(?:text|email|captchar|textarea)[*]?$/', $type ) )
				$_POST[$name] = (string) $_POST[$name];

			if ( preg_match( '/^(?:text|email)[*]?$/', $type ) )
				$_POST[$name] = trim( strtr( $_POST[$name], "\n", " " ) );

			if ( preg_match( '/^(?:select|checkbox|radio)[*]?$/', $type ) ) {
				if ( is_array( $_POST[$name] ) ) {
					foreach ( $_POST[$name] as $key => $value ) {
						$value = stripslashes( $value );
						if ( ! in_array( $value, (array) $values ) ) // Not in given choices.
							unset( $_POST[$name][$key] );
					}
				} else {
					$value = stripslashes( $_POST[$name] );
					if ( ! in_array( $value, (array) $values ) ) //  Not in given choices.
						$_POST[$name] = '';
				}
			}

			if ( 'acceptance' == $type )
				$_POST[$name] = $_POST[$name] ? 1 : 0;

			// Required item (*)
			if ( preg_match( '/^(?:text|textarea)[*]$/', $type ) ) {
				if ( ! isset( $_POST[$name] ) || '' == $_POST[$name] ) {
					$valid = false;
					$reason[$name] = wpcf7_message( $contact_form, 'invalid_required' );
				}
			}

			if ( 'checkbox*' == $type ) {
				if ( empty( $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = wpcf7_message( $contact_form, 'invalid_required' );
				}
			}

			if ( 'select*' == $type ) {
				if ( empty( $_POST[$name] ) ||
						! is_array( $_POST[$name] ) && '---' == $_POST[$name] ||
						is_array( $_POST[$name] ) && 1 == count( $_POST[$name] ) && '---' == $_POST[$name][0] ) {
					$valid = false;
					$reason[$name] = wpcf7_message( $contact_form, 'invalid_required' );
				}
			}

			if ( preg_match( '/^email[*]?$/', $type ) ) {
				if ( '*' == substr( $type, -1 ) && ( ! isset( $_POST[$name] ) || '' == $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = wpcf7_message( $contact_form, 'invalid_required' );
				} elseif ( isset( $_POST[$name] ) && '' != $_POST[$name] && ! is_email( $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = wpcf7_message( $contact_form, 'invalid_email' );
				}
			}

			if ( preg_match( '/^captchar$/', $type ) ) {
				$captchac = '_wpcf7_captcha_challenge_' . $name;
				if ( ! wpcf7_check_captcha( $_POST[$captchac], $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = wpcf7_message( $contact_form, 'captcha_not_match' );
				}
				wpcf7_remove_captcha( $_POST[$captchac] );
			}

			if ( 'quiz' == $type ) {
				$answer = wpcf7_canonicalize( $_POST[$name] );
				$answer_hash = wp_hash( $answer, 'wpcf7_quiz' );
				$expected_hash = $_POST['_wpcf7_quiz_answer_' . $name];
				if ( $answer_hash != $expected_hash ) {
					$valid = false;
					$reason[$name] = wpcf7_message( $contact_form, 'quiz_answer_not_correct' );
				}
			}
		}
		return compact( 'valid', 'reason' );
	}

	function refill_quiz( $contact_form ) {
		$fes = $this->form_elements( $contact_form['form'], false );
		$refill = array();

		foreach ( $fes as $fe ) {
			$type = $fe['type'];
			$name = $fe['name'];
			$values = $fe['values'];
			$raw_values = $fe['raw_values'];

			if ( 'quiz' != $type )
				continue;

			if ( count( $values ) == 0 )
				continue;

			if ( count( $values ) == 1 )
				$question = $values[0];
			else
				$question = $values[array_rand( $values )];

			$pipes = wpcf7_get_pipes( $raw_values );
			$answer = wpcf7_pipe( $pipes, $question );
			$answer = wpcf7_canonicalize( $answer );

			$refill[$name] = array( $question, wp_hash( $answer, 'wpcf7_quiz' ) );
		}

		return $refill;
	}

/* Processing form element placeholders */

	function form_elements( $form, $replace = true ) {
		$types = 'text[*]?|email[*]?|textarea[*]?|select[*]?|checkbox[*]?|radio|acceptance|captchac|captchar|file[*]?|quiz';
		$regex = '%\[\s*(' . $types . ')(\s+[a-zA-Z][0-9a-zA-Z:._-]*)([-0-9a-zA-Z:#_/|\s]*)?((?:\s*(?:"[^"]*"|\'[^\']*\'))*)?\s*\]%';
		$submit_regex = '%\[\s*submit(\s[-0-9a-zA-Z:#_/\s]*)?(\s+(?:"[^"]*"|\'[^\']*\'))?\s*\]%';
		if ( $replace ) {
			$form = preg_replace_callback( $regex, array( &$this, 'form_element_replace_callback' ), $form );
			// Submit button
			$form = preg_replace_callback( $submit_regex, array( &$this, 'submit_replace_callback' ), $form );
			return $form;
		} else {
			$results = array();
			preg_match_all( $regex, $form, $matches, PREG_SET_ORDER );
			foreach ( $matches as $match ) {
				$results[] = (array) $this->form_element_parse( $match );
			}
			return $results;
		}
	}

	function form_element_replace_callback( $matches ) {
		extract( (array) $this->form_element_parse( $matches ) ); // $type, $name, $options, $values, $raw_values

		if ( $this->processing_unit_tag == $_POST['_wpcf7_unit_tag'] ) {
			$validation_error = $_POST['_wpcf7_validation_errors']['messages'][$name];
			$validation_error = $validation_error ? '<span class="wpcf7-not-valid-tip-no-ajax">' . $validation_error . '</span>' : '';
		} else {
			$validation_error = '';
		}

		$atts = '';
		$options = (array) $options;

		$id_array = preg_grep( '%^id:[-0-9a-zA-Z_]+$%', $options );
		if ( $id = array_shift( $id_array ) ) {
			preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $id, $id_matches );
			if ( $id = $id_matches[1] )
				$atts .= ' id="' . $id . '"';
		}

		$class_att = "";
		$class_array = preg_grep( '%^class:[-0-9a-zA-Z_]+$%', $options );
		foreach ( $class_array as $class ) {
			preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $class, $class_matches );
			if ( $class = $class_matches[1] )
				$class_att .= ' ' . $class;
		}

		if ( preg_match( '/^email[*]?$/', $type ) )
			$class_att .= ' wpcf7-validates-as-email';
		if ( preg_match( '/[*]$/', $type ) )
			$class_att .= ' wpcf7-validates-as-required';

		if ( preg_match( '/^checkbox[*]?$/', $type ) )
			$class_att .= ' wpcf7-checkbox';

		if ( 'radio' == $type )
			$class_att .= ' wpcf7-radio';

		if ( preg_match( '/^captchac$/', $type ) )
			$class_att .= ' wpcf7-captcha-' . $name;

		if ( 'acceptance' == $type ) {
			$class_att .= ' wpcf7-acceptance';
			if ( preg_grep( '%^invert$%', $options ) )
				$class_att .= ' wpcf7-invert';
		}

		if ( $class_att )
			$atts .= ' class="' . trim( $class_att ) . '"';

		// Value.
		if ( $this->processing_unit_tag == $_POST['_wpcf7_unit_tag'] ) {
			if ( isset( $_POST['_wpcf7_mail_sent'] ) && $_POST['_wpcf7_mail_sent']['ok'] )
				$value = '';
			elseif ( 'captchar' == $type )
				$value = '';
			else
				$value = $_POST[$name];
		} else {
			$value = $values[0];
		}

		// Default selected/checked for select/checkbox/radio
		if ( preg_match( '/^(?:select|checkbox|radio)[*]?$/', $type ) ) {
			$scr_defaults = array_values( preg_grep( '/^default:/', $options ) );
			preg_match( '/^default:([0-9_]+)$/', $scr_defaults[0], $scr_default_matches );
			$scr_default = explode( '_', $scr_default_matches[1] );
		}

		switch ( $type ) {
			case 'text':
			case 'text*':
			case 'email':
			case 'email*':
			case 'captchar':
				if ( is_array( $options ) ) {
					$size_maxlength_array = preg_grep( '%^[0-9]*[/x][0-9]*$%', $options );
					if ( $size_maxlength = array_shift( $size_maxlength_array ) ) {
						preg_match( '%^([0-9]*)[/x]([0-9]*)$%', $size_maxlength, $sm_matches );
						if ( $size = (int) $sm_matches[1] )
							$atts .= ' size="' . $size . '"';
						else
							$atts .= ' size="40"';
						if ( $maxlength = (int) $sm_matches[2] )
							$atts .= ' maxlength="' . $maxlength . '"';
					} else {
						$atts .= ' size="40"';
					}
				}
				$html = '<input type="text" name="' . $name . '" value="' . attribute_escape( $value ) . '"' . $atts . ' />';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'textarea':
			case 'textarea*':
				if ( is_array( $options ) ) {
					$cols_rows_array = preg_grep( '%^[0-9]*[x/][0-9]*$%', $options );
					if ( $cols_rows = array_shift( $cols_rows_array ) ) {
						preg_match( '%^([0-9]*)[x/]([0-9]*)$%', $cols_rows, $cr_matches );
						if ( $cols = (int) $cr_matches[1] )
							$atts .= ' cols="' . $cols . '"';
						else
							$atts .= ' cols="40"';
						if ( $rows = (int) $cr_matches[2] )
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
				$multiple = ( preg_grep( '%^multiple$%', $options ) ) ? true : false;
				$include_blank = preg_grep( '%^include_blank$%', $options );

				if ( $empty_select = empty( $values ) || $include_blank )
					array_unshift( $values, '---' );

				$html = '';
				foreach ( $values as $key => $value ) {
					$selected = '';
					if ( ! $empty_select && in_array( $key + 1, (array) $scr_default ) )
						$selected = ' selected="selected"';
					if ( $this->processing_unit_tag == $_POST['_wpcf7_unit_tag'] && (
						$multiple && in_array( $value, (array) $_POST[$name] ) ||
							! $multiple && $_POST[$name] == $value ) )
						$selected = ' selected="selected"';
					$html .= '<option value="' . attribute_escape( $value ) . '"' . $selected . '>' . $value . '</option>';
				}

				if ( $multiple )
					$atts .= ' multiple="multiple"';

				$html = '<select name="' . $name . ( $multiple ? '[]' : '' ) . '"' . $atts . '>' . $html . '</select>';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'checkbox':
			case 'checkbox*':
			case 'radio':
				$multiple = ( preg_match( '/^checkbox[*]?$/', $type ) && ! preg_grep( '%^exclusive$%', $options ) ) ? true : false;
				$html = '';

				if ( preg_match( '/^checkbox[*]?$/', $type ) && ! $multiple )
					$onclick = ' onclick="wpcf7ExclusiveCheckbox(this);"';

				$input_type = rtrim( $type, '*' );

				foreach ( $values as $key => $value ) {
					$checked = '';
					if ( in_array( $key + 1, (array) $scr_default ) )
						$checked = ' checked="checked"';
					if ( $this->processing_unit_tag == $_POST['_wpcf7_unit_tag'] && (
						$multiple && in_array( $value, (array) $_POST[$name] ) ||
							! $multiple && $_POST[$name] == $value ) )
						$checked = ' checked="checked"';
					if ( preg_grep( '%^label[_-]?first$%', $options ) ) { // put label first, input last
						$item = '<span class="wpcf7-list-item-label">' . $value . '</span>&nbsp;';
						$item .= '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . attribute_escape( $value ) . '"' . $checked . $onclick . ' />';
					} else {
						$item = '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . attribute_escape( $value ) . '"' . $checked . $onclick . ' />';
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
				if ( count( $raw_values ) == 0 && count( $values ) == 0 ) { // default quiz
					$raw_values[] = '1+1=?|2';
					$values[] = '1+1=?';
				}

				$pipes = wpcf7_get_pipes( $raw_values );

				if ( count( $values ) == 0 ) {
					break;
				} elseif ( count( $values ) == 1 ) {
					$value = $values[0];
				} else {
					$value = $values[array_rand( $values )];
				}

				$answer = wpcf7_pipe( $pipes, $value );
				$answer = wpcf7_canonicalize( $answer );

				if ( is_array( $options ) ) {
					$size_maxlength_array = preg_grep( '%^[0-9]*[/x][0-9]*$%', $options );
					if ( $size_maxlength = array_shift( $size_maxlength_array ) ) {
						preg_match( '%^([0-9]*)[/x]([0-9]*)$%', $size_maxlength, $sm_matches );
						if ( $size = (int) $sm_matches[1] )
							$atts .= ' size="' . $size . '"';
						else
							$atts .= ' size="40"';
						if ( $maxlength = (int) $sm_matches[2] )
							$atts .= ' maxlength="' . $maxlength . '"';
					} else {
						$atts .= ' size="40"';
					}
				}
                
				$html = '<span class="wpcf7-quiz-label">' . $value . '</span>&nbsp;';
				$html .= '<input type="text" name="' . $name . '"' . $atts . ' />';
				$html .= '<input type="hidden" name="_wpcf7_quiz_answer_' . $name . '" value="' . wp_hash( $answer, 'wpcf7_quiz' ) . '" />';
				$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';
				return $html;
				break;
			case 'acceptance':
				$invert = (bool) preg_grep( '%^invert$%', $options );
				$default = (bool) preg_grep( '%^default:on$%', $options );

				$onclick = ' onclick="wpcf7ToggleSubmit(this.form);"';
				$checked = $default ? ' checked="checked"' : '';
				$html = '<input type="checkbox" name="' . $name . '" value="1"' . $atts . $onclick . $checked . ' />';
				return $html;
				break;
			case 'captchac':
				if ( ! class_exists( 'ReallySimpleCaptcha' ) ) {
					return '<em>' . __( 'To use CAPTCHA, you need <a href="http://wordpress.org/extend/plugins/really-simple-captcha/">Really Simple CAPTCHA</a> plugin installed.', 'wpcf7' ) . '</em>';
					break;
				}

				$op = array();
				// Default
				$op['img_size'] = array( 72, 24 );
				$op['base'] = array( 6, 18 );
				$op['font_size'] = 14;
				$op['font_char_width'] = 15;

				$op = array_merge( $op, wpcf7_captchac_options( $options ) );

				if ( ! $filename = wpcf7_generate_captcha( $op ) ) {
					return '';
					break;
				}
				if ( is_array( $op['img_size'] ) )
					$atts .= ' width="' . $op['img_size'][0] . '" height="' . $op['img_size'][1] . '"';
				$captcha_url = trailingslashit( wpcf7_captcha_tmp_url() ) . $filename;
				$html = '<img alt="captcha" src="' . $captcha_url . '"' . $atts . ' />';
				$ref = substr( $filename, 0, strrpos( $filename, '.' ) );
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

	function submit_replace_callback( $matches ) {
		$atts = '';
		$options = preg_split( '/[\s]+/', trim( $matches[1] ) );

		$id_array = preg_grep( '%^id:[-0-9a-zA-Z_]+$%', $options );
		if ( $id = array_shift( $id_array ) ) {
			preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $id, $id_matches );
			if ( $id = $id_matches[1] )
				$atts .= ' id="' . $id . '"';
		}

		$class_att = '';
		$class_array = preg_grep( '%^class:[-0-9a-zA-Z_]+$%', $options );
		foreach ( $class_array as $class ) {
			preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $class, $class_matches );
			if ( $class = $class_matches[1] )
				$class_att .= ' ' . $class;
		} 

		if ( $class_att )
			$atts .= ' class="' . trim( $class_att ) . '"';

		if ( $matches[2] )
			$value = wpcf7_strip_quote( $matches[2] );
		if ( empty( $value ) )
			$value = __( 'Send', 'wpcf7' );
		$ajax_loader_image_url = WPCF7_PLUGIN_URL . '/images/ajax-loader.gif';

		$html = '<input type="submit" value="' . $value . '"' . $atts . ' />';
		$html .= ' <img class="ajax-loader" style="visibility: hidden;" alt="ajax loader" src="' . $ajax_loader_image_url . '" />';
		return $html;
	}

	function form_element_parse( $element ) {
		$type = trim( $element[1] );
		$name = trim( $element[2] );
		$options = preg_split( '/[\s]+/', trim( $element[3] ) );

		preg_match_all( '/"[^"]*"|\'[^\']*\'/', $element[4], $matches );
		$raw_values = wpcf7_strip_quote_deep( $matches[0] );

		if ( WPCF7_USE_PIPE && preg_match( '/^(select[*]?|checkbox[*]?|radio)$/', $type ) || 'quiz' == $type ) {
			$pipes = wpcf7_get_pipes( $raw_values );
			$values = wpcf7_get_pipe_ins( $pipes );
		} else {
			$values =& $raw_values;
		}

		return compact( 'type', 'name', 'options', 'values', 'raw_values' );
	}
}

require_once WPCF7_PLUGIN_DIR . '/includes/classes.php';
require_once WPCF7_PLUGIN_DIR . '/includes/functions.php';
require_once WPCF7_PLUGIN_DIR . '/includes/formatting.php';
require_once WPCF7_PLUGIN_DIR . '/includes/form.php';
require_once WPCF7_PLUGIN_DIR . '/includes/mail.php';
require_once WPCF7_PLUGIN_DIR . '/includes/pipe.php';
require_once WPCF7_PLUGIN_DIR . '/includes/captcha.php';
require_once WPCF7_PLUGIN_DIR . '/includes/upload.php';

$wpcf7 = new tam_contact_form_seven();

function wpcf7_init_switch() {
	global $wpcf7;

	if ( 'POST' == $_SERVER['REQUEST_METHOD'] && 1 == (int) $_POST['_wpcf7_is_ajax_call'] ) {
		$wpcf7->ajax_json_echo();
		exit();
	} elseif ( ! is_admin() ) {
		$wpcf7->process_nonajax_submitting();
		wpcf7_cleanup_captcha_files();
		wpcf7_cleanup_upload_files();
	}
}

add_action( 'init', 'wpcf7_init_switch', 11 );

function wpcf7_load_plugin_textdomain() { // l10n
	global $wp_version;

	if ( version_compare( $wp_version, '2.6', '<' ) ) // Using old WordPress
		load_plugin_textdomain( 'wpcf7', 'wp-content/plugins/contact-form-7/languages' );
	else
		load_plugin_textdomain( 'wpcf7', 'wp-content/plugins/contact-form-7/languages', 'contact-form-7/languages' );
}

add_action( 'init', 'wpcf7_load_plugin_textdomain' );

function wpcf7_set_initial() {
	wpcf7_load_plugin_textdomain();

	$wpcf7 = get_option( 'wpcf7' );
	if ( ! is_array( $wpcf7 ) )
		$wpcf7 = array();

	$contact_forms = $wpcf7['contact_forms'];
	if ( ! is_array( $contact_forms ) )
		$contact_forms = array();

	if ( 0 == count( $contact_forms ) )
		$contact_forms[1] = wpcf7_default_pack( __( 'Contact form', 'wpcf7' ) . ' 1' );

	$wpcf7['contact_forms'] = $contact_forms;
	update_option( 'wpcf7', $wpcf7 );
}

if ( is_admin() ) {
	require_once WPCF7_PLUGIN_DIR . '/admin/admin.php';
	add_action( 'wp_print_scripts', 'wpcf7_admin_load_js' );
}

add_action( 'activate_' . WPCF7_PLUGIN_BASENAME, 'wpcf7_set_initial' );
add_action( 'admin_menu', 'wpcf7_admin_add_pages' );
add_action( 'admin_head', 'wpcf7_admin_head' );
add_action( 'wp_head', 'wpcf7_wp_head' );
add_action( 'wp_print_scripts', 'wpcf7_load_js' );

add_filter( 'the_content', array( $wpcf7, 'the_content_filter' ), 9 );
add_filter( 'widget_text', array( $wpcf7, 'widget_text_filter' ), 9 );

add_shortcode( 'contact-form', array( $wpcf7, 'contact_form_tag_func' ) );
?>