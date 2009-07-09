<?php
/**
** A base module for [quiz]
**/

function wpcf7_quiz_shortcode_handler( $tag ) {
	global $wpcf7_contact_form;

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$pipes = $tag['pipes'];

	if ( is_object( $wpcf7_contact_form ) && $wpcf7_contact_form->is_posted() ) {
		$validation_error = $_POST['_wpcf7_validation_errors']['messages'][$name];
		$validation_error = $validation_error ? '<span class="wpcf7-not-valid-tip-no-ajax">' . esc_html( $validation_error ) . '</span>' : '';
	} else {
		$validation_error = '';
	}

	$atts = '';

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

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	if ( is_object( $pipes ) && ! $pipes->zero() ) {
		$pipe = $pipes->random_pipe();
		$question = $pipe->before;
		$answer = $pipe->after;
	} else {
		// default quiz
		$question = '1+1=?';
		$answer = '2';
	}

	$answer = wpcf7_canonicalize( $answer );

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

	$html = '<span class="wpcf7-quiz-label">' . esc_html( $question ) . '</span>&nbsp;';
	$html .= '<input type="text" name="' . $name . '"' . $atts . ' />';
	$html .= '<input type="hidden" name="_wpcf7_quiz_answer_' . $name . '" value="' . wp_hash( $answer, 'wpcf7_quiz' ) . '" />';
	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}

wpcf7_add_shortcode( 'quiz', 'wpcf7_quiz_shortcode_handler', true );

?>