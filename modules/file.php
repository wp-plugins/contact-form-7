<?php
/**
** A base module for [file] and [file*]
**/

function wpcf7_file_shortcode_handler( $tag ) {
	global $wpcf7_contact_form;

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	$atts = '';
	$id_att = '';
	$class_att = '';

	if ( 'file*' == $type )
		$class_att .= ' wpcf7-validates-as-required';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		}
	}

	if ( $id_att )
		$atts .= ' id="' . trim( $id_att ) . '"';

	if ( $class_att )
		$atts .= ' class="' . trim( $class_att ) . '"';

	$html = '<input type="file" name="' . $name . '"' . $atts . ' value="1" />';

	$validation_error = '';
	if ( is_a( $wpcf7_contact_form, 'WPCF7_ContactForm' ) )
		$validation_error = $wpcf7_contact_form->validation_error( $name );

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}

wpcf7_add_shortcode( 'file', 'wpcf7_file_shortcode_handler', true );
wpcf7_add_shortcode( 'file*', 'wpcf7_file_shortcode_handler', true );


/* Upload handling filter */

function wpcf7_file_upload_handling_filter( $handled_uploads ) {
	global $wpcf7_contact_form;

	$files = array();
	$valid = true;
	$reason = array();

	$uploads_dir = null;

	$fes = $wpcf7_contact_form->form_scan_shortcode(
		array( 'type' => array( 'file', 'file*' ) ) );

	foreach ( $fes as $fe ) {
		$name = $fe['name'];
		$options = (array) $fe['options'];

		$file = $_FILES[$name];

		if ( empty( $file['tmp_name'] ) && 'file*' == $fe['type'] ) {
			$valid = false;
			$reason[$name] = $wpcf7_contact_form->message( 'invalid_required' );
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
			$reason[$name] = $wpcf7_contact_form->message( 'upload_file_type_invalid' );
			continue;
		}

		/* File size validation */

		$allowed_size = 1048576; // default size 1 MB
		if ( $allowed_size_options = preg_grep( '%^limit:%', $options ) ) {
			$allowed_size_option = array_shift( $allowed_size_options );
			preg_match( '/^limit:([1-9][0-9]*)([kKmM]?[bB])?$/', $allowed_size_option, $matches );
			$allowed_size = (int) $matches[1];

			$kbmb = strtolower( $matches[2] );
			if ( 'kb' == $kbmb ) {
				$allowed_size *= 1024;
			} elseif ( 'mb' == $kbmb ) {
				$allowed_size *= 1024 * 1024;
			}
		}

		if ( $file['size'] > $allowed_size ) {
			$valid = false;
			$reason[$name] = $wpcf7_contact_form->message( 'upload_file_too_large' );
			continue;
		}

		if ( ! $uploads_dir ) {
			$uploads_dir = wpcf7_upload_tmp_dir();
			wpcf7_init_uploads(); // Confirm upload dir
		}

		$filename = wp_unique_filename( $uploads_dir, $file['name'] );

		// If you get script file, it's a danger. Make it TXT file.
		if ( preg_match( '/\.(php|pl|py|rb|cgi)\d?$/', $filename ) )
			$filename .= '.txt';

		$new_file = trailingslashit( $uploads_dir ) . $filename;
		if ( false === @move_uploaded_file( $file['tmp_name'], $new_file ) ) {
			$valid = false;
			$reason[$name] = $wpcf7_contact_form->message( 'upload_failed' );
			continue;
		}

		// Make sure the uploaded file is only readable for the owner process
		@chmod( $new_file, 0400 );

		$files[$name] = $new_file;
	}

	$validation = compact( 'valid', 'reason' );

	return compact( 'files', 'validation' );
}

add_filter( 'wpcf7_handled_uploads', 'wpcf7_file_upload_handling_filter' );


/* File uploading functions */

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
	$dir = trailingslashit( wpcf7_upload_tmp_dir() );

	if ( ! is_dir( $dir ) )
		return false;
	if ( ! is_readable( $dir ) )
		return false;
	if ( ! is_writable( $dir ) )
		return false;

	if ( $handle = @opendir( $dir ) ) {
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( $file == "." || $file == ".." || $file == ".htaccess" )
				continue;

			$stat = stat( $dir . $file );
			if ( $stat['mtime'] + 60 < time() ) // 60 secs
				@unlink( $dir . $file );
		}
		closedir( $handle );
	}
}

if ( ! is_admin() && 'GET' == $_SERVER['REQUEST_METHOD'] )
	wpcf7_cleanup_upload_files();

?>