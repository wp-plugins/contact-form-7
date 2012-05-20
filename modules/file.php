<?php
/**
** A base module for [file] and [file*]
**/

/* Shortcode handler */

wpcf7_add_shortcode( 'file', 'wpcf7_file_shortcode_handler', true );
wpcf7_add_shortcode( 'file*', 'wpcf7_file_shortcode_handler', true );

function wpcf7_file_shortcode_handler( $tag ) {
	if ( ! is_array( $tag ) )
		return '';

	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];
	$values = (array) $tag['values'];

	if ( empty( $name ) )
		return '';

	$validation_error = wpcf7_get_validation_error( $name );

	$atts = $id_att = $size_att = $tabindex_att = '';

	$class_att = wpcf7_form_controls_class( $type );

	if ( $validation_error )
		$class_att .= ' wpcf7-not-valid';

	foreach ( $options as $option ) {
		if ( preg_match( '%^id:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$id_att = $matches[1];

		} elseif ( preg_match( '%^class:([-0-9a-zA-Z_]+)$%', $option, $matches ) ) {
			$class_att .= ' ' . $matches[1];

		} elseif ( preg_match( '%^([0-9]*)[/x]([0-9]*)$%', $option, $matches ) ) {
			$size_att = (int) $matches[1];

		} elseif ( preg_match( '%^tabindex:(\d+)$%', $option, $matches ) ) {
			$tabindex_att = (int) $matches[1];

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

	if ( '' !== $tabindex_att )
		$atts .= sprintf( ' tabindex="%d"', $tabindex_att );

	$html = '<input type="file" name="' . $name . '"' . $atts . ' value="1" />';

	$html = '<span class="wpcf7-form-control-wrap ' . $name . '">' . $html . $validation_error . '</span>';

	return $html;
}


/* Encode type filter */

add_filter( 'wpcf7_form_enctype', 'wpcf7_file_form_enctype_filter' );

function wpcf7_file_form_enctype_filter( $enctype ) {
	$multipart = (bool) wpcf7_scan_shortcode( array( 'type' => array( 'file', 'file*' ) ) );

	if ( $multipart )
		$enctype = ' enctype="multipart/form-data"';

	return $enctype;
}


/* Validation + upload handling filter */

add_filter( 'wpcf7_validate_file', 'wpcf7_file_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_file*', 'wpcf7_file_validation_filter', 10, 2 );

function wpcf7_file_validation_filter( $result, $tag ) {
	$type = $tag['type'];
	$name = $tag['name'];
	$options = (array) $tag['options'];

	$file = isset( $_FILES[$name] ) ? $_FILES[$name] : null;

	if ( $file['error'] && UPLOAD_ERR_NO_FILE != $file['error'] ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'upload_failed_php_error' );
		return $result;
	}

	if ( empty( $file['tmp_name'] ) && 'file*' == $type ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
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
		$result['reason'][$name] = wpcf7_get_message( 'upload_file_type_invalid' );
		return $result;
	}

	/* File size validation */

	if ( $file['size'] > $allowed_size ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'upload_file_too_large' );
		return $result;
	}

	$uploads_dir = wpcf7_upload_tmp_dir();
	wpcf7_init_uploads(); // Confirm upload dir

	$filename = $file['name'];

	// If you get script file, it's a danger. Make it TXT file.
	if ( preg_match( '/\.(php|pl|py|rb|cgi)\d?$/', $filename ) )
		$filename .= '.txt';

	$filename = wp_unique_filename( $uploads_dir, $filename );

	$new_file = trailingslashit( $uploads_dir ) . $filename;

	if ( false === @move_uploaded_file( $file['tmp_name'], $new_file ) ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'upload_failed' );
		return $result;
	}

	// Make sure the uploaded file is only readable for the owner process
	@chmod( $new_file, 0400 );

	if ( $contact_form = wpcf7_get_current_contact_form() ) {
		$contact_form->uploaded_files[$name] = $new_file;

		if ( empty( $contact_form->posted_data[$name] ) )
			$contact_form->posted_data[$name] = $filename;
	}

	return $result;
}


