<?php

class WPCF7_ContactForm {

	var $id;

	var $title;
	var $form;
	var $mail;
	var $mail_2;
	var $messages;
	var $additional_settings;
	var $options;

	var $unit_tag;

	var $responses_count = 0;

	// Return true if this form is the same one as currently POSTed.
	function is_posted() {
		if ( ! isset( $_POST['_wpcf7_unit_tag'] ) || empty( $_POST['_wpcf7_unit_tag'] ) )
			return false;

		if ( $this->unit_tag == $_POST['_wpcf7_unit_tag'] )
			return true;

		return false;
	}

	/* Generating Form HTML */

	function form_html() {
		$form = '<div class="wpcf7" id="' . $this->unit_tag . '">';

		$url = parse_url( $_SERVER['REQUEST_URI'] );
		$url = $url['path'] . ( empty( $url['query'] ) ? '' : '?' . $url['query'] ) . '#' . $this->unit_tag;

		$form_elements = $this->form_elements( false );
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
		$form .= '<input type="hidden" name="_wpcf7" value="' . $this->id . '" />';
		$form .= '<input type="hidden" name="_wpcf7_version" value="' . WPCF7_VERSION . '" />';
		$form .= '<input type="hidden" name="_wpcf7_unit_tag" value="' . $this->unit_tag . '" />';
		$form .= '</div>';
		$form .= $this->form_elements();

		if ( ! $this->responses_count )
			$form .= $this->form_response_output();

		$form .= '</form>';

		$form .= '</div>';

		if ( WPCF7_AUTOP )
			$form = wpcf7_wpautop_substitute( $form );

		return $form;
	}

	function form_response_output() {
		$class = 'wpcf7-response-output';

		if ( $this->is_posted() ) { // Post response output for non-AJAX
			if ( isset( $_POST['_wpcf7_mail_sent'] ) && $_POST['_wpcf7_mail_sent']['id'] == $this->id ) {
				if ( $_POST['_wpcf7_mail_sent']['ok'] ) {
					$class .= ' wpcf7-mail-sent-ok';
					$content = $_POST['_wpcf7_mail_sent']['message'];
				} else {
					$class .= ' wpcf7-mail-sent-ng';
					if ( $_POST['_wpcf7_mail_sent']['spam'] )
						$class .= ' wpcf7-spam-blocked';
					$content = $_POST['_wpcf7_mail_sent']['message'];
				}
			} elseif ( isset( $_POST['_wpcf7_validation_errors'] ) && $_POST['_wpcf7_validation_errors']['id'] == $this->id ) {
				$class .= ' wpcf7-validation-errors';
				$content = $this->message( 'validation_error' );
			}
		} else {
			$class .= ' wpcf7-display-none';
		}

		$class = ' class="' . $class . '"';

		return '<div' . $class . '>' . $content . '</div>';
	}

	/* Form Elements */

	function form_elements( $replace = true ) {
		$form = $this->form;

		$types = 'text[*]?|email[*]?|textarea[*]?|select[*]?|checkbox[*]?|radio|acceptance|captchac|captchar|file[*]?|quiz';
		$regex = '%\[\s*(' . $types . ')(\s+[a-zA-Z][0-9a-zA-Z:._-]*)([-0-9a-zA-Z:#_/|\s]*)?((?:\s*(?:"[^"]*"|\'[^\']*\'))*)?\s*\]%';
		$submit_regex = '%\[\s*submit(\s[-0-9a-zA-Z:#_/\s]*)?(\s+(?:"[^"]*"|\'[^\']*\'))?\s*\]%';
		$response_regex = '%\[\s*response\s*\]%';
		if ( $replace ) {
			$form = preg_replace_callback( $regex, array( &$this, 'form_element_replace_callback' ), $form );
			// Submit button
			$form = preg_replace_callback( $submit_regex, array( &$this, 'submit_replace_callback' ), $form );
			// Response output
			$form = preg_replace_callback( $response_regex, array( &$this, 'response_replace_callback' ), $form );
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
		global $wpdb;

		extract( (array) $this->form_element_parse( $matches ) ); // $type, $name, $options, $values, $raw_values

		if ( $this->is_posted() ) {
			$validation_error = $_POST['_wpcf7_validation_errors']['messages'][$name];
			$validation_error = $validation_error ? '<span class="wpcf7-not-valid-tip-no-ajax">' . esc_html( $validation_error ) . '</span>' : '';
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
		if ( $this->is_posted() ) {
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
				$html = '<input type="text" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $atts . ' />';
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
				$html = '<textarea name="' . $name . '"' . $atts . '>' . esc_html( $value ) . '</textarea>';
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
					if ( $this->is_posted() && (
						$multiple && in_array( $wpdb->escape( $value ), (array) $_POST[$name] ) ||
							! $multiple && $_POST[$name] == $wpdb->escape( $value ) ) )
						$selected = ' selected="selected"';
					$html .= '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
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
					if ( $this->is_posted() && (
						$multiple && in_array( $wpdb->escape( $value ), (array) $_POST[$name] ) ||
							! $multiple && $_POST[$name] == $wpdb->escape( $value ) ) )
						$checked = ' checked="checked"';
					if ( preg_grep( '%^label[_-]?first$%', $options ) ) { // put label first, input last
						$item = '<span class="wpcf7-list-item-label">' . $value . '</span>&nbsp;';
						$item .= '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . esc_attr( $value ) . '"' . $checked . $onclick . ' />';
					} else {
						$item = '<input type="' . $input_type . '" name="' . $name . ( $multiple ? '[]' : '' ) . '" value="' . esc_attr( $value ) . '"' . $checked . $onclick . ' />';
						$item .= '&nbsp;<span class="wpcf7-list-item-label">' . $value . '</span>';
					}

					if ( preg_grep( '%^use[_-]?label[_-]?element$%', $options ) )
						$item = '<label>' . $item . '</label>';

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
                
				$html = '<span class="wpcf7-quiz-label">' . esc_html( $value ) . '</span>&nbsp;';
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
		$ajax_loader_image_url = wpcf7_plugin_url( 'images/ajax-loader.gif' );

		$html = '<input type="submit" value="' . esc_attr( $value ) . '"' . $atts . ' />';
		$html .= ' <img class="ajax-loader" style="visibility: hidden;" alt="ajax loader" src="' . $ajax_loader_image_url . '" />';
		return $html;
	}

	function response_replace_callback( $matches ) {
		$this->responses_count += 1;
		return $this->form_response_output();
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

	/* Validate */

	function validate() {
		$fes = $this->form_elements( false );
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
					$reason[$name] = $this->message( 'invalid_required' );
				}
			}

			if ( 'checkbox*' == $type ) {
				if ( empty( $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = $this->message( 'invalid_required' );
				}
			}

			if ( 'select*' == $type ) {
				if ( empty( $_POST[$name] ) ||
						! is_array( $_POST[$name] ) && '---' == $_POST[$name] ||
						is_array( $_POST[$name] ) && 1 == count( $_POST[$name] ) && '---' == $_POST[$name][0] ) {
					$valid = false;
					$reason[$name] = $this->message( 'invalid_required' );
				}
			}

			if ( preg_match( '/^email[*]?$/', $type ) ) {
				if ( '*' == substr( $type, -1 ) && ( ! isset( $_POST[$name] ) || '' == $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = $this->message( 'invalid_required' );
				} elseif ( isset( $_POST[$name] ) && '' != $_POST[$name] && ! is_email( $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = $this->message( 'invalid_email' );
				}
			}

			if ( preg_match( '/^captchar$/', $type ) ) {
				$captchac = '_wpcf7_captcha_challenge_' . $name;
				if ( ! wpcf7_check_captcha( $_POST[$captchac], $_POST[$name] ) ) {
					$valid = false;
					$reason[$name] = $this->message( 'captcha_not_match' );
				}
				wpcf7_remove_captcha( $_POST[$captchac] );
			}

			if ( 'quiz' == $type ) {
				$answer = wpcf7_canonicalize( $_POST[$name] );
				$answer_hash = wp_hash( $answer, 'wpcf7_quiz' );
				$expected_hash = $_POST['_wpcf7_quiz_answer_' . $name];
				if ( $answer_hash != $expected_hash ) {
					$valid = false;
					$reason[$name] = $this->message( 'quiz_answer_not_correct' );
				}
			}
		}
		return compact( 'valid', 'reason' );
	}

	/* Message */

	function message( $status ) {
		$messages = $this->messages;

		if ( ! is_array( $messages ) || ! isset( $messages[$status] ) )
			return wpcf7_default_message( $status );

		return $messages[$status];
	}

	/* Additional settings */

	function additional_setting( $name, $max = 1 ) {
		$tmp_settings = (array) explode( "\n", $this->additional_settings );

		$count = 0;
		$values = array();

		foreach ( $tmp_settings as $setting ) {
			if ( preg_match('/^([a-zA-Z0-9_]+)\s*:(.*)$/', $setting, $matches ) ) {
				if ( $matches[1] != $name )
					continue;

				if ( ! $max || $count < (int) $max ) {
					$values[] = trim( $matches[2] );
					$count += 1;
				}
			}
		}

		return $values;
	}

	/* Upgrade */

	function upgrade() {
		if ( ! isset( $this->mail['recipient'] ) )
			$this->mail['recipient'] = $this->options['recipient'];


		if ( ! is_array( $this->messages ) )
			$this->messages = array();

		$messages = array(
			'mail_sent_ok', 'mail_sent_ng', 'akismet_says_spam', 'validation_error', 'accept_terms',
			'invalid_email', 'invalid_required', 'captcha_not_match', 'upload_failed', 'upload_file_type_invalid',
			'upload_file_too_large', 'quiz_answer_not_correct' );

		foreach ($messages as $message) {
			if ( ! isset( $this->messages[$message] ) )
				$this->messages[$message] = wpcf7_default_message( $message );
		}
	}

}

?>