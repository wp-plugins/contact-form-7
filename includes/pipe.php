<?php

function wpcf7_pipe( $pipes, $value ) {
	if ( is_array( $value ) ) {
		$results = array();
		foreach ( $value as $k => $v ) {
			$results[$k] = wpcf7_pipe( $pipes, $v );
		}
		return $results;
	}

	foreach ( $pipes as $p ) {
		if ( $p[0] == $value )
			return $p[1];
	}

	return $value;
}

function wpcf7_get_pipe_ins( $pipes ) {
	$ins = array();
	foreach ( $pipes as $pipe ) {
		$in = $pipe[0];
		if ( ! in_array( $in, $ins ) )
			$ins[] = $in;
	}
	return $ins;
}

function wpcf7_get_pipes( $values ) {
	$pipes = array();

	foreach ( $values as $value ) {
		$pipe_pos = strpos( $value, '|' );
		if ( false === $pipe_pos ) {
			$before = $after = $value;
		} else {
			$before = substr( $value, 0, $pipe_pos );
			$after = substr( $value, $pipe_pos + 1 );
		}

		$pipes[] = array( $before, $after );
	}

	return $pipes;
}

function wpcf7_pipe_all_posted( $contact_form ) {
	global $wpcf7_posted_data;

	$all_pipes = array();

	$fes = $contact_form->form_elements( false );
	foreach ( $fes as $fe ) {
		$type = $fe['type'];
		$name = $fe['name'];
		$raw_values = $fe['raw_values'];

		if ( ! preg_match( '/^(select[*]?|checkbox[*]?|radio)$/', $type ) )
			continue;

		$pipes = wpcf7_get_pipes( $raw_values );

		$all_pipes[$name] = array_merge( $pipes, (array) $all_pipes[$name] );
	}

	foreach ( $all_pipes as $name => $pipes ) {
		if ( isset( $wpcf7_posted_data[$name] ) )
			$wpcf7_posted_data[$name] = wpcf7_pipe( $pipes, $wpcf7_posted_data[$name] );
	}
}

?>