/* Messages */

add_filter( 'wpcf7_messages', 'wpcf7_file_messages' );

function wpcf7_file_messages( $messages ) {
	return array_merge( $messages, array(
		'upload_failed' => array(
			'description' => __( "Uploading a file fails for any reason", 'wpcf7' ),
			'default' => __( 'Failed to upload file.', 'wpcf7' )
		),

		'upload_file_type_invalid' => array(
			'description' => __( "Uploaded file is not allowed file type", 'wpcf7' ),
			'default' => __( 'This file type is not allowed.', 'wpcf7' )
		),

		'upload_file_too_large' => array(
			'description' => __( "Uploaded file is too large", 'wpcf7' ),
			'default' => __( 'This file is too large.', 'wpcf7' )
		),

		'upload_failed_php_error' => array(
			'description' => __( "Uploading a file fails for PHP error", 'wpcf7' ),
			'default' => __( 'Failed to upload file. Error occurred.', 'wpcf7' )
		)
	) );
}


/* Tag generator */

add_action( 'admin_init', 'wpcf7_add_tag_generator_file', 50 );

function wpcf7_add_tag_generator_file() {
	wpcf7_add_tag_generator( 'file', __( 'File upload', 'wpcf7' ),
		'wpcf7-tg-pane-file', 'wpcf7_tg_pane_file' );
}

function wpcf7_tg_pane_file( &$contact_form ) {
?>
<div id="wpcf7-tg-pane-file" class="hidden">
<form action="">
<table>
<tr><td><input type="checkbox" name="required" />&nbsp;<?php echo esc_html( __( 'Required field?', 'wpcf7' ) ); ?></td></tr>
<tr><td><?php echo esc_html( __( 'Name', 'wpcf7' ) ); ?><br /><input type="text" name="name" class="tg-name oneline" /></td><td></td></tr>
</table>

<table>
<tr>
<td><code>id</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="id" class="idvalue oneline option" /></td>

<td><code>class</code> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="class" class="classvalue oneline option" /></td>
</tr>

<tr>
<td><?php echo esc_html( __( "File size limit", 'wpcf7' ) ); ?> (<?php echo esc_html( __( 'bytes', 'wpcf7' ) ); ?>) (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="limit" class="filesize oneline option" /></td>

<td><?php echo esc_html( __( "Acceptable file types", 'wpcf7' ) ); ?> (<?php echo esc_html( __( 'optional', 'wpcf7' ) ); ?>)<br />
<input type="text" name="filetypes" class="filetype oneline option" /></td>
</tr>
</table>

<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'wpcf7' ) ); ?><br /><input type="text" name="file" class="tag" readonly="readonly" onfocus="this.select()" /></div>

<div class="tg-mail-tag"><?php echo esc_html( __( "And, put this code into the File Attachments field below.", 'wpcf7' ) ); ?><br /><span class="arrow">&#11015;</span>&nbsp;<input type="text" class="mail-tag" readonly="readonly" onfocus="this.select()" /></div>
</form>
</div>
<?php
}


/* Warning message */

add_action( 'wpcf7_admin_notices', 'wpcf7_file_display_warning_message' );

function wpcf7_file_display_warning_message() {
	if ( empty( $_GET['post'] ) || ! $contact_form = wpcf7_contact_form( $_GET['post'] ) )
		return;

	$has_tags = (bool) $contact_form->form_scan_shortcode(
		array( 'type' => array( 'file', 'file*' ) ) );

	if ( ! $has_tags )
		return;

	$uploads_dir = wpcf7_upload_tmp_dir();
	wpcf7_init_uploads();

	if ( ! is_dir( $uploads_dir ) || ! is_writable( $uploads_dir ) ) {
		$message = sprintf( __( 'This contact form contains file uploading fields, but the temporary folder for the files (%s) does not exist or is not writable. You can create the folder or change its permission manually.', 'wpcf7' ), $uploads_dir );

		echo '<div class="error"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
	}
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