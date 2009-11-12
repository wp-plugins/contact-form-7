<?php
/**
** A base module for [file] and [file*]
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'file', 'wpcf7_file_shortcode_handler', true );
wpcf7_add_shortcode( 'file*', 'wpcf7_file_shortcode_handler', true );

function wpcf7_file_shortcode_handler( $tag ) {
	global $wpcf7_contact_form;

	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	if ( empty( $name ) )
		return '';

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


/* Encode type filter */

add_filter( 'wpcf7_form_enctype', 'wpcf7_file_form_enctype_filter' );

function wpcf7_file_form_enctype_filter( $enctype ) {
	global $wpcf7_contact_form;

	$multipart = (bool) $wpcf7_contact_form->form_scan_shortcode(
		array( 'type' => array( 'file', 'file*' ) ) );

	if ( $multipart )
		$enctype = ' enctype="multipart/form-data"';

	return $enctype;
}


/* Validation + upload handling filter */

add_filter( 'wpcf7_validate_file', 'wpcf7_file_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_file*', 'wpcf7_file_validation_filter', 10, 2 );

function wpcf7_file_validation_filter( $result, $tag ) {
	global $wpcf7_contact_form;

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];

	$file = $_FILES[$name];

	if ( $file['error'] && UPLOAD_ERR_NO_FILE != $file['error'] ) {
		$result['valid'] = false;
		$result['reason'][$name] = $wpcf7_contact_form->message( 'upload_failed_php_error' );
		return $result;
	}

	if ( empty( $file['tmp_name'] ) && 'file*' == $type ) {
		$result['valid'] = false;
		$result['reason'][$name] = $wpcf7_contact_form->message( 'invalid_required' );
		return $result;
	}

	if ( ! is_uploaded_file( $file['tmp_name'] ) )
		return $result;

	$file_type_pattern = '';
	$allowed_size = 1048576; // default size 1 MB

	foreach ( $options as $option ) {
		if ( preg_match( '%^filetypes:(.+)$%', $option, $matches ) ) {
			$file_types = explode( '|', $matches[1] );
			foreach ( $file_types as $file_type ) {
				$file_type = trim( $file_type, '.' );
				$file_type = str_replace(
					array( '.', '+', '*', '?' ), array( '\.', '\+', '\*', '\?' ), $file_type );
				$file_type_pattern .= '|' . $file_type;
			}

		} elseif ( preg_match( '/^limit:([1-9][0-9]*)([kKmM]?[bB])?$/', $option, $matches ) ) {
			$allowed_size = (int) $matches[1];

			$kbmb = strtolower( $matches[2] );
			if ( 'kb' == $kbmb ) {
				$allowed_size *= 1024;
			} elseif ( 'mb' == $kbmb ) {
				$allowed_size *= 1024 * 1024;
			}

		}
	}

	/* File type validation */

	// Default file-type restriction
	if ( '' == $file_type_pattern )
		$file_type_pattern = 'jpg|jpeg|png|gif|pdf|doc|docx|ppt|pptx|odt|avi|ogg|m4a|mov|mp3|mp4|mpg|wav|wmv';

	$file_type_pattern = trim( $file_type_pattern, '|' );
	$file_type_pattern = '(' . $file_type_pattern . ')';
	$file_type_pattern = '/\.' . $file_type_pattern . '$/i';

	if ( ! preg_match( $file_type_pattern, $file['name'] ) ) {
		$result['valid'] = false;
		$result['reason'][$name] = $wpcf7_contact_form->message( 'upload_file_type_invalid' );
		return $result;
	}

	/* File size validation */

	if ( $file['size'] > $allowed_size ) {
		$result['valid'] = false;
		$result['reason'][$name] = $wpcf7_contact_form->message( 'upload_file_too_large' );
		return $result;
	}

	$uploads_dir = wpcf7_upload_tmp_dir();
	wpcf7_init_uploads(); // Confirm upload dir

	$filename = $file['name'];

	// If you get script file, it's a danger. Make it TXT file.
	if ( preg_match( '/\.(php|pl|py|rb|cgi)\d?$/', $filename ) )
		$filename .= '.txt';

	// foo.php.jpg => foo.php_.jpg
	$filename = wpcf7_sanitize_file_name( $filename );

	$filename = wp_unique_filename( $uploads_dir, $filename );

	$new_file = trailingslashit( $uploads_dir ) . $filename;

	if ( false === @move_uploaded_file( $file['tmp_name'], $new_file ) ) {
		$result['valid'] = false;
		$result['reason'][$name] = $wpcf7_contact_form->message( 'upload_failed' );
		return $result;
	}

	// Make sure the uploaded file is only readable for the owner process
	@chmod( $new_file, 0400 );

	$wpcf7_contact_form->uploaded_files[$name] = $new_file;

	return $result;
}


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

function wpcf7_upload_tmp_dir() {
	if ( defined( 'WPCF7_UPLOADS_TMP_DIR' ) )
		return WPCF7_UPLOADS_TMP_DIR;
	else
		return wpcf7_upload_dir( 'dir' ) . '/wpcf7_uploads';
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