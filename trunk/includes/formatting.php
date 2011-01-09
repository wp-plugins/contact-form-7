<?php

function wpcf7_autop( $pee, $br = 1 ) {

	if ( trim( $pee ) === '' )
		return '';
	$pee = $pee . "\n"; // just to make things a little easier, pad the end
	$pee = preg_replace( '|<br />\s*<br />|', "\n\n", $pee );
	// Space things out a little
	/* wpcf7: remove select and input */
	$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
	$pee = preg_replace( '!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee );
	$pee = preg_replace( '!(</' . $allblocks . '>)!', "$1\n\n", $pee );
	$pee = str_replace( array( "\r\n", "\r" ), "\n", $pee ); // cross-platform newlines
	if ( strpos( $pee, '<object' ) !== false ) {
		$pee = preg_replace( '|\s*<param([^>]*)>\s*|', "<param$1>", $pee ); // no pee inside object/embed
		$pee = preg_replace( '|\s*</embed>\s*|', '</embed>', $pee );
	}
	$pee = preg_replace( "/\n\n+/", "\n\n", $pee ); // take care of duplicates
	// make paragraphs, including one at the end
	$pees = preg_split( '/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY );
	$pee = '';
	foreach ( $pees as $tinkle )
		$pee .= '<p>' . trim( $tinkle, "\n" ) . "</p>\n";
	$pee = preg_replace( '|<p>\s*</p>|', '', $pee ); // under certain strange conditions it could create a P of entirely whitespace
	$pee = preg_replace( '!<p>([^<]+)</(div|address|form|fieldset)>!', "<p>$1</p></$2>", $pee );
	$pee = preg_replace( '!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee ); // don't pee all over a tag
	$pee = preg_replace( "|<p>(<li.+?)</p>|", "$1", $pee ); // problem with nested lists
	$pee = preg_replace( '|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee );
	$pee = str_replace( '</blockquote></p>', '</p></blockquote>', $pee );
	$pee = preg_replace( '!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee );
	$pee = preg_replace( '!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee );
	if ( $br ) {
		/* wpcf7: add textarea */
		$pee = preg_replace_callback( '/<(script|style|textarea).*?<\/\\1>/s', create_function( '$matches', 'return str_replace("\n", "<WPPreserveNewline />", $matches[0]);' ), $pee );
		$pee = preg_replace( '|(?<!<br />)\s*\n|', "<br />\n", $pee ); // optionally make line breaks
		$pee = str_replace( '<WPPreserveNewline />', "\n", $pee );
	}
	$pee = preg_replace( '!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee );
	$pee = preg_replace( '!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee );
	if ( strpos( $pee, '<pre' ) !== false )
		$pee = preg_replace_callback( '!(<pre[^>]*>)(.*?)</pre>!is', 'clean_pre', $pee );
	$pee = preg_replace( "|\n</p>$|", '</p>', $pee );

	return $pee;
}

function wpcf7_strip_quote( $text ) {
	$text = trim( $text );
	if ( preg_match( '/^"(.*)"$/', $text, $matches ) )
		$text = $matches[1];
	elseif ( preg_match( "/^'(.*)'$/", $text, $matches ) )
		$text = $matches[1];
	return $text;
}

function wpcf7_strip_quote_deep( $arr ) {
	if ( is_string( $arr ) )
		return wpcf7_strip_quote( $arr );

	if ( is_array( $arr ) ) {
		$result = array();
		foreach ( $arr as $key => $text ) {
			$result[$key] = wpcf7_strip_quote( $text );
		}
		return $result;
	}
}

function wpcf7_canonicalize( $text ) {
	if ( function_exists( 'mb_convert_kana' ) && 'UTF-8' == get_option( 'blog_charset' ) )
		$text = mb_convert_kana( $text, 'asKV', 'UTF-8' );

	$text = strtolower( $text );
	$text = trim( $text );
	return $text;
}

function wpcf7_sanitize_file_name( $filename ) {
	/* Memo:
	// This function does sanitization introduced in http://core.trac.wordpress.org/ticket/11122
	// WordPress 2.8.6 will implement it in sanitize_file_name().
	// While Contact Form 7's file uploading function uses wp_unique_filename(), and
	// it in turn calls sanitize_file_name(). Therefore this wpcf7_sanitize_file_name() will be
	// redundant and unnecessary when you use Contact Form 7 on WordPress 2.8.6 or higher.
	// This function is provided just for the sake of protecting who uses older WordPress.
	*/

	// Split the filename into a base and extension[s]
	$parts = explode( '.', $filename );

	// Return if only one extension
	if ( count( $parts ) <= 2 )
		return $filename;

	// Process multiple extensions
	$filename = array_shift( $parts );
	$extension = array_pop( $parts );

	$mimes = array( 'jpg|jpeg|jpe', 'gif', 'png', 'bmp',
		'tif|tiff', 'ico', 'asf|asx|wax|wmv|wmx', 'avi',
		'divx', 'mov|qt', 'mpeg|mpg|mpe', 'txt|c|cc|h',
		'rtx', 'css', 'htm|html', 'mp3|m4a', 'mp4|m4v',
		'ra|ram', 'wav', 'ogg', 'mid|midi', 'wma', 'rtf',
		'js', 'pdf', 'doc|docx', 'pot|pps|ppt|pptx', 'wri',
		'xla|xls|xlsx|xlt|xlw', 'mdb', 'mpp', 'swf', 'class',
		'tar', 'zip', 'gz|gzip', 'exe',
		// openoffice formats
		'odt', 'odp', 'ods', 'odg', 'odc', 'odb', 'odf' );

	// Loop over any intermediate extensions.
	// Munge them with a trailing underscore if they are a 2 - 5 character
	// long alpha string not in the extension whitelist.
	foreach ( (array) $parts as $part) {
		$filename .= '.' . $part;

		if ( preg_match( '/^[a-zA-Z]{2,5}\d?$/', $part ) ) {
			$allowed = false;
			foreach ( $mimes as $ext_preg ) {
				$ext_preg = '!(^' . $ext_preg . ')$!i';
				if ( preg_match( $ext_preg, $part ) ) {
					$allowed = true;
					break;
				}
			}
			if ( ! $allowed )
				$filename .= '_';
		}
	}
	$filename .= '.' . $extension;

	return $filename;
}

function wpcf7_is_name( $string ) {
	// See http://www.w3.org/TR/html401/types.html#h-6.2
	// ID and NAME tokens must begin with a letter ([A-Za-z])
	// and may be followed by any number of letters, digits ([0-9]),
	// hyphens ("-"), underscores ("_"), colons (":"), and periods (".").

	return preg_match( '/^[A-Za-z][-A-Za-z0-9_:.]*$/', $string );
}

?>