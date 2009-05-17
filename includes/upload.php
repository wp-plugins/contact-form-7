<?php

function wpcf7_handle_uploads( $contact_form ) {
	$files = array();
	$valid = true;
	$reason = array();

	wpcf7_init_uploads(); // Confirm upload dir
	$uploads_dir = wpcf7_upload_tmp_dir();

	$fes = $contact_form->form_elements( false );

	foreach ( $fes as $fe ) {
		if ( 'file' != $fe['type'] && 'file*' != $fe['type'] )
			continue;

		$name = $fe['name'];
		$options = (array) $fe['options'];

		$file = $_FILES[$name];

		if ( empty( $file['tmp_name'] ) && 'file*' == $fe['type'] ) {
			$valid = false;
			$reason[$name] = $contact_form->message( 'invalid_required' );
			continue;
		}

		if ( ! is_uploaded_file( $file['tmp_name'] ) )
			continue;

		/* File type validation */

		$pattern = '';
		if ( $allowed_types_options = preg_grep( '%^filetypes:%', $options ) ) {
			foreach ( $allowed_types_options as $allowed_types_option ) {
				if ( preg_match( '%^filetypes:(.+)$%', $allowed_types_option, $matches ) ) {
					$file_types = explode( '|', $matches[1] );
					foreach ( $file_types as $file_type ) {
						$file_type = trim( $file_type, '.' );
						$file_type = str_replace( array( '.', '+', '*', '?' ), array( '\.', '\+', '\*', '\?' ), $file_type );
						$pattern .= '|' . $file_type;
					}
				}
			}
		}

		// Default file-type restriction
		if ( '' == $pattern )
			$pattern = 'jpg|jpeg|png|gif|pdf|doc|docx|ppt|pptx|odt|avi|ogg|m4a|mov|mp3|mp4|mpg|wav|wmv';

		$pattern = trim( $pattern, '|' );
		$pattern = '(' . $pattern . ')';
		$pattern = '/\.' . $pattern . '$/i';
		if ( ! preg_match( $pattern, $file['name'] ) ) {
			$valid = false;
			$reason[$name] = $contact_form->message( 'upload_file_type_invalid' );
			continue;
		}

		/* File size validation */

		$allowed_size = 1048576; // default size 1 MB
		if ( $allowed_size_options = preg_grep( '%^limit:%', $options ) ) {
			$allowed_size_option = array_shift( $allowed_size_options );
			preg_match( '/^limit:([1-9][0-9]*)$/', $allowed_size_option, $matches );
			$allowed_size = (int) $matches[1];
		}

		if ( $file['size'] > $allowed_size ) {
			$valid = false;
			$reason[$name] = $contact_form->message( 'upload_file_too_large' );
			continue;
		}

		$filename = wp_unique_filename( $uploads_dir, $file['name'] );

		// If you get script file, it's a danger. Make it TXT file.
		if ( preg_match( '/\.(php|pl|py|rb|cgi)\d?$/', $filename ) )
			$filename .= '.txt';

		$new_file = trailingslashit( $uploads_dir ) . $filename;
		if ( false === @move_uploaded_file( $file['tmp_name'], $new_file ) ) {
			$valid = false;
			$reason[$name] = $contact_form->message( 'upload_failed' );
			continue;
		}

		// Make sure the uploaded file is only readable for the owner process
		chmod( $new_file, 0400 );

		$files[$name] = $new_file;
	}

	$validation = compact( 'valid', 'reason' );

	return compact( 'files', 'validation' );
}

function wpcf7_init_uploads() {
	$dir = wpcf7_upload_tmp_dir();
	wp_mkdir_p( trailingslashit( $dir ) );
	@chmod( $dir, 0733 );

	$htaccess_file = trailingslashit( $dir ) . '.htaccess';
	if ( file_exists( $htaccess_file ) )
		return;

	if ( $handle = @fopen( $htaccess_file, 'w' ) ) {
		fwrite( $handle, "Deny from all\n" );
		fclose( $handle );
	}
}

function wpcf7_cleanup_upload_files() {
	$dir = wpcf7_upload_tmp_dir();
	$dir = trailingslashit( $dir );

	if ( ! is_dir( $dir ) || ! is_writable( $dir ) )
		return false;

	if ( $handle = opendir( $dir ) ) {
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( $file == "." || $file == ".." || $file == ".htaccess" )
				continue;

			$stat = stat( $dir . $file );
			if ( $stat['mtime'] + 60 < time() ) // 60 secs
				@ unlink( $dir . $file );
		}
		closedir( $handle );
	}
}

?>