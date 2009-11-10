<?php
/**
** A base module for [quiz]
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'quiz', 'wpcf7_quiz_shortcode_handler', true );

function wpcf7_quiz_shortcode_handler( $tag ) {
	global $wpcf7_contact_form;

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$pipes = $tag['pipes'];

	if ( empty( $name ) )
		return '';

	$atts = '';
	$id_att = '';
	$class_att = '';
	$size_att = '';
	$maxlength_att = '';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^([0-9]*)[/x]([0-9]*)$%', $option, $matches ) ) {
			$size_att = (int) $matches[1];
			$maxlength_att = (int) $matches[2];
		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	if ( $size_att )
		$atts .= ' size="' . $size_att . '"';
	else
		$atts .= ' size="40"'; // default size

	if ( $maxlength_att )
		$atts .= ' maxlength="' . $maxlength_att . '"';

	if ( is_a( $pipes, 'WPCF7_Pipes' ) && ! $pipes->zero() ) {
		$pipe = $pipes->random_pipe();
		$question = $pipe->before;
		$answer = $pipe->after;
	} else {
		// default quiz
		$question = '1+1=?';
		$answer = '2';
	}

	$answer = wpcf7_canonicalize( $answer );

	$html = '<span class="wpcf7-quiz-label">' . esc_html( $question ) . '</span>&nbsp;';
	$html .= '<input type="text" name="' . $name . '"' . $atts . ' />';
	$html .= '<input type="hidden" name="_wpcf7_quiz_answer_' . $name . '" value="' . wp_hash( $answer, 'wpcf7_quiz' ) . '" />';

	$validation_error = '';
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		$validation_error = $wpcf7_contact_form->validation_error( $name );

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_quiz', 'wpcf7_quiz_validation_filter', 10, 2 );

function wpcf7_quiz_validation_filter( $result, $tag ) {
	global $wpcf7_contact_form;

	$type = $tag['type'];
	$name = $tag['name'];

	$answer = wpcf7_canonicalize( $_POST[$name] );
	$answer_hash = wp_hash( $answer, 'wpcf7_quiz' );
	$expected_hash = $_POST['_wpcf7_quiz_answer_' . $name];
	if ( $answer_hash != $expected_hash ) {
		$result['valid'] = false;
		$result['reason'][$name] = $wpcf7_contact_form->message( 'quiz_answer_not_correct' );
	}

	return $result;
}


/* Ajax echo filter */

add_filter( 'wpcf7_ajax_json_echo', 'wpcf7_quiz_ajax_echo_filter' );

function wpcf7_quiz_ajax_echo_filter( $items ) {
	global $wpcf7_contact_form;

	if ( ! is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		return $items;

	if ( ! is_array( $items ) )
		return $items;

	$fes = $wpcf7_contact_form->form_scan_shortcode(
		array( 'type' => 'quiz' ) );

	if ( empty( $fes ) )
		return $items;

	$refill = array();

	foreach ( $fes as $fe ) {
		$fe = apply_filters( 'wpcf7_form_tag', $fe );

		$name = $fe['name'];
		$pipes = $fe['pipes'];

		if ( empty( $name ) )
			continue;

		if ( is_a( $pipes, 'WPCF7_Pipes' ) && ! $pipes->zero() ) {
			$pipe = $pipes->random_pipe();
			$question = $pipe->before;
			$answer = $pipe->after;
		} else {
			// default quiz
			$question = '1+1=?';
			$answer = '2';
		}

		$answer = wpcf7_canonicalize( $answer );

		$refill[$name] = array( $question, wp_hash( $answer, 'wpcf7_quiz' ) );
	}

	if ( ! empty( $refill ) )
		$items['quiz'] = $refill;

	return $items;
}

?>