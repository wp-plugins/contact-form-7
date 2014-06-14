<?php

class WPCF7_ContactForm {

	const post_type = 'wpcf7_contact_form';

	private static $found_items = 0;
	private static $current = null;

	private $initial = true;
	public $id;
	public $name;
	public $title;
	public $unit_tag;
	public $responses_count = 0;
	public $scanned_form_tags;

	public static function count() {
		return self::$found_items;
	}

	public static function set_current( self $obj ) {
		self::$current = $obj;
	}

	public static function get_current() {
		return self::$current;
	}

	public static function reset_current() {
		self::$current = null;
	}

	private static function get_unit_tag( $id = 0 ) {
		static $global_count = 0;

		$global_count += 1;

		if ( in_the_loop() ) {
			$unit_tag = sprintf( 'wpcf7-f%1$d-p%2$d-o%3$d',
				absint( $id ), get_the_ID(), $global_count );
		} else {
			$unit_tag = sprintf( 'wpcf7-f%1$d-o%2$d',
				absint( $id ), $global_count );
		}

		return $unit_tag;
	}

	public static function register_post_type() {
		register_post_type( self::post_type, array(
			'labels' => array(
				'name' => __( 'Contact Forms', 'contact-form-7' ),
				'singular_name' => __( 'Contact Form', 'contact-form-7' ) ),
			'rewrite' => false,
			'query_var' => false ) );
	}

	public static function find( $args = '' ) {
		$defaults = array(
			'post_status' => 'any',
			'posts_per_page' => -1,
			'offset' => 0,
			'orderby' => 'ID',
			'order' => 'ASC' );

		$args = wp_parse_args( $args, $defaults );

		$args['post_type'] = self::post_type;

		$q = new WP_Query();
		$posts = $q->query( $args );

		self::$found_items = $q->found_posts;

		$objs = array();

		foreach ( (array) $posts as $post )
			$objs[] = new self( $post );

		return $objs;
	}

	public function __construct( $post = null ) {
		$this->form = '';
		$this->mail = array();
		$this->mail_2 = array();
		$this->messages = array();
		$this->additional_settings = '';

		$post = get_post( $post );

		if ( $post && self::post_type == get_post_type( $post ) ) {
			$this->initial = false;
			$this->id = $post->ID;
			$this->name = $post->post_name;
			$this->title = $post->post_title;
			$this->locale = get_post_meta( $post->ID, '_locale', true );

			$props = $this->get_properties();

			foreach ( $props as $prop => $value ) {
				if ( metadata_exists( 'post', $post->ID, '_' . $prop ) )
					$this->{$prop} = get_post_meta( $post->ID, '_' . $prop, true );
				else
					$this->{$prop} = get_post_meta( $post->ID, $prop, true );
			}

			$this->upgrade();
		}

		do_action( 'wpcf7_contact_form', $this );
	}

	public static function generate_default_package( $args = '' ) {
		global $l10n;

		$defaults = array( 'locale' => null, 'title' => '' );
		$args = wp_parse_args( $args, $defaults );

		$locale = $args['locale'];
		$title = $args['title'];

		if ( $locale ) {
			$mo_orig = $l10n['contact-form-7'];
			wpcf7_load_textdomain( $locale );
		}

		$contact_form = new self;
		$contact_form->title =
			( $title ? $title : __( 'Untitled', 'contact-form-7' ) );
		$contact_form->locale = ( $locale ? $locale : get_locale() );

		$props = $contact_form->get_properties();

		foreach ( $props as $prop => $value ) {
			$contact_form->{$prop} = wpcf7_get_default_template( $prop );
		}

		$contact_form = apply_filters( 'wpcf7_contact_form_default_pack',
			$contact_form, $args );

		if ( isset( $mo_orig ) ) {
			$l10n['contact-form-7'] = $mo_orig;
		}

		return $contact_form;
	}

	public function initial() {
		return $this->initial;
	}

	public function get_properties() {
		$prop_names = array( 'form', 'mail', 'mail_2', 'messages', 'additional_settings' );

		$properties = array();

		foreach ( $prop_names as $prop_name )
			$properties[$prop_name] = isset( $this->{$prop_name} ) ? $this->{$prop_name} : '';

		return apply_filters( 'wpcf7_contact_form_properties', $properties, $this );
	}

	// Return true if this form is the same one as currently POSTed.
	public function is_posted() {
		if ( ! WPCF7_Submission::get_instance() ) {
			return false;
		}

		if ( empty( $_POST['_wpcf7_unit_tag'] ) ) {
			return false;
		}

		return $this->unit_tag == $_POST['_wpcf7_unit_tag'];
	}

	/* Generating Form HTML */

	public function form_html( $atts = array() ) {
		$atts = wp_parse_args( $atts, array(
			'html_id' => '',
			'html_name' => '',
			'html_class' => '' ) );

		$this->unit_tag = self::get_unit_tag( $this->id );

		$html = sprintf( '<div %s>', wpcf7_format_atts( array(
			'class' => 'wpcf7',
			'id' => $this->unit_tag,
			( get_option( 'html_type' ) == 'text/html' ) ? 'lang' : 'xml:lang'
				=> str_replace( '_', '-', $this->locale ),
			'dir' => wpcf7_is_rtl( $this->locale ) ? 'rtl' : 'ltr' ) ) ) . "\n";

		$html .= $this->screen_reader_response() . "\n";

		$url = wpcf7_get_request_uri();

		if ( $frag = strstr( $url, '#' ) )
			$url = substr( $url, 0, -strlen( $frag ) );

		$url .= '#' . $this->unit_tag;

		$url = apply_filters( 'wpcf7_form_action_url', $url );

		$id_attr = apply_filters( 'wpcf7_form_id_attr',
			preg_replace( '/[^A-Za-z0-9:._-]/', '', $atts['html_id'] ) );

		$name_attr = apply_filters( 'wpcf7_form_name_attr',
			preg_replace( '/[^A-Za-z0-9:._-]/', '', $atts['html_name'] ) );

		$class = 'wpcf7-form';

		if ( $this->is_posted() ) {
			$submission = WPCF7_Submission::get_instance();

			if ( $submission->is( 'validation_failed' ) ) {
				$class .= ' invalid';
			} elseif ( $submission->is( 'spam' ) ) {
				$class .= ' spam';
			} elseif ( $submission->is( 'mail_sent' ) ) {
				$class .= ' sent';
			} elseif ( $submission->is( 'mail_failed' ) ) {
				$class .= ' failed';
			}
		}

		if ( $atts['html_class'] ) {
			$class .= ' ' . $atts['html_class'];
		}

		if ( $this->in_demo_mode() ) {
			$class .= ' demo';
		}

		$class = explode( ' ', $class );
		$class = array_map( 'sanitize_html_class', $class );
		$class = array_filter( $class );
		$class = array_unique( $class );
		$class = implode( ' ', $class );
		$class = apply_filters( 'wpcf7_form_class_attr', $class );

		$enctype = apply_filters( 'wpcf7_form_enctype', '' );

		$novalidate = apply_filters( 'wpcf7_form_novalidate', wpcf7_support_html5() );

		$html .= sprintf( '<form %s>',
			wpcf7_format_atts( array(
				'action' => esc_url( $url ),
				'method' => 'post',
				'id' => $id_attr,
				'name' => $name_attr,
				'class' => $class,
				'enctype' => wpcf7_enctype_value( $enctype ),
				'novalidate' => $novalidate ? 'novalidate' : '' ) ) ) . "\n";

		$html .= $this->form_hidden_fields();
		$html .= $this->form_elements();

		if ( ! $this->responses_count )
			$html .= $this->form_response_output();

		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	public function form_hidden_fields() {
		$hidden_fields = array(
			'_wpcf7' => $this->id,
			'_wpcf7_version' => WPCF7_VERSION,
			'_wpcf7_locale' => $this->locale,
			'_wpcf7_unit_tag' => $this->unit_tag );

		if ( WPCF7_VERIFY_NONCE )
			$hidden_fields['_wpnonce'] = wpcf7_create_nonce( $this->id );

		$content = '';

		foreach ( $hidden_fields as $name => $value ) {
			$content .= '<input type="hidden"'
				. ' name="' . esc_attr( $name ) . '"'
				. ' value="' . esc_attr( $value ) . '" />' . "\n";
		}

		return '<div style="display: none;">' . "\n" . $content . '</div>' . "\n";
	}

	public function form_response_output() {
		$class = 'wpcf7-response-output';
		$role = '';
		$content = '';

		if ( $this->is_posted() ) { // Post response output for non-AJAX
			$role = 'alert';

			$submission = WPCF7_Submission::get_instance();
			$content = $submission->get_response();

			if ( $submission->is( 'validation_failed' ) ) {
				$class .= ' wpcf7-validation-errors';
			} elseif ( $submission->is( 'spam' ) ) {
				$class .= ' wpcf7-spam-blocked';
			} elseif ( $submission->is( 'mail_sent' ) ) {
				$class .= ' wpcf7-mail-sent-ok';
			} elseif ( $submission->is( 'mail_failed' ) ) {
				$class .= ' wpcf7-mail-sent-ng';
			}
		} else {
			$class .= ' wpcf7-display-none';
		}

		$atts = array(
			'class' => trim( $class ),
			'role' => trim( $role ) );

		$atts = wpcf7_format_atts( $atts );

		$output = sprintf( '<div %1$s>%2$s</div>',
			$atts, esc_html( $content ) );

		return apply_filters( 'wpcf7_form_response_output',
			$output, $class, $content, $this );
	}

	public function screen_reader_response() {
		$class = 'screen-reader-response';
		$role = '';
		$content = '';

		if ( $this->is_posted() ) { // Post response output for non-AJAX
			$role = 'alert';

			$submission = WPCF7_Submission::get_instance();

			if ( $response = $submission->get_response() ) {
				$content = esc_html( $response );
			}

			if ( $invalid_fields = $submission->get_invalid_fields() ) {
				$content .= "\n" . '<ul>' . "\n";

				foreach ( (array) $invalid_fields as $name => $field ) {
					if ( $field['idref'] ) {
						$link = sprintf( '<a href="#%1$s">%2$s</a>',
							esc_attr( $field['idref'] ),
							esc_html( $field['reason'] ) );
						$content .= sprintf( '<li>%s</li>', $link );
					} else {
						$content .= sprintf( '<li>%s</li>',
							esc_html( $field['reason'] ) );
					}

					$content .= "\n";
				}

				$content .= '</ul>' . "\n";
			}
		}

		$atts = array(
			'class' => trim( $class ),
			'role' => trim( $role ) );

		$atts = wpcf7_format_atts( $atts );

		$output = sprintf( '<div %1$s>%2$s</div>',
			$atts, $content );

		return $output;
	}

	public function validation_error( $name ) {
		$error = '';

		if ( $this->is_posted() ) {
			$submission = WPCF7_Submission::get_instance();

			if ( $invalid_field = $submission->get_invalid_field( $name ) ) {
				$error = trim( $invalid_field['reason'] );
			}
		}

		if ( ! $error ) {
			return $error;
		}

		$error = sprintf(
			'<span role="alert" class="wpcf7-not-valid-tip">%s</span>',
			esc_html( $error ) );

		return apply_filters( 'wpcf7_validation_error', $error, $name, $this );
	}

	/* Form Elements */

	public function form_do_shortcode() {
		$manager = WPCF7_ShortcodeManager::get_instance();
		$form = $this->form;

		if ( WPCF7_AUTOP ) {
			$form = $manager->normalize_shortcode( $form );
			$form = wpcf7_autop( $form );
		}

		$form = $manager->do_shortcode( $form );
		$this->scanned_form_tags = $manager->get_scanned_tags();

		return $form;
	}

	public function form_scan_shortcode( $cond = null ) {
		$manager = WPCF7_ShortcodeManager::get_instance();

		if ( ! empty( $this->scanned_form_tags ) ) {
			$scanned = $this->scanned_form_tags;
		} else {
			$scanned = $manager->scan_shortcode( $this->form );
			$this->scanned_form_tags = $scanned;
		}

		if ( empty( $scanned ) )
			return null;

		if ( ! is_array( $cond ) || empty( $cond ) )
			return $scanned;

		for ( $i = 0, $size = count( $scanned ); $i < $size; $i++ ) {

			if ( isset( $cond['type'] ) ) {
				if ( is_string( $cond['type'] ) && ! empty( $cond['type'] ) ) {
					if ( $scanned[$i]['type'] != $cond['type'] ) {
						unset( $scanned[$i] );
						continue;
					}
				} elseif ( is_array( $cond['type'] ) ) {
					if ( ! in_array( $scanned[$i]['type'], $cond['type'] ) ) {
						unset( $scanned[$i] );
						continue;
					}
				}
			}

			if ( isset( $cond['name'] ) ) {
				if ( is_string( $cond['name'] ) && ! empty( $cond['name'] ) ) {
					if ( $scanned[$i]['name'] != $cond['name'] ) {
						unset ( $scanned[$i] );
						continue;
					}
				} elseif ( is_array( $cond['name'] ) ) {
					if ( ! in_array( $scanned[$i]['name'], $cond['name'] ) ) {
						unset( $scanned[$i] );
						continue;
					}
				}
			}
		}

		return array_values( $scanned );
	}

	public function form_elements() {
		return apply_filters( 'wpcf7_form_elements', $this->form_do_shortcode() );
	}

	public function submit( $ajax = false ) {
		$submission = WPCF7_Submission::get_instance( $this );

		$result = array(
			'status' => $submission->get_status(),
			'message' => $submission->get_response() );

		if ( $submission->is( 'validation_failed' ) ) {
			$result['invalid_fields'] = $submission->get_invalid_fields();
		}

		if ( $submission->is( 'mail_sent' ) ) {
			if ( $this->in_demo_mode() ) {
				$result['status'] = 'demo_mode';
			}

			if ( $ajax ) {
				$on_sent_ok = $this->additional_setting( 'on_sent_ok', false );

				if ( ! empty( $on_sent_ok ) ) {
					$result['scripts_on_sent_ok'] = array_map(
						'wpcf7_strip_quote', $on_sent_ok );
				}
			}
		}

		if ( $ajax ) {
			$on_submit = $this->additional_setting( 'on_submit', false );

			if ( ! empty( $on_submit ) ) {
				$result['scripts_on_submit'] = array_map(
					'wpcf7_strip_quote', $on_submit );
			}
		}

		do_action( 'wpcf7_submit', $this, $result );

		return $result;
	}

	/* Message */

	public function message( $status ) {
		$messages = $this->messages;
		$message = isset( $messages[$status] ) ? $messages[$status] : '';
		$message = WPCF7_Mail::replace_tags( $message, true );

		return apply_filters( 'wpcf7_display_message', $message, $status );
	}

	/* Additional settings */

	public function additional_setting( $name, $max = 1 ) {
		$tmp_settings = (array) explode( "\n", $this->additional_settings );

		$count = 0;
		$values = array();

		foreach ( $tmp_settings as $setting ) {
			if ( preg_match('/^([a-zA-Z0-9_]+)[\t ]*:(.*)$/', $setting, $matches ) ) {
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

	public function is_true( $name ) {
		$settings = $this->additional_setting( $name, false );

		foreach ( $settings as $setting ) {
			if ( in_array( $setting, array( 'on', 'true', '1' ) ) )
				return true;
		}

		return false;
	}

	public function in_demo_mode() {
		return $this->is_true( 'demo_mode' );
	}

	/* Upgrade */

	public function upgrade() {
		if ( is_array( $this->mail ) ) {
			if ( ! isset( $this->mail['recipient'] ) )
				$this->mail['recipient'] = get_option( 'admin_email' );
		}

		if ( is_array( $this->messages ) ) {
			foreach ( wpcf7_messages() as $key => $arr ) {
				if ( ! isset( $this->messages[$key] ) )
					$this->messages[$key] = $arr['default'];
			}
		}
	}

	/* Save */

	public function save() {
		$props = $this->get_properties();

		$post_content = implode( "\n", wpcf7_array_flatten( $props ) );

		if ( $this->initial ) {
			$post_id = wp_insert_post( array(
				'post_type' => self::post_type,
				'post_status' => 'publish',
				'post_title' => $this->title,
				'post_content' => trim( $post_content ) ) );
		} else {
			$post_id = wp_update_post( array(
				'ID' => (int) $this->id,
				'post_status' => 'publish',
				'post_title' => $this->title,
				'post_content' => trim( $post_content ) ) );
		}

		if ( $post_id ) {
			foreach ( $props as $prop => $value )
				update_post_meta( $post_id, '_' . $prop, wpcf7_normalize_newline_deep( $value ) );

			if ( ! empty( $this->locale ) )
				update_post_meta( $post_id, '_locale', $this->locale );

			if ( $this->initial ) {
				$this->initial = false;
				$this->id = $post_id;
				do_action( 'wpcf7_after_create', $this );
			} else {
				do_action( 'wpcf7_after_update', $this );
			}

			do_action( 'wpcf7_after_save', $this );
		}

		return $post_id;
	}

	public function copy() {
		$new = new self;
		$new->title = $this->title . '_copy';
		$new->locale = $this->locale;

		$props = $this->get_properties();

		foreach ( $props as $prop => $value )
			$new->{$prop} = $value;

		$new = apply_filters( 'wpcf7_copy', $new, $this );

		return $new;
	}

	public function delete() {
		if ( $this->initial )
			return;

		if ( wp_delete_post( $this->id, true ) ) {
			$this->initial = true;
			$this->id = null;
			return true;
		}

		return false;
	}
}

function wpcf7_contact_form( $id ) {
	$contact_form = new WPCF7_ContactForm( $id );

	if ( $contact_form->initial() ) {
		return false;
	}

	return $contact_form;
}

function wpcf7_get_contact_form_by_old_id( $old_id ) {
	global $wpdb;

	$q = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_old_cf7_unit_id'"
		. $wpdb->prepare( " AND meta_value = %d", $old_id );

	if ( $new_id = $wpdb->get_var( $q ) )
		return wpcf7_contact_form( $new_id );
}

function wpcf7_get_contact_form_by_title( $title ) {
	$page = get_page_by_title( $title, OBJECT, WPCF7_ContactForm::post_type );

	if ( $page )
		return wpcf7_contact_form( $page->ID );

	return null;
}

function wpcf7_get_contact_form_default_pack( $args = '' ) {
	$contact_form = WPCF7_ContactForm::generate_default_package( $args );

	return $contact_form;
}

function wpcf7_get_current_contact_form() {
	if ( $current = WPCF7_ContactForm::get_current() ) {
		return $current;
	}
}

function wpcf7_is_posted() {
	if ( ! $contact_form = wpcf7_get_current_contact_form() )
		return false;

	return $contact_form->is_posted();
}

function wpcf7_get_hangover( $name, $default = null ) {
	if ( ! wpcf7_is_posted() ) {
		return $default;
	}

	$submission = WPCF7_Submission::get_instance();

	if ( ! $submission || $submission->is( 'mail_sent' ) ) {
		return $default;
	}

	return isset( $_POST[$name] ) ? wp_unslash( $_POST[$name] ) : $default;
}

function wpcf7_get_validation_error( $name ) {
	if ( ! $contact_form = wpcf7_get_current_contact_form() )
		return '';

	return $contact_form->validation_error( $name );
}

function wpcf7_get_message( $status ) {
	if ( ! $contact_form = wpcf7_get_current_contact_form() )
		return '';

	return $contact_form->message( $status );
}

function wpcf7_scan_shortcode( $cond = null ) {
	if ( ! $contact_form = wpcf7_get_current_contact_form() )
		return null;

	return $contact_form->form_scan_shortcode( $cond );
}

function wpcf7_form_controls_class( $type, $default = '' ) {
	$type = trim( $type );
	$default = array_filter( explode( ' ', $default ) );

	$classes = array_merge( array( 'wpcf7-form-control' ), $default );

	$typebase = rtrim( $type, '*' );
	$required = ( '*' == substr( $type, -1 ) );

	$classes[] = 'wpcf7-' . $typebase;

	if ( $required )
		$classes[] = 'wpcf7-validates-as-required';

	$classes = array_unique( $classes );

	return implode( ' ', $classes );
}

?>