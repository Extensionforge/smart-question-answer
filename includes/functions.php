<?php
/**
 * SmartQa common functions.
 *
 * @package SmartQa
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-3.0+
 * @link      https://extensionforge.com
 * @copyright 2014 Peter Mertzlin
 */



// To show the column header
function custom_column_header_moderator( $columns ){
  $columns['moderator'] = 'Moderator(en)'; 
  return $columns;
}

add_filter( "manage_edit-question_category_columns", 'custom_column_header_moderator', 10,2);

function manage_category_custom_fields($deprecated,$column_name,$term_id)
{
 if ($column_name == 'moderator') {
 	$term = get_term($term_id);

 	global $wpdb;
	
	$users = $wpdb->prefix . 'users';
	$usersid = $wpdb->prefix . 'users.ID';
	$capabilities = $wpdb->prefix . 'capabilities';
	$usermeta = $wpdb->prefix . 'usermeta';
	$usermetauserid = $wpdb->prefix . 'usermeta.user_id';
	$usermetakey = $wpdb->prefix . 'usermeta.meta_key';
	$usermetavalue = $wpdb->prefix . 'usermeta.meta_value';
	$moderators = $wpdb->prefix . 'asqa_moderators';
	$moderatorsid = $wpdb->prefix . 'asqa_moderators.user_id';
	$cat_id = $wpdb->prefix . 'asqa_moderators.cat_id';

 	$moderatoren = $wpdb->get_results( "SELECT * FROM $moderators inner join $users ON $usersid=$moderatorsid where $cat_id='$term_id'"); 
 	$output = "";
 	foreach($moderatoren as $singlemod){
 		$output .= $singlemod->user_login.",";
 	}

	echo substr($output,0,strlen($output)-1); 
   
 }
}
add_filter ('manage_question_category_custom_column', 'manage_category_custom_fields', 10,3);

function crunchify_reorder_columns($columns) {
  $crunchify_columns = array();
  $categories = 'moderator'; 
  $title = 'description'; 
  foreach($columns as $key => $value) {
    if ($key==$title){
      $crunchify_columns[$categories] = $categories;
    }
      $crunchify_columns[$key] = $value;
  }
  return $crunchify_columns;
}
add_filter('manage_edit-question_category_columns', 'crunchify_reorder_columns');




function asqa_delete_attachment_forced() {
    
    $attachmentid = $_POST["attachmentid"]; 
  
    $ok = wp_delete_attachment($attachmentid, true); 

    if ($ok==true) {

		$meta         = wp_get_attachment_metadata( $attachmentid );
		$backup_sizes = get_post_meta( $attachmentid, '_wp_attachment_backup_sizes', true );
		$file         = get_attached_file( $attachmentid );

		wp_delete_attachment_files( $attachmentid, $meta, $backup_sizes, $file );

    $msg = array( "code" => "success", "message" => __( "Die Datei wurde gelöscht.", "smart-question-answer" ) );
            } else {
            $msg = array( "code" => "error", "message" => __( "Achtung. Die Datei konnte nicht gelöscht werden!", "smart-question-answer" ) );
        }
   
    wp_send_json( $msg );
}


add_action('wp_ajax_nopriv_asqa_delete_attachment_forced', 'asqa_delete_attachment_forced');
add_action('wp_ajax_asqa_delete_attachment_forced', 'asqa_delete_attachment_forced');




/**
 * Get slug of base page.
 *
 * @return string
 * @since  2.0.0
 * @since  1.0.0 Return `questions` if base page is not selected.
 * @since  1.0.0 Make sure always `questions` is returned if no base page is set.
 */
function asqa_base_page_slug() {
	$slug = 'questions';

	if ( ! empty( asqa_opt( 'base_page' ) ) ) {
		$base_page = get_post( asqa_opt( 'base_page' ) );

		if ( $base_page ) {
			$slug = $base_page->post_name;

			if ( $base_page->post_parent > 0 ) {
				$parent_page = get_post( $base_page->post_parent );
				$slug        = $parent_page->post_name . '/' . $slug;
			}
		}
	}

	return apply_filters( 'asqa_base_page_slug', $slug );
}

/**
 * Retrieve permalink to base page.
 *
 * @return  string URL to SmartQa base page
 * @since   2.0.0
 * @since   1.0.0 Return link to questions page if base page not selected.
 */
function asqa_base_page_link() {
	if ( empty( asqa_opt( 'base_page' ) ) ) {
		return home_url( '/questions/' );
	}
	return get_permalink( asqa_opt( 'base_page' ) );
}

/**
 * Get location to a file. First file is being searched in child theme and then active theme
 * and last fall back to SmartQa theme directory.
 *
 * @param   string $file   file name.
 * @param   mixed  $plugin Plugin path. File is search inside SmartQa extension.
 * @return  string
 * @since   0.1
 * @since   2.4.7 Added filter `asqa_get_theme_location`
 */
function asqa_get_theme_location( $file, $plugin = false ) {
	$child_path  = get_stylesheet_directory() . '/smartqa/' . $file;
	$parent_path = get_template_directory() . '/smartqa/' . $file;

	// Checks if the file exists in the theme first,
	// Otherwise serve the file from the plugin.
	if ( file_exists( $child_path ) ) {
		$template_path = $child_path;
	} elseif ( file_exists( $parent_path ) ) {
		$template_path = $parent_path;
	} elseif ( false !== $plugin ) {
		$template_path = $plugin . '/templates/' . $file;
	} else {
		$template_path = SMARTQA_THEME_DIR . '/' . $file;
	}

	/**
	 * Filter SmartQa template file.
	 *
	 * @param string $template_path Path to template file.
	 * @since 2.4.7
	 */
	return apply_filters( 'asqa_get_theme_location', $template_path );
}

/**
 * Get url to a file
 * Used for enqueue CSS or JS.
 *
 * @param  string  $file   File name.
 * @param  mixed   $plugin Plugin path, if calling from SmartQa extension.
 * @param  boolean $ver    When true, SmartQa version will be appended to file url.
 * @return string
 * @since  2.0
 */
function asqa_get_theme_url( $file, $plugin = false, $ver = true ) {
	$child_path  = get_stylesheet_directory() . '/smartqa/' . $file;
	$parent_path = get_template_directory() . '/smartqa/' . $file;

	// Checks if the file exists in the theme first.
	// Otherwise serve the file from the plugin.
	if ( file_exists( $child_path ) ) {
		$template_url = get_stylesheet_directory_uri() . '/smartqa/' . $file;
	} elseif ( file_exists( $parent_path ) ) {
		$template_url = get_template_directory_uri() . '/smartqa/' . $file;
	} elseif ( false !== $plugin ) {
		$template_url = $plugin . 'templates/' . $file;
	} else {
		$template_url = SMARTQA_THEME_URL . '/' . $file;
	}

	/**
	 * Allows filtering url of a SmartQa file.
	 *
	 * @param string $url Url of a file.
	 * @since 2.0
	 */
	return apply_filters( 'asqa_theme_url', $template_url . ( true === $ver ? '?v=' . ASQA_VERSION : '' ) );
}


/**
 * Check if current page is SmartQa. Also check if showing question or
 * answer page in BuddyPress.
 *
 * @return boolean
 * @since 1.0.0 Improved check. Check for main pages.
 * @since 1.0.0 Check for @see asqa_current_page().
 * @since 1.0.0 Added filter `is_smartqa`.
 */
function is_smartqa() {
	$ret = false;

	// If BuddyPress installed.
	if ( function_exists( 'bp_current_component' ) ) {
		if ( in_array( bp_current_component(), array( 'qa', 'questions', 'answers' ), true ) ) {
			$ret = true;
		}
	}

	$page_slug      = array_keys( asqa_main_pages() );
	$queried_object = get_queried_object();

	// Check if main pages.
	if ( $queried_object instanceof WP_Post ) {
		$page_ids = array();
		foreach ( $page_slug as $slug ) {
			$page_ids[] = asqa_opt( $slug );
		}

		if ( in_array( $queried_object->ID, $page_ids ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			$ret = true;
		}
	}

	// Check if asqa_page.
	if ( is_search() && 'question' === get_query_var( 'post_type' ) ) {
		$ret = true;
	} elseif ( '' !== asqa_current_page() ) {
		$ret = true;
	}

	/**
	 * Filter for overriding is_smartqa() return value.
	 *
	 * @param boolean $ret True or false.
	 * @since 1.0.0
	 */
	return apply_filters( 'is_smartqa', $ret );
}

/**
 * Check if current page is question page.
 *
 * @return boolean
 * @since 0.0.1
 * @since 1.0.0 Also check and return true if singular question.
 */
function is_question() {
	if ( is_singular( 'question' ) ) {
		return true;
	}

	return false;
}

/**
 * Is if current SmartQa page is ask page.
 *
 * @return boolean
 */
function is_ask() {
	if ( is_smartqa() && 'ask' === asqa_current_page() ) {
		return true;
	}
	return false;
}

/**
 * Get current question ID in single question page.
 *
 * @return integer
 * @since unknown
 * @since 1.0.0 Remove `question_name` query var check. Get question ID from queried object.
 * @since 1.0.0 Return only integer.
 */
function get_question_id() {
	if ( is_question() && get_query_var( 'question_id' ) ) {
		return (int) get_query_var( 'question_id' );
	}

	if ( is_question() ) {
		return get_queried_object_id();
	}

	if ( get_query_var( 'edit_q' ) ) {
		return get_query_var( 'edit_q' );
	}

	if ( asqa_answer_the_object() ) {
		return asqa_get_post_field( 'post_parent' );
	}

	return 0;
}

/**
 * Return human readable time format.
 *
 * @param  string         $time Time.
 * @param  boolean        $unix Is $time is unix.
 * @param  integer        $show_full_date Show full date after some period. Default is 7 days in epoch.
 * @param  boolean|string $format Date format.
 * @return string|null
 * @since  2.4.7 Checks if showing default date format is enabled.
 */
function asqa_human_time( $time, $unix = true, $show_full_date = 604800, $format = false ) {
	if ( false === $format ) {
		$format = get_option( 'date_format' );
	}

	if ( ! is_numeric( $time ) && ! $unix ) {
		$time = strtotime( $time );
	}

	// If default date format is enabled then just return date.
	if ( asqa_opt( 'default_date_format' ) ) {
		return date_i18n( $format, $time );
	}

	if ( $time ) {
		if ( $show_full_date + $time > time() ) {
			return sprintf(
				/* translators: %s: human-readable time difference */
				__( '%s ago', 'smart-question-answer' ),
				human_time_diff( $time, time() )
			);
		}

		return date_i18n( $format, $time );
	}
}

/**
 * Check if user answered on a question.
 *
 * @param integer $question_id  Question ID.
 * @param integer $user_id      User ID.
 * @return boolean
 *
 * @since unknown
 * @since 1.0.0 Changed cache group to `counts`.
 */
function asqa_is_user_answered( $question_id, $user_id ) {
	global $wpdb;

	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts where post_parent = %d AND ( post_author = %d AND post_type = 'answer')", $question_id, $user_id ) ); // phpcs:ignore WordPress.DB

	return $count > 0 ? true : false;
}

/**
 * Return link to answers.
 *
 * @param  boolean|integer $question_id Question ID.
 * @return string
 */
function asqa_answers_link( $question_id = false ) {
	if ( ! $question_id ) {
		return get_permalink() . '#answers';
	}
	return get_permalink( $question_id ) . '#answers';
}


/**
 * Return edit link for question and answer.
 *
 * @param mixed $_post Post.
 * @return string
 * @since 1.0.0
 */
function asqa_post_edit_link( $_post ) {
	$_post     = asqa_get_post( $_post );
	$nonce     = wp_create_nonce( 'edit-post-' . $_post->ID );
	$base_page = 'question' === $_post->post_type ? asqa_get_link_to( 'ask' ) : asqa_get_link_to( 'edit' );
	$edit_link = add_query_arg(
		array(
			'id'      => $_post->ID,
			'__nonce' => $nonce,
		),
		$base_page
	);

	/**
	 * Allows filtering post edit link.
	 *
	 * @param string $edit_link Url to edit post.
	 * @since unknown
	 */
	return apply_filters( 'asqa_post_edit_link', $edit_link );
}

/**
 * Truncate string but preserve full word.
 *
 * @param string $text String.
 * @param int    $limit Limit string to.
 * @param string $ellipsis Ellipsis.
 * @return string
 *
 * @since 1.0.0 Strip tags.
 */
function asqa_truncate_chars( $text, $limit = 40, $ellipsis = '...' ) {
	$text = wp_strip_all_tags( $text );
	$text = str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', $text );
	if ( strlen( $text ) > $limit ) {
		$endpos = strpos( $text, ' ', (string) $limit );

		if ( false !== $endpos ) {
			$text = trim( substr( $text, 0, $endpos ) ) . $ellipsis;
		}
	}
	return $text;
}

/**
 * Convert number to 1K, 1M etc.
 *
 * @param  integer $num       Number to convert.
 * @param  integer $precision Precision.
 * @return string
 */
function asqa_short_num( $num, $precision = 2 ) {
	if ( $num >= 1000 && $num < 1000000 ) {
		$n_format = number_format( $num / 1000, $precision ) . 'K';
	} elseif ( $num >= 1000000 && $num < 1000000000 ) {
		$n_format = number_format( $num / 1000000, $precision ) . 'M';
	} elseif ( $num >= 1000000000 ) {
		$n_format = number_format( $num / 1000000000, $precision ) . 'B';
	} else {
		$n_format = $num;
	}

	return $n_format;
}

/**
 * Sanitize comma delimited strings.
 *
 * @param  string|array $str Comma delimited string.
 * @param  string       $pieces_type Type of piece, string or number.
 * @return string
 */
function sanitize_comma_delimited( $str, $pieces_type = 'int' ) {
	$str = ! is_array( $str ) ? explode( ',', $str ) : $str;

	if ( ! empty( $str ) ) {
		$str       = wp_unslash( $str );
		$glue      = 'int' !== $pieces_type ? '","' : ',';
		$sanitized = array();
		foreach ( $str as $s ) {
			if ( '0' == $s || ! empty( $s ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				$sanitized[] = 'int' === $pieces_type ? intval( $s ) : str_replace( array( "'", '"', ',' ), '', sanitize_text_field( $s ) );
			}
		}

		$new_str = implode( $glue, esc_sql( $sanitized ) );

		if ( 'int' !== $pieces_type ) {
			return '"' . $new_str . '"';
		}

		return $new_str;
	}
}

/**
 * Check if doing ajax request.
 *
 * @return boolean
 * @since 1.0.0
 * @since  1.0.0 Check if `asqa_ajax_action` is set.
 */
function asqa_is_ajax() {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && asqa_sanitize_unslash( 'asqa_ajax_action', 'request', false ) ) {
		return true;
	}

	return false;
}

/**
 * Allow HTML tags.
 *
 * @return array
 * @since 0.9
 */
function asqa_form_allowed_tags() {
	global $asqa_kses_check;
	$asqa_kses_check = true;

	$allowed_style = array(
		'align' => true,
	);

	$allowed_tags = array(
		'p'          => array(
			'style' => $allowed_style,
			'title' => true,
		),
		'span'       => array(
			'style' => $allowed_style,
		),
		'a'          => array(
			'href'  => true,
			'title' => true,
		),
		'br'         => array(),
		'em'         => array(),
		'strong'     => array(
			'style' => $allowed_style,
		),
		'pre'        => array(),
		'code'       => array(),
		'blockquote' => array(),
		'img'        => array(
			'src'   => true,
			'style' => $allowed_style,
		),
		'ul'         => array(),
		'ol'         => array(),
		'li'         => array(),
		'del'        => array(),
	);

	/**
	 * Filter allowed HTML KSES tags.
	 *
	 * @param array $allowed_tags Allowed tags.
	 */
	return apply_filters( 'asqa_allowed_tags', $allowed_tags );
}

/**
 * Send a array as a JSON.
 *
 * @param array $result Results.
 */
function asqa_send_json( $result = array() ) {
	$result['is_asqa_ajax'] = true;

	wp_send_json( $result ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Highlight matching words.
 *
 * @param string $text  String.
 * @param string $words Words need to highlight.
 * @return string
 * @since   2.0
 */
function asqa_highlight_words( $text, $words ) {
	$words = explode( ' ', $words );
	foreach ( $words as $word ) {
		// Quote the text for regex.
		$word = preg_quote( $word, '/' );

		// Highlight the words.
		$text = preg_replace( "/\b($word)\b/i", '<span class="highlight_word">\1</span>', $text );
	}
	return $text;
}

/**
 * Return response with type and message.
 *
 * @param string $id           messge id.
 * @param bool   $only_message return message string instead of array.
 * @return string
 * @since 2.0.0
 */
function asqa_responce_message( $id, $only_message = false ) {
	$msg = array(
		'success'                       => array(
			'type'    => 'success',
			'message' => __( 'Success', 'smart-question-answer' ),
		),

		'something_wrong'               => array(
			'type'    => 'error',
			'message' => __( 'Something went wrong, last action failed.', 'smart-question-answer' ),
		),

		'comment_edit_success'          => array(
			'type'    => 'success',
			'message' => __( 'Comment updated successfully.', 'smart-question-answer' ),
		),
		'cannot_vote_own_post'          => array(
			'type'    => 'warning',
			'message' => __( 'You cannot vote on your own question or answer.', 'smart-question-answer' ),
		),
		'no_permission_to_view_private' => array(
			'type'    => 'warning',
			'message' => __( 'You do not have permission to view private posts.', 'smart-question-answer' ),
		),
		'captcha_error'                 => array(
			'type'    => 'error',
			'message' => __( 'Please check captcha field and resubmit it again.', 'smart-question-answer' ),
		),
		'post_image_uploaded'           => array(
			'type'    => 'success',
			'message' => __( 'Image uploaded successfully', 'smart-question-answer' ),
		),
		'answer_deleted_permanently'    => array(
			'type'    => 'success',
			'message' => __( 'Answer has been deleted permanently', 'smart-question-answer' ),
		),
		'upload_limit_crossed'          => array(
			'type'    => 'warning',
			'message' => __( 'You have already attached maximum numbers of allowed uploads.', 'smart-question-answer' ),
		),
		'profile_updated_successfully'  => array(
			'type'    => 'success',
			'message' => __( 'Your profile has been updated successfully.', 'smart-question-answer' ),
		),
		'voting_down_disabled'          => array(
			'type'    => 'warning',
			'message' => __( 'Voting down is disabled.', 'smart-question-answer' ),
		),
		'you_cannot_vote_on_restricted' => array(
			'type'    => 'warning',
			'message' => __( 'You cannot vote on restricted posts', 'smart-question-answer' ),
		),
	);

	/**
	 * Filter ajax response message.
	 *
	 * @param array $msg Messages.
	 * @since 1.0.0
	 */
	$msg = apply_filters( 'asqa_responce_message', $msg );

	if ( isset( $msg[ $id ] ) && $only_message ) {
		return $msg[ $id ]['message'];
	}

	if ( isset( $msg[ $id ] ) ) {
		return $msg[ $id ];
	}

	return false;
}

/**
 * Format an array as valid SmartQa ajax response.
 *
 * @param  array|string $results Response to send.
 * @return array
 * @since  unknown
 * @since  1.0.0 Removed `template` variable. Send `snackbar` for default message.
 */
function asqa_ajax_responce( $results ) {
	if ( ! is_array( $results ) ) {
		$message_id         = $results;
		$results            = array();
		$results['message'] = $message_id;
	}

	$results['asqa_responce'] = true;

	if ( isset( $results['message'] ) ) {
		$error_message = asqa_responce_message( $results['message'] );

		if ( false !== $error_message ) {
			$results['snackbar'] = array(
				'message'      => $error_message['message'],
				'message_type' => $error_message['type'],
			);

			$results['success'] = 'error' === $error_message['type'] ? false : true;
		}
	}

	/**
	 * Filter SmartQa ajax response body.
	 *
	 * @param array $results Results.
	 * @since 1.0.0
	 */
	$results = apply_filters( 'asqa_ajax_responce', $results );

	return $results;
}

/**
 * Array map callback.
 *
 * @param  array $a Array.
 * @return mixed
 */
function asqa_meta_array_map( $a ) {
	return $a[0];
}

/**
 * Return the current page url.
 *
 * @param array $args Arguments.
 * @return string
 * @since 2.0.0
 */
function asqa_current_page_url( $args ) {
	$base = rtrim( get_permalink(), '/' );
	if ( get_option( 'permalink_structure' ) !== '' ) {
		$link = $base . '/';
		if ( ! empty( $args ) ) {
			foreach ( $args as $k => $s ) {
				$link .= $k . '/' . $s . '/';
			}
		}
	} else {
		$link = add_query_arg( $args, $base );
	}

	return $link;
}

/**
 * Sort array by order value. Group array which have same order number and then sort them.
 *
 * @param array $array Array to order.
 * @return array
 * @since 2.0.0
 * @since 1.0.0 Use `WP_List_Util` class for sorting.
 */
function asqa_sort_array_by_order( $array ) {
	$new_array = array();

	if ( ! empty( $array ) && is_array( $array ) ) {
		$i = 1;
		foreach ( $array as $k => $a ) {
			if ( is_array( $a ) ) {
				$array[ $k ]['order'] = isset( $a['order'] ) ? $a['order'] : $i;
			}

			$i += 2;
		}

		$util = new WP_List_Util( $array );
		return $util->sort( 'order', 'ASC', true );
	}
}

/**
 * Echo smartqa links.
 *
 * @param string|array $sub Sub page.
 * @since 2.1
 */
function asqa_link_to( $sub ) {
	echo esc_url( asqa_get_link_to( $sub ) );
}

	/**
	 * Return link to SmartQa pages.
	 *
	 * @param string|array $sub Sub pages/s.
	 * @return string
	 */
function asqa_get_link_to( $sub ) {
	$url = false;

	if ( 'ask' === $sub ) {
		$url = get_permalink( asqa_opt( 'ask_page' ) );
	}

	if ( false === $url ) {
		/**
		 * Define default SmartQa page slugs.
		 *
		 * @var array
		 */
		$default_pages = array(
			'question' => asqa_opt( 'question_page_slug' ),
			'users'    => asqa_opt( 'users_page_slug' ),
			'user'     => asqa_opt( 'user_page_slug' ),
		);

		$default_pages = apply_filters( 'asqa_default_page_slugs', $default_pages );

		if ( is_array( $sub ) && isset( $sub['asqa_page'] ) && isset( $default_pages[ $sub['asqa_page'] ] ) ) {
			$sub['asqa_page'] = $default_pages[ $sub['asqa_page'] ];
		} elseif ( ! is_array( $sub ) && ! empty( $sub ) && isset( $default_pages[ $sub ] ) ) {
			$sub = $default_pages[ $sub ];
		}

		$base = rtrim( asqa_base_page_link(), '/' );
		$args = '';

		if ( get_option( 'permalink_structure' ) !== '' ) {
			if ( ! is_array( $sub ) && 'base' !== $sub ) {
				$args = $sub ? '/' . $sub : '';
			} elseif ( is_array( $sub ) ) {
				$args = '/';

				if ( ! empty( $sub ) ) {
					foreach ( (array) $sub as $s ) {
						$args .= $s . '/';
					}
				}
			}

			$args = user_trailingslashit( rtrim( $args, '/' ) );
		} else {
			if ( ! is_array( $sub ) ) {
				$args = $sub ? '&asqa_page=' . $sub : '';
			} elseif ( is_array( $sub ) ) {
				$args = '';

				if ( ! empty( $sub ) ) {
					foreach ( $sub as $k => $s ) {
						$args .= '&' . $k . '=' . $s;
					}
				}
			}
		}

		$url = $base . $args;
	}

	/**
	 * Allows filtering smartqa links.
	 *
	 * @param string       $url Generated url.
	 * @param string|array $sub SmartQa sub pages.
	 *
	 * @since unknown
	 */
	return apply_filters( 'asqa_link_to', $url, $sub );
}

/**
 * Return the total numbers of post.
 *
 * @param string         $post_type Post type.
 * @param boolean|string $asqa_type   asqa_meta type.
 * @param false|int      $user_id   User id, default is current user id.
 * @return object
 * @since  2.0.0
 */
function asqa_total_posts_count( $post_type = 'question', $asqa_type = false, $user_id = false ) {
	global $wpdb;

	if ( 'question' === $post_type ) {
		$type = "p.post_type = 'question'";
	} elseif ( 'answer' === $post_type ) {
		$type = "p.post_type = 'answer'";
	} else {
		$type = "(p.post_type = 'question' OR p.post_type = 'answer')";
	}

	$meta = '';
	$join = '';

	if ( 'flag' === $asqa_type ) {
		$meta = 'AND qameta.flags > 0';
		$join = "INNER JOIN {$wpdb->asqa_qameta} qameta ON p.ID = qameta.post_id";
	} elseif ( 'unanswered' === $asqa_type ) {
		$meta = 'AND qameta.answers = 0';
		$join = "INNER JOIN {$wpdb->asqa_qameta} qameta ON p.ID = qameta.post_id";
	} elseif ( 'best_answer' === $asqa_type ) {
		$meta = 'AND qameta.selected > 0';
		$join = "INNER JOIN {$wpdb->asqa_qameta} qameta ON p.ID = qameta.post_id";
	}

	$where = "WHERE p.post_status NOT IN ('trash', 'draft') AND $type $meta";

	if ( false !== $user_id && (int) $user_id > 0 ) {
		$where .= ' AND p.post_author = ' . (int) $user_id;
	}

	$where = apply_filters( 'asqa_total_posts_count', $where );
	$query = "SELECT count(*) as count, p.post_status FROM $wpdb->posts p $join $where GROUP BY p.post_status";

	$count = $wpdb->get_results( $query, ARRAY_A ); // @codingStandardsIgnoreLine
	$counts = array();

	foreach ( (array) get_post_stati() as $state ) {
		$counts[ $state ] = 0;
	}

	$counts['total'] = 0;

	if ( ! empty( $count ) ) {
		foreach ( $count as $row ) {
			$counts[ $row['post_status'] ] = (int) $row['count'];
			$counts['total']              += (int) $row['count'];
		}
	}

	return (object) $counts;
}

/**
 * Return total numbers of published questions.
 *
 * @return integer
 */
function asqa_total_published_questions() {
	$posts = asqa_total_posts_count();
	return $posts->publish;
}

/**
 * Get total numbers of solved question.
 *
 * @param string $type Valid values are int or object.
 * @return int|object
 */
function asqa_total_solved_questions( $type = 'int' ) {
	global $wpdb;
	$query = "SELECT count(*) as count, p.post_status FROM $wpdb->posts p INNER JOIN $wpdb->asqa_qameta qameta ON p.ID = qameta.post_id WHERE p.post_type = 'question' AND qameta.selected_id IS NOT NULL AND qameta.selected_id > 0 GROUP BY p.post_status";

	$count  = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB
	$counts = array( 'total' => 0 );

	foreach ( get_post_stati() as $state ) {
		$counts[ $state ] = 0;
	}

	foreach ( (array) $count as $row ) {
		$counts[ $row['post_status'] ] = (int) $row['count'];
		$counts['total']              += (int) $row['count'];
	}

	$counts = (object) $counts;
	if ( 'int' === $type ) {
		return $counts->publish + $counts->private_post;
	}

	return $counts;
}

/**
 * Get current sorting type.
 *
 * @return string
 * @since 2.1
 */
function asqa_get_sort() {
	return asqa_sanitize_unslash( 'asqa_sort', 'p', null );
}

/**
 * Remove white space from string.
 *
 * @param string $contents String.
 * @return string
 */
function asqa_trim_traling_space( $contents ) {
	$contents = preg_replace( '#(^(&nbsp;|\s)+|(&nbsp;|\s)+$)#', '', $contents );
	return $contents;
}

/**
 * Replace square brackets in a string.
 *
 * @param string $contents String.
 */
function asqa_replace_square_bracket( $contents ) {
	$contents = str_replace( '[', '&#91;', $contents );
	$contents = str_replace( ']', '&#93;', $contents );
	return $contents;
}

/**
 * Create base page for SmartQa.
 *
 * This function is called in plugin activation. This function checks if base page already exists,
 * if not then it create a new one and update the option.
 *
 * @see smartqa_activate
 * @since 2.3
 * @since 1.0.0 Creates all other SmartQa pages if not exists.
 */
function asqa_create_base_page() {
	$opt = asqa_opt();

	$pages = asqa_main_pages();

	foreach ( $pages as $slug => $page ) {
		// Check if page already exists.
		$_post = get_page( asqa_opt( $slug ) );

		if ( ! $_post || 'trash' === $_post->post_status ) {
			$args = wp_parse_args(
				$page,
				array(
					'post_type'      => 'page',
					'post_content'   => '[smartqa]',
					'post_status'    => 'publish',
					'comment_status' => 'closed',
				)
			);

			if ( 'base_page' !== $slug ) {
				$args['post_parent'] = asqa_opt( 'base_page' );
			}

			// Now create post.
			$new_page_id = wp_insert_post( $args );

			if ( $new_page_id ) {
				$page = get_page( $new_page_id );

				asqa_opt( $slug, $page->ID );
				asqa_opt( $slug . '_id', $page->post_name );
			}
		}
	}
}

/**
 * Return question title with solved prefix if answer is accepted.
 *
 * @param boolean|integer $question_id Question ID.
 * @return string
 *
 * @since   2.3 @see `asqa_page_title`
 */
function asqa_question_title_with_solved_prefix( $question_id = false ) {
	if ( false === $question_id ) {
		$question_id = get_question_id();
	}

	$solved = asqa_have_answer_selected( $question_id );

	if ( asqa_opt( 'show_solved_prefix' ) ) {
		return get_the_title( $question_id ) . ' ' . ( $solved ? __( '[Solved] ', 'smart-question-answer' ) : '' );
	}

	return get_the_title( $question_id );
}

/**
 * Verify the __nonce field.
 *
 * @param string $action Action.
 * @return bool
 * @since  2.4
 */
function asqa_verify_nonce( $action ) {
	return wp_verify_nonce( asqa_sanitize_unslash( '__nonce', 'p' ), $action );
}

/**
 * Verify default ajax nonce field.
 *
 * @return boolean
 */
function asqa_verify_default_nonce() {
	$nonce_name = isset( $_REQUEST['asqa_ajax_nonce'] ) ? 'asqa_ajax_nonce' : '__nonce'; // input var okay.

	if ( ! isset( $_REQUEST[ $nonce_name ] ) ) { // input var okay.
		return false;
	}

	return wp_verify_nonce( asqa_sanitize_unslash( $nonce_name, 'p' ), 'asqa_ajax_nonce' );
}

/**
 * Parse search string to array.
 *
 * @param  string $str search string.
 * @return array
 */
function asqa_parse_search_string( $str ) {
	$output = array();

	// Split by space.
	$bits = explode( ' ', $str );

	// Process pairs.
	foreach ( $bits as $id => $pair ) {
		// Split the pair.
		$pair_bits = explode( ':', $pair );

		// This was actually a pair.
		if ( count( $pair_bits ) === 2 ) {
			$values    = explode( ',', $pair_bits[1] );
			$sanitized = array();

			if ( is_array( $values ) && ! empty( $values ) ) {
				foreach ( $values as $value ) {
					if ( ! empty( $value ) ) {
						$sanitized[] = sanitize_text_field( $value );
					}
				}
			}

			if ( count( $sanitized ) > 0 ) {
				// Use left part of pair as index and push right part to array.
				if ( ! empty( $pair_bits[0] ) ) {
					$output[ sanitize_text_field( $pair_bits[0] ) ] = $sanitized;
				}
			}

			if ( isset( $bits[ $id ] ) ) {
				// Remove this pair from $bits.
				unset( $bits[ $id ] );
			}
		} else {
			// Exit the loop.
			break;
		}
	}

	// Rebuild query with remains of $bits.
	$output['q'] = sanitize_text_field( implode( ' ', $bits ) );

	return $output;
}

/**
 * Send properly formatted SmartQa json string.
 *
 * @param  array|string $response Response array or string.
 */
function asqa_ajax_json( $response ) {
	asqa_send_json( asqa_ajax_responce( $response ) );
}

/**
 * Check if object is profile menu item.
 *
 * @param  object $menu Menu Object.
 * @return boolean
 */
function asqa_is_profile_menu( $menu ) {
	return in_array( 'smartqa-page-profile', $menu->classes, true );
}

/**
 * Get the IDs of answer by question ID.
 *
 * @param  integer $question_id Question post ID.
 * @return object
 * @since  2.4
 */
function asqa_questions_answer_ids( $question_id ) {
	global $wpdb;

	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'answer' AND post_parent=%d", $question_id ) ); // phpcs:ignore WordPress.DB

	return $ids;
}

/**
 * Whitelist array items.
 *
 * @param  array $master_keys Master keys.
 * @param  array $array       Array to filter.
 * @return array
 */
function asqa_whitelist_array( $master_keys, $array ) {
	return array_intersect_key( $array, array_flip( $master_keys ) );
}

/**
 * Append table name in $wpdb.
 *
 * @since unknown
 * @since 1.0.0 Added `asqa_activity` table.
 * @since 1.0.0 Added `asqa_reputation_events`.
 */
function asqa_append_table_names() {
	global $wpdb;

	$wpdb->asqa_qameta            = $wpdb->prefix . 'asqa_qameta';
	$wpdb->asqa_votes             = $wpdb->prefix . 'asqa_votes';
	$wpdb->asqa_views             = $wpdb->prefix . 'asqa_views';
	$wpdb->asqa_reputations       = $wpdb->prefix . 'asqa_reputations';
	$wpdb->asqa_subscribers       = $wpdb->prefix . 'asqa_subscribers';
	$wpdb->asqa_activity          = $wpdb->prefix . 'asqa_activity';
	$wpdb->asqa_reputation_events = $wpdb->prefix . 'asqa_reputation_events';
}
asqa_append_table_names();


/**
 * Check if $_REQUEST var exists and get value. If not return default.
 *
 * @param  string $var     Variable name.
 * @param  mixed  $default Default value.
 * @return mixed
 * @since  1.0.0
 */
function asqa_isset_post_value( $var, $default = '' ) {
	if ( isset( $_REQUEST[ $var ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return wp_unslash( $_REQUEST[ $var ] ); // phpcs:ignore WordPress.Security
	}

	return $default;
}

/**
 * Get active list filter by filter key.
 *
 * @param  string|null $filter  Filter key.
 * @return false|string|array
 * @since  4.0.0
 */
function asqa_get_current_list_filters( $filter = null ) {
	$get_filters = array();
	$filters     = array_keys( asqa_get_list_filters() );

	if ( in_array( 'order_by', $filters, true ) ) {
		$get_filters['order_by'] = asqa_opt( 'question_order_by' );
	}

	if ( empty( $filters ) || ! is_array( $filters ) ) {
		$filters = array();
	}

	foreach ( (array) $filters as $k ) {
		$val = asqa_isset_post_value( $k );

		if ( ! empty( $val ) ) {
			$get_filters[ $k ] = $val;
		}
	}

	if ( null !== $filter ) {
		return ! isset( $get_filters[ $filter ] ) ? null : $get_filters[ $filter ];
	}

	return $get_filters;
}

/**
 * Sanitize and unslash string or array or post/get value at the same time.
 *
 * @param  string|array   $str    String or array to sanitize. Or post/get key name.
 * @param  boolean|string $from   Get value from `$_REQUEST` or `query_var`. Valid values: request, query_var.
 * @param  mixed          $default   Default value if variable not found.
 * @return array|string
 * @since  1.0.0
 */
function asqa_sanitize_unslash( $str, $from = false, $default = '' ) {
	// If not false then get from $_REQUEST or query_var.
	if ( false !== $from ) {
		if ( in_array( strtolower( $from ), array( 'request', 'post', 'get', 'p', 'g', 'r' ), true ) ) {
			$str = asqa_isset_post_value( $str, $default );
		} elseif ( 'query_var' === $from ) {
			$str = get_query_var( $str );
		}
	}

	if ( empty( $str ) ) {
		return $default;
	}

	if ( is_array( $str ) ) {
		$str = wp_unslash( $str );
		return array_map( 'sanitize_text_field', $str );
	}

	return sanitize_text_field( wp_unslash( $str ) );
}

/**
 * Return post status based on SmartQa options.
 *
 * @param  boolean|integer $user_id    ID of user creating question.
 * @param  string          $post_type  Post type, question or answer.
 * @param  boolean         $edit       Is editing post.
 * @return string
 * @since  1.0.0
 */
function asqa_new_edit_post_status( $user_id = false, $post_type = 'question', $edit = false ) {
	if ( false === $user_id ) {
		$user_id = get_current_user_id();
	}

	$new_edit   = $edit ? 'edit' : 'new';
	$option_key = $new_edit . '_' . $post_type . '_status';
	$status     = 'publish';

	// If super admin or user have no_moderation cap.
	if ( is_super_admin( $user_id ) || user_can( $user_id, 'asqa_no_moderation' ) ) {
		return $status;
	}

	if ( asqa_opt( $option_key ) === 'moderate' && ! ( user_can( $user_id, 'asqa_moderator' ) || is_super_admin( $user_id ) ) ) {
		$status = 'moderate';
	}

	// If anonymous post status is set to moderate.
	if ( empty( $user_id ) && asqa_opt( 'anonymous_post_status' ) === 'moderate' ) {
		$status = 'moderate';
	}

	return $status;
}

/**
 * Find duplicate post by content.
 *
 * @param  string        $content   Post content.
 * @param  string        $post_type Post type.
 * @param  integer|false $question_id Question ID.
 * @return boolean|false
 * @since  1.0.0
 * @since  1.0.0 Removed option check. Removed `asqa_sanitize_description_field` sanitization.
 */
function asqa_find_duplicate_post( $content, $post_type = 'question', $question_id = false ) {
	global $wpdb;

	// Return if content is empty. But blank content will be checked.
	if ( empty( $content ) ) {
		return false;
	}

	$question_q = false !== $question_id ? $wpdb->prepare( ' AND post_parent= %d', $question_id ) : '';

	$var = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_content = %s AND post_type = %s {$question_q} LIMIT 1", $content, $post_type ) ); // @codingStandardsIgnoreLine

	if ( $var > 0 ) {
		return $var;
	}

	return false;
}
/**
 * Check if question suggestion is disabled.
 *
 * @return boolean
 * @since  1.0.0
 */
function asqa_disable_question_suggestion() {
	/**
	 * Modify asqa_disable_question_suggestion.
	 *
	 * @param boolean $enable Default is false.
	 * @since  1.0.0
	 */
	return (bool) apply_filters( 'asqa_disable_question_suggestion', false );
}

/**
 * Pre fetch users and update cache.
 *
 * @param  array $ids User ids.
 * @since 4.0.0
 */
function asqa_post_author_pre_fetch( $ids ) {
	$users = get_users(
		array(
			'include' => $ids,
			'fields'  => array( 'ID', 'user_login', 'user_nicename', 'user_email', 'display_name' ),
		)
	);

	foreach ( (array) $users as $user ) {
		update_user_caches( $user );
	}

	update_meta_cache( 'user', $ids );
}


/**
 * Activity type to human readable title.
 *
 * @param  string $type Activity type.
 * @return string
 */
function asqa_activity_short_title( $type ) {
	$title = array(
		'new_question'           => __( 'asked', 'smart-question-answer' ),
		'approved_question'      => __( 'approved', 'smart-question-answer' ),
		'approved_answer'        => __( 'approved', 'smart-question-answer' ),
		'new_answer'             => __( 'answered', 'smart-question-answer' ),
		'delete_answer'          => __( 'deleted answer', 'smart-question-answer' ),
		'restore_question'       => __( 'restored question', 'smart-question-answer' ),
		'restore_answer'         => __( 'restored answer', 'smart-question-answer' ),
		'new_comment'            => __( 'commented', 'smart-question-answer' ),
		'delete_comment'         => __( 'deleted comment', 'smart-question-answer' ),
		'new_comment_answer'     => __( 'commented on answer', 'smart-question-answer' ),
		'edit_question'          => __( 'edited question', 'smart-question-answer' ),
		'edit_answer'            => __( 'edited answer', 'smart-question-answer' ),
		'edit_comment'           => __( 'edited comment', 'smart-question-answer' ),
		'edit_comment_answer'    => __( 'edited comment on answer', 'smart-question-answer' ),
		'answer_selected'        => __( 'selected answer', 'smart-question-answer' ),
		'answer_unselected'      => __( 'unselected answer', 'smart-question-answer' ),
		'status_updated'         => __( 'updated status', 'smart-question-answer' ),
		'best_answer'            => __( 'selected as best answer', 'smart-question-answer' ),
		'unselected_best_answer' => __( 'unselected as best answer', 'smart-question-answer' ),
		'changed_status'         => __( 'changed status', 'smart-question-answer' ),
	);

	$title = apply_filters( 'asqa_activity_short_title', $title );

	if ( isset( $title[ $type ] ) ) {
		return $title[ $type ];
	}

	return $type;
}

/**
 * Return canonical URL of current page.
 *
 * @return string
 * @since  1.0.0
 */
function asqa_canonical_url() {
	$canonical_url = asqa_get_link_to( get_query_var( 'asqa_page' ) );

	if ( is_question() ) {
		$canonical_url = get_permalink( get_question_id() );
	}

	/**
	 * Filter SmartQa canonical URL.
	 *
	 * @param string $canonical_url Current URL.
	 * @return string
	 * @since  1.0.0
	 */
	$canonical_url = apply_filters( 'asqa_canonical_url', $canonical_url );

	return esc_url( $canonical_url );
}

/**
 * Return or echo user display name.
 *
 * Get display name from comments if WP_Comment object is passed. Else
 * fetch name form user profile. If anonymous user then fetch name from
 * current question, answer or comment.
 *
 * @param  WP_Comment|array|integer $args {
 *      Arguments or `WP_Comment` or user ID.
 *
 *      @type integer $user_id User ID.
 *      @type boolean $html    Shall return just text name or name with html markup.
 *      @type boolean $echo    Return or echo.
 *      @type string  $anonymous_label A placeholder name for anonymous user if no name found in post or comment.
 * }
 *
 * @return string|void If `$echo` argument is tru then it will echo name.
 * @since 0.1
 * @since 1.0.0 Improved args and PHPDoc.
 */
function asqa_user_display_name( $args = array() ) {
	global $post;

	$defaults = array(
		'user_id'         => get_the_author_meta( 'ID' ),
		'html'            => false,
		'echo'            => false,
		'anonymous_label' => __( 'Anonymous', 'smart-question-answer' ),
	);

	// When only user id passed.
	if ( is_numeric( $args ) ) {
		$defaults['user_id'] = $args;
		$args                = $defaults;
	} elseif ( $args instanceof WP_Comment ) {
		$defaults['user_id']         = $args->user_id;
		$defaults['anonymous_label'] = $args->comment_author;
		$args                        = $defaults;
	} else {
		$args = wp_parse_args( $args, $defaults );
	}

	extract( $args ); // @codingStandardsIgnoreLine

	$user = get_userdata( $user_id );

	if ( $user ) {
		$return = ! $html ? $user->display_name : '<a href="' . esc_url( asqa_user_link( $user_id ) ) . '" itemprop="url"><span itemprop="name">' . esc_html( $user->display_name ) . '</span></a>';
	} elseif ( $post && in_array( $post->post_type, array( 'question', 'answer' ), true ) ) {
		$post_fields = asqa_get_post_field( 'fields' );

		if ( ! $html ) {
			if ( is_array( $post_fields ) && ! empty( $post_fields['anonymous_name'] ) ) {
				$return = $post_fields['anonymous_name'];
			} else {
				$return = $anonymous_label;
			}
		} else {
			if ( is_array( $post_fields ) && ! empty( $post_fields['anonymous_name'] ) ) {
				$return = $post_fields['anonymous_name'] . esc_attr__( ' (anonymous)', 'smart-question-answer' );
			} else {
				$return = $anonymous_label;
			}
		}
	} else {
		if ( ! $html ) {
			$return = $anonymous_label;
		} else {
			$return = $anonymous_label;
		}
	}

	/**
	 * Filter SmartQa user display name.
	 *
	 * Filter can be used to alter user display name or
	 * appending some extra information of user, like: rank, reputation etc.
	 * Make sure to return plain text when `$args['html']` is true.
	 *
	 * @param string $return Name of user to return.
	 * @param array  $args   Arguments.
	 *
	 * @since 1.0.0
	 */
	$return = apply_filters( 'asqa_user_display_name', $return, $args );

	if ( ! $args['echo'] ) {
		return $return;
	}

	echo $return; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return Link to user pages.
 *
 * @param  boolean|integer $user_id    user id.
 * @param  string|array    $sub        page slug.
 * @return string
 * @since  unknown
 * @since  1.0.0 Profile link not linking to BuddyPress when active.
 * @since  1.0.0 User user nicename in url as author_name query var gets user by nicename.
 */
function asqa_user_link( $user_id = false, $sub = false ) {
	$link = '';

	if ( false === $user_id ) {
		$user_id = get_the_author_meta( 'ID' );
	}

	if ( empty( $user_id ) && is_author() ) {
		$user_id = get_queried_object_id();
	}

	if ( $user_id < 1 && empty( $user_id ) ) {
		$link = '#/user/anonymous';
	} else {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			$link = '#/user/anonymous';
		} elseif ( function_exists( 'bp_core_get_userlink' ) ) {
			$link = bp_core_get_userlink( $user_id, false, true );
		} elseif ( asqa_is_addon_active( 'profile.php' ) ) {
			$slug = get_option( 'asqa_user_path' );
			$link = home_url( $slug ) . '/' . $user->user_nicename . '/';
		} else {
			$link = get_author_posts_url( $user_id );
		}
	}

	// Append sub.
	if ( ! empty( $sub ) ) {
		if ( is_array( $sub ) ) {
			$link = rtrim( $link, '/' ) . implode( '/', $sub ) . '/';
		} else {
			$link = $link . rtrim( $sub, '/' ) . '/';
		}
	}

	$link = user_trailingslashit( $link );

	return apply_filters( 'asqa_user_link', $link, $user_id, $sub );
}

/**
 * Return current page in user profile.
 *
 * @since 1.0.0
 * @return string
 * @since 2.4.7 Added new filter `asqa_active_user_page`.
 */
function asqa_active_user_page() {
	$user_page = sanitize_text_field( get_query_var( 'user_page' ) );

	if ( ! empty( $user_page ) ) {
		return $user_page;
	}

	$page = 'about';

	return apply_filters( 'asqa_active_user_page', $page );
}

/**
 * User name and link with anchor tag.
 *
 * @param string  $user_id User ID.
 * @param boolean $echo Echo or return.
 */
function asqa_user_link_anchor( $user_id, $echo = true ) {
	$name = asqa_user_display_name( $user_id );

	if ( $user_id < 1 ) {
		if ( $echo ) {
			echo $name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			return $name;
		}
	}

	$html  = '<a href="' . esc_url( asqa_user_link( $user_id ) ) . '">';
	$html .= $name;
	$html .= '</a>';

	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	return $html;
}

/**
 * Remove stop words from a string.
 *
 * @param  string $str String from need to be filtered.
 * @return string
 */
function asqa_remove_stop_words( $str ) {
	// EEEEEEK Stop words.
	$common_words = array( 'a', 'able', 'about', 'above', 'abroad', 'according', 'accordingly', 'across', 'actually', 'adj', 'after', 'afterwards', 'again', 'against', 'ago', 'ahead', 'ain\'t', 'all', 'allow', 'allows', 'almost', 'alone', 'along', 'alongside', 'already', 'also', 'although', 'always', 'am', 'amid', 'amidst', 'among', 'amongst', 'an', 'and', 'another', 'any', 'anybody', 'anyhow', 'anyone', 'anything', 'anyway', 'anyways', 'anywhere', 'apart', 'appear', 'appreciate', 'appropriate', 'are', 'aren\'t', 'around', 'as', 'a\'s', 'aside', 'ask', 'asking', 'associated', 'at', 'available', 'away', 'awfully', 'b', 'back', 'backward', 'backwards', 'be', 'became', 'because', 'become', 'becomes', 'becoming', 'been', 'before', 'beforehand', 'begin', 'behind', 'being', 'believe', 'below', 'beside', 'besides', 'best', 'better', 'between', 'beyond', 'both', 'brief', 'but', 'by', 'c', 'came', 'can', 'cannot', 'cant', 'can\'t', 'caption', 'cause', 'causes', 'certain', 'certainly', 'changes', 'clearly', 'c\'mon', 'co', 'co.', 'com', 'come', 'comes', 'concerning', 'consequently', 'consider', 'considering', 'contain', 'containing', 'contains', 'corresponding', 'could', 'couldn\'t', 'course', 'c\'s', 'currently', 'd', 'dare', 'daren\'t', 'definitely', 'described', 'despite', 'did', 'didn\'t', 'different', 'directly', 'do', 'does', 'doesn\'t', 'doing', 'done', 'don\'t', 'down', 'downwards', 'during', 'e', 'each', 'edu', 'eg', 'eight', 'eighty', 'either', 'else', 'elsewhere', 'end', 'ending', 'enough', 'entirely', 'especially', 'et', 'etc', 'even', 'ever', 'evermore', 'every', 'everybody', 'everyone', 'everything', 'everywhere', 'ex', 'exactly', 'example', 'except', 'f', 'fairly', 'far', 'farther', 'few', 'fewer', 'fifth', 'first', 'five', 'followed', 'following', 'follows', 'for', 'forever', 'former', 'formerly', 'forth', 'forward', 'found', 'four', 'from', 'further', 'furthermore', 'g', 'get', 'gets', 'getting', 'given', 'gives', 'go', 'goes', 'going', 'gone', 'got', 'gotten', 'greetings', 'h', 'had', 'hadn\'t', 'half', 'happens', 'hardly', 'has', 'hasn\'t', 'have', 'haven\'t', 'having', 'he', 'he\'d', 'he\'ll', 'hello', 'help', 'hence', 'her', 'here', 'hereafter', 'hereby', 'herein', 'here\'s', 'hereupon', 'hers', 'herself', 'he\'s', 'hi', 'him', 'himself', 'his', 'hither', 'hopefully', 'how', 'howbeit', 'however', 'hundred', 'i', 'i\'d', 'ie', 'if', 'ignored', 'i\'ll', 'i\'m', 'immediate', 'in', 'inasmuch', 'inc', 'inc.', 'indeed', 'indicate', 'indicated', 'indicates', 'inner', 'inside', 'insofar', 'instead', 'into', 'inward', 'is', 'isn\'t', 'it', 'it\'d', 'it\'ll', 'its', 'it\'s', 'itself', 'i\'ve', 'j', 'just', 'k', 'keep', 'keeps', 'kept', 'know', 'known', 'knows', 'l', 'last', 'lately', 'later', 'latter', 'latterly', 'least', 'less', 'lest', 'let', 'let\'s', 'like', 'liked', 'likely', 'likewise', 'little', 'look', 'looking', 'looks', 'low', 'lower', 'ltd', 'm', 'made', 'mainly', 'make', 'makes', 'many', 'may', 'maybe', 'mayn\'t', 'me', 'mean', 'meantime', 'meanwhile', 'merely', 'might', 'mightn\'t', 'mine', 'minus', 'miss', 'more', 'moreover', 'most', 'mostly', 'mr', 'mrs', 'much', 'must', 'mustn\'t', 'my', 'myself', 'n', 'name', 'namely', 'nd', 'near', 'nearly', 'necessary', 'need', 'needn\'t', 'needs', 'neither', 'never', 'neverf', 'neverless', 'nevertheless', 'new', 'next', 'nine', 'ninety', 'no', 'nobody', 'non', 'none', 'nonetheless', 'noone', 'no-one', 'nor', 'normally', 'not', 'nothing', 'notwithstanding', 'novel', 'now', 'nowhere', 'o', 'obviously', 'of', 'off', 'often', 'oh', 'ok', 'okay', 'old', 'on', 'once', 'one', 'ones', 'one\'s', 'only', 'onto', 'opposite', 'or', 'other', 'others', 'otherwise', 'ought', 'oughtn\'t', 'our', 'ours', 'ourselves', 'out', 'outside', 'over', 'overall', 'own', 'p', 'particular', 'particularly', 'past', 'per', 'perhaps', 'placed', 'please', 'plus', 'possible', 'presumably', 'probably', 'provided', 'provides', 'q', 'que', 'quite', 'qv', 'r', 'rather', 'rd', 're', 'really', 'reasonably', 'recent', 'recently', 'regarding', 'regardless', 'regards', 'relatively', 'respectively', 'right', 'round', 's', 'said', 'same', 'saw', 'say', 'saying', 'says', 'second', 'secondly', 'see', 'seeing', 'seem', 'seemed', 'seeming', 'seems', 'seen', 'self', 'selves', 'sensible', 'sent', 'serious', 'seriously', 'seven', 'several', 'shall', 'shan\'t', 'she', 'she\'d', 'she\'ll', 'she\'s', 'should', 'shouldn\'t', 'since', 'six', 'so', 'some', 'somebody', 'someday', 'somehow', 'someone', 'something', 'sometime', 'sometimes', 'somewhat', 'somewhere', 'soon', 'sorry', 'specified', 'specify', 'specifying', 'still', 'sub', 'such', 'sup', 'sure', 't', 'take', 'taken', 'taking', 'tell', 'tends', 'th', 'than', 'thank', 'thanks', 'thanx', 'that', 'that\'ll', 'thats', 'that\'s', 'that\'ve', 'the', 'their', 'theirs', 'them', 'themselves', 'then', 'thence', 'there', 'thereafter', 'thereby', 'there\'d', 'therefore', 'therein', 'there\'ll', 'there\'re', 'theres', 'there\'s', 'thereupon', 'there\'ve', 'these', 'they', 'they\'d', 'they\'ll', 'they\'re', 'they\'ve', 'thing', 'things', 'think', 'third', 'thirty', 'this', 'thorough', 'thoroughly', 'those', 'though', 'three', 'through', 'throughout', 'thru', 'thus', 'till', 'to', 'together', 'too', 'took', 'toward', 'towards', 'tried', 'tries', 'truly', 'try', 'trying', 't\'s', 'twice', 'two', 'u', 'un', 'under', 'underneath', 'undoing', 'unfortunately', 'unless', 'unlike', 'unlikely', 'until', 'unto', 'up', 'upon', 'upwards', 'us', 'use', 'used', 'useful', 'uses', 'using', 'usually', 'v', 'value', 'various', 'versus', 'very', 'via', 'viz', 'vs', 'w', 'want', 'wants', 'was', 'wasn\'t', 'way', 'we', 'we\'d', 'welcome', 'well', 'we\'ll', 'went', 'were', 'we\'re', 'weren\'t', 'we\'ve', 'what', 'whatever', 'what\'ll', 'what\'s', 'what\'ve', 'when', 'whence', 'whenever', 'where', 'whereafter', 'whereas', 'whereby', 'wherein', 'where\'s', 'whereupon', 'wherever', 'whether', 'which', 'whichever', 'while', 'whilst', 'whither', 'who', 'who\'d', 'whoever', 'whole', 'who\'ll', 'whom', 'whomever', 'who\'s', 'whose', 'why', 'will', 'willing', 'wish', 'with', 'within', 'without', 'wonder', 'won\'t', 'would', 'wouldn\'t', 'x', 'y', 'yes', 'yet', 'you', 'you\'d', 'you\'ll', 'your', 'you\'re', 'yours', 'yourself', 'yourselves', 'you\'ve', 'z', 'zero' );

	return preg_replace( '/\b(' . implode( '|', $common_words ) . ')\b/', '', $str );
}

/**
 * Search array by key and value.
 *
 * @param  array  $array Array to search.
 * @param  string $key   Array key to search.
 * @param  mixed  $value Value of key supplied.
 * @return array
 * @since  4.0.0
 */
function asqa_search_array( $array, $key, $value ) {
	$results = array();

	if ( is_array( $array ) ) {
		if ( isset( $array[ $key ] ) && $array[ $key ] == $value ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$results[] = $array;
		}

		foreach ( $array as $subarray ) {
			$results = array_merge( $results, asqa_search_array( $subarray, $key, $value ) );
		}
	}

	return $results;
}

/**
 * Get all SmartQa add-ons data.
 *
 * @return array
 * @since 4.0.0
 * @since 1.0.0 Do not fetch addon meta data from files, instead defined it in array.
 */
function asqa_get_addons() {
	$cache  = wp_cache_get( 'addons', 'smartqa' );
	$option = get_option( 'smartqa_addons', array() );

	if ( false !== $cache ) {
		return $cache;
	}

	$all_files = array();
	foreach ( array( 'pro', 'free' ) as $folder ) {
		$path = SMARTQA_ADDONS_DIR . DS . $folder;

		if ( file_exists( $path ) ) {
			$files = scandir( $path );

			foreach ( $files as $file ) {
				$ext = pathinfo( $file, PATHINFO_EXTENSION );

				if ( 'php' === $ext ) {
					$all_files[] = $folder . DS . $file;
				}
			}
		}
	}

	$addons = array(
		'email.php'             => array(
			'name'        => __( 'Emails', 'smart-question-answer' ),
			'description' => __( 'Notifies users and admins by email for various events and activities.', 'smart-question-answer' ),
		),
		'categories.php'        => array(
			'name'        => __( 'Categories', 'smart-question-answer' ),
			'description' => __( 'Add category support in SmartQa questions.', 'smart-question-answer' ),
		),
		'notifications.php'     => array(
			'name'        => __( 'Notifications', 'smart-question-answer' ),
			'description' => __( 'Adds a fancy user notification dropdown like Facebook and Stackoverflow.', 'smart-question-answer' ),
		),
		'tags.php'              => array(
			'name'        => __( 'Tags', 'smart-question-answer' ),
			'description' => __( 'Add tag support in SmartQa questions.', 'smart-question-answer' ),
		),
		'reputation.php'        => array(
			'name'        => __( 'Reputation', 'smart-question-answer' ),
			'description' => __( 'Award points to user based on activities.', 'smart-question-answer' ),
		),
		'avatar.php'            => array(
			'name'        => __( 'Dynamic Avatar', 'smart-question-answer' ),
			'description' => __( 'Generate user avatar based on display name initials.', 'smart-question-answer' ),
		),
		'buddypress.php'        => array(
			'name'        => __( 'BuddyPress', 'smart-question-answer' ),
			'description' => __( 'Integrate SmartQa with BuddyPress.', 'smart-question-answer' ),
		),
		'profile.php'           => array(
			'name'        => __( 'User Profile', 'smart-question-answer' ),
			'description' => __( 'User profile for users.', 'smart-question-answer' ),
		),
		'recaptcha.php'         => array(
			'name'        => __( 'reCaptcha', 'smart-question-answer' ),
			'description' => __( 'Add reCaptcha verification in question, answer and comment forms.', 'smart-question-answer' ),
		),
		'syntaxhighlighter.php' => array(
			'name'        => __( 'Syntax Highlighter', 'smart-question-answer' ),
			'description' => __( 'Add syntax highlighter support.', 'smart-question-answer' ),
		),
		'akismet.php'           => array(
			'name'        => __( 'Akismet Check', 'smart-question-answer' ),
			'description' => __( 'Check for spam in post content.', 'smart-question-answer' ),
		),
	);

	/**
	 * This hooks can be used to filter existing addons or for adding new addons.
	 *
	 * @since 1.0.0
	 */
	$addons = apply_filters( 'asqa_addons', $addons );

	$valid_addons = array();
	foreach ( (array) $addons as $k => $addon ) {
		$path  = SMARTQA_ADDONS_DIR . DS . basename( $k, '.php' ) . DS . $k;
		$path2 = SMARTQA_ADDONS_DIR . DS . $k;

		$addons[ $k ]['path'] = '';

		if ( isset( $addon['path'] ) && file_exists( $addon['path'] ) ) {
			$addons[ $k ]['path'] = wp_normalize_path( $addon['path'] );
		} elseif ( file_exists( $path ) ) {
			$addons[ $k ]['path'] = wp_normalize_path( $path );
		} elseif ( file_exists( $path2 ) ) {
			$addons[ $k ]['path'] = wp_normalize_path( $path2 );
		}

		$addons[ $k ]['pro']    = isset( $addon['pro'] ) ? $addon['pro'] : false;
		$addons[ $k ]['active'] = isset( $option[ $k ] ) ? true : false;
		$addons[ $k ]['id']     = $k;
		$addons[ $k ]['class']  = sanitize_html_class( sanitize_title( str_replace( array( '/', '.php' ), array( '-', '' ), 'addon-' . $k ) ) );

		if ( ! empty( $addons[ $k ]['path'] ) && file_exists( $addons[ $k ]['path'] ) ) {
			$valid_addons[ $k ] = $addons[ $k ];
		}
	}

	wp_cache_set( 'addons', $valid_addons, 'smartqa' );
	return $valid_addons;
}

/**
 * Return all active addons.
 *
 * @return array
 * @since 4.0.0
 */
function asqa_get_active_addons() {
	$active_addons = array();

	foreach ( asqa_get_addons() as $addon ) {
		if ( $addon['active'] ) {
			$active_addons[ $addon['id'] ] = $addon;
		}
	}

	return $active_addons;
}

/**
 * Return a single addon by file path.
 *
 * @param string $file Main file name of addon.
 * @return array
 * @since 1.0.0
 */
function asqa_get_addon( $file ) {
	$search = false;

	foreach ( asqa_get_addons() as $f => $addon ) {
		if ( $f === $file ) {
			$search = $addon;
			break;
		}
	}

	return $search;
}

/**
 * Activate an addon and trigger addon activation hook.
 *
 * @param string $addon_name Addon file name.
 * @return boolean
 *
 * @since 1.0.0 Fixed fatal error if addon does not exists.
 */
function asqa_activate_addon( $addon_name ) {
	if ( asqa_is_addon_active( $addon_name ) ) {
		return false;
	}

	global $asqa_addons_activation;

	$opt        = get_option( 'smartqa_addons', array() );
	$all_addons = asqa_get_addons();
	$addon_name = wp_normalize_path( $addon_name );

	if ( isset( $all_addons[ $addon_name ] ) ) {
		$opt[ $addon_name ] = true;
		update_option( 'smartqa_addons', $opt );

		$file = $all_addons[ $addon_name ]['path'];

		// Check file exists before requiring.
		if ( ! file_exists( $file ) ) {
			return false;
		}

		require_once $file;

		if ( isset( $asqa_addons_activation[ $addon_name ] ) ) {
			call_user_func( $asqa_addons_activation[ $addon_name ] );
		}

		do_action( 'asqa_addon_activated', $addon_name );

		// Fix to drop wpengine cache.
		if ( class_exists( 'WpeCommon' ) ) {
			WpeCommon::purge_memcached();
			WpeCommon::clear_maxcdn_cache();
			WpeCommon::purge_varnish_cache();
		}

		// Delete cache.
		wp_cache_delete( 'addons', 'smartqa' );

		// Flush rewrite rules.
		asqa_opt( 'asqa_flush', 'true' );

		return true;
	}

	return false;
}

/**
 * Deactivate addons.
 *
 * @param string $addon_name Addons file name.
 * @return boolean
 */
function asqa_deactivate_addon( $addon_name ) {
	if ( ! asqa_is_addon_active( $addon_name ) ) {
		return false;
	}

	$opt        = get_option( 'smartqa_addons', array() );
	$all_addons = asqa_get_addons();
	$addon_name = wp_normalize_path( $addon_name );

	if ( isset( $all_addons[ $addon_name ] ) ) {
		unset( $opt[ $addon_name ] );
		update_option( 'smartqa_addons', $opt );
		do_action( 'asqa_addon_deactivated', $addon_name );

		// Delete cache.
		wp_cache_delete( 'addons', 'smartqa' );

		return true;
	}

	return false;
}

/**
 * Check if addon is active.
 *
 * @param string $addon Addon file name without path.
 * @return boolean
 * @since 4.0.0
 */
function asqa_is_addon_active( $addon ) {
	$addons = asqa_get_active_addons();

	if ( isset( $addons[ $addon ] ) ) {
		return true;
	}

	return false;
}

/**
 * Get addon image.
 *
 * @param string $file Addon main file name. Example: `avatar.php` or `category.php`.
 * @return void|string Return image if exists else null.
 * @since 1.0.0
 */
function asqa_get_addon_image( $file ) {
	$addon = asqa_get_addon( $file );

	if ( isset( $addon['path'] ) ) {
		$path_parts = pathinfo( $addon['path'] );
		if ( isset( $path_parts['dirname'] ) && file_exists( $path_parts['dirname'] . '/image.png' ) ) {
			return plugin_dir_url( $addon['path'] ) . '/image.png';
		}
	}
}

/**
 * Check if addon has options.
 *
 * @param string $file Addon main file.
 * @return boolean
 * @since 1.0.0
 */
function asqa_addon_has_options( $file ) {
	$addon = asqa_get_addon( $file );

	$form_name = str_replace( '.php', '', $addon['id'] );
	$form_name = str_replace( '/', '_', $form_name );

	if ( smartqa()->form_exists( 'addon-' . $form_name ) ) {
		return true;
	}

	return false;
}

/**
 * Trigger question and answer update hooks.
 *
 * @param object $_post Post object.
 * @param string $event Event name.
 * @since 4.0.0
 */
function asqa_trigger_qa_update_hook( $_post, $event ) {
	$_post = asqa_get_post( $_post );

	// Check if post type is question or answer.
	if ( ! in_array( $_post->post_type, array( 'question', 'answer' ), true ) ) {
		return;
	}

	/**
		* Triggered right after updating question/answer.
		*
		* @param object $_post      Inserted post object.
		* @since 0.9
		*/
	do_action( 'asqa_after_update_' . $_post->post_type, $_post, $event );
}

/**
 * Find item in in child array.
 *
 * @param  mixed   $needle Needle to find.
 * @param  mixed   $haystack Haystack.
 * @param  boolean $strict Strict match.
 * @return boolean
 */
function asqa_in_array_r( $needle, $haystack, $strict = false ) {
	foreach ( $haystack as $item ) {
		if ( ( $strict ? $item === $needle : $item == $needle ) || ( is_array( $item ) && asqa_in_array_r( $needle, $item, $strict ) ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			return true;
		}
	}
	return false;
}

/**
 * Return short link to a item.
 *
 * @param array $args Arguments.
 * @return string Shortlink to a SmartQa page or item.
 *
 * @category haveTest
 *
 * @since unknown
 * @since 1.0.0 Fixed: trailing slash.
 */
function asqa_get_short_link( $args ) {
	array_unshift( $args, array( 'asqa_page' => 'shortlink' ) );
	return add_query_arg( $args, home_url( '/' ) );
}

/**
 * Register a callback function which triggred
 * after activating an addon.
 *
 * @param string       $addon Name of addon.
 * @param string|array $cb    Callback function name.
 * @since 4.0.0
 */
function asqa_addon_activation_hook( $addon, $cb ) {
	global $asqa_addons_activation;
	$addon = wp_normalize_path( $addon );

	$asqa_addons_activation[ $addon ] = $cb;
}

/**
 * Insert a value or key/value pair after a specific key in an array.  If key doesn't exist, value is appended
 * to the end of the array.
 *
 * @param array  $array Array.
 * @param string $key   Key.
 * @param array  $new   Array.
 *
 * @return array
 */
function asqa_array_insert_after( $array = array(), $key = '', $new = array() ) {
	$keys  = array_keys( $array );
	$index = array_search( $key, $keys ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
	$pos   = false === $index ? count( $array ) : $index + 1;

	return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
}

/**
 * Utility function for getting random values with weighting.
 *
 * @param integer $min Minimum integer.
 * @param integer $max Maximum integer.
 * @param weight  $weight Weight of random integer.
 * @return integer
 */
function asqa_rand( $min, $max, $weight ) {
	$offset = $max - $min + 1;
	return floor( $min + pow( lcg_value(), $weight ) * $offset );
}

/**
 * Convert array notation (string, not real array) to dot notation.
 *
 * @param boolean|string $path Path name.
 * @return string Path separated by dot notation.
 */
function asqa_to_dot_notation( $path = false ) {
	$parsed = rtrim( str_replace( '..', '.', str_replace( array( ']', '[' ), '.', $path ) ), '.' );
	return $parsed;
}

/**
 * Set key => value in an array.
 *
 * @param array  $arr  Array in which key value need to set.
 * @param string $path Path of new array item.
 * @param mixed  $val  Value to set.
 * @param bool   $merge_arr Should merge array.
 *
 * @return array Updated array.
 * @since 1.0.0
 * @since Added new parameter `$merge_arr`.
 */
function asqa_set_in_array( &$arr, $path, $val, $merge_arr = false ) {
	$path = is_string( $path ) ? explode( '.', $path ) : $path;
	$loc  = &$arr;

	foreach ( (array) $path as $step ) {
		$loc = &$loc[ $step ];
	}

	if ( $merge_arr && is_array( $loc ) ) {
		$loc = array_merge( $loc, $val );
	} else {
		$loc = $val;
	}

	return $loc;
}

/**
 * Output new/edit question form.
 *
 * @param null $deprecated Deprecated argument.
 * @return void
 *
 * @since unknown
 * @since 1.0.0 Moved from includes\ask-form.php. Deprecated first argument. Using new form class.
 * @since 1.0.0 Don't use asqa_ajax as action. Set values here while editing. Get values form session if exists.
 *
 * @category haveTests
 */
function asqa_ask_form( $deprecated = null ) {
	if ( ! is_null( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '1.0.0', 'Use $_GET[id] for currently editing question ID.' );
	}

	$editing    = false;
	$editing_id = asqa_sanitize_unslash( 'id', 'r' );

	// If post_id is empty then its not editing.
	if ( ! empty( $editing_id ) ) {
		$editing = true;
	}

	if ( $editing && ! asqa_user_can_edit_question( $editing_id ) ) {
		echo '<p>' . esc_attr__( 'You cannot edit this question.', 'smart-question-answer' ) . '</p>';
		return;
	}

	if ( ! $editing && ! asqa_user_can_ask() ) {
		echo '<p>' . esc_attr__( 'You do not have permission to ask a question.', 'smart-question-answer' ) . '</p>';
		return;
	}

	$args = array(
		'hidden_fields' => array(
			array(
				'name'  => 'action',
				'value' => 'asqa_form_question',
			),
		),
	);

	$values         = array();
	$session_values = smartqa()->session->get( 'form_question' );

	// Add value when editing post.
	if ( $editing ) {
		$question = asqa_get_post( $editing_id );

		$form['editing']      = true;
		$form['editing_id']   = $editing_id;
		$form['submit_label'] = __( 'Update Question', 'smart-question-answer' );

		$values['post_title']   = $question->post_title;
		$values['post_content'] = $question->post_content;
		$values['attachments'] = "test";
		$values['is_private']   = 'private_post' === $question->post_status ? true : false;

		if ( isset( $values['anonymous_name'] ) ) {
			$fields = asqa_get_post_field( 'fields', $question );

			$values['anonymous_name'] = ! empty( $fields['anonymous_name'] ) ? $fields['anonymous_name'] : '';
		}
	} elseif ( ! empty( $session_values ) ) {
		// Set last session values if not editing.
		$values = $session_values;
	}

	// Generate form.
	smartqa()->get_form( 'question' )->set_values( $values )->generate( $args );
}

/**
 * Remove stop words from post name if option is enabled.
 *
 * @param  string $str Post name to filter.
 * @return string
 *
 * @since  1.0.0
 * @since 1.0.0 Moved from includes\ask-form.php.
 */
function asqa_remove_stop_words_post_name( $str ) {
	$str = sanitize_title( $str );

	if ( asqa_opt( 'keep_stop_words' ) ) {
		return $str;
	}

	$post_name = asqa_remove_stop_words( $str );

	// Check if post name is not empty.
	if ( ! empty( $post_name ) ) {
		return $post_name;
	}

	// If empty then return original without stripping stop words.
	return sanitize_title( $str );
}

/**
 * Send ajax response after posting an answer.
 *
 * @param integer|object $question_id Question ID or object.
 * @param integer|object $answer_id   Answer ID or object.
 * @return void
 * @since 4.0.0
 * @since 1.0.0 Moved from includes\answer-form.php.
 */
function asqa_answer_post_ajax_response( $question_id, $answer_id ) {
	$question = asqa_get_post( $question_id );
	// Get existing answer count.
	$current_ans = asqa_count_published_answers( $question_id );

	global $post;
	$post = asqa_get_post( $answer_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	setup_postdata( $post );

	ob_start();
	global $withcomments;
	$withcomments = true; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

	asqa_get_template_part( 'answer' );

	$html = ob_get_clean();

	// translators: %d is answer count.
	$count_label = sprintf( _n( '%d Answer', '%d Answers', $current_ans, 'smart-question-answer' ), $current_ans );

	$result = array(
		'success'      => true,
		'ID'           => $answer_id,
		'form'         => 'answer',
		'div_id'       => '#post-' . get_the_ID(),
		'can_answer'   => asqa_user_can_answer( $post->ID ),
		'html'         => $html,
		'snackbar'     => array( 'message' => __( 'Answer submitted successfully', 'smart-question-answer' ) ),
		'answersCount' => array(
			'text'   => $count_label,
			'number' => $current_ans,
		),
	);

	asqa_ajax_json( $result );
}

/**
 * Generate answer form.
 *
 * @param  mixed   $question_id  Question iD.
 * @param  boolean $editing      true if post is being edited.
 * @return void
 * @since unknown
 * @since 1.0.0 Moved from includes\answer-form.php. Using new Form class.
 * @since 1.0.0 Don't use asqa_ajax as action.
 * @since 1.0.0 Fixed: editing answer creates new answer.
 */
function asqa_answer_form( $question_id, $editing = false ) {
	$editing    = false;
	$editing_id = asqa_sanitize_unslash( 'id', 'r' );

	// If post_id is empty then its not editing.
	if ( ! empty( $editing_id ) ) {
		$editing = true;
	}

	if ( $editing && ! asqa_user_can_edit_answer( $editing_id ) ) {
		echo '<p>' . esc_attr__( 'You cannot edit this answer.', 'smart-question-answer' ) . '</p>';
		return;
	}

	if ( ! $editing && ! asqa_user_can_answer( $question_id ) ) {
		echo '<p>' . esc_attr__( 'You do not have permission to answer this question.', 'smart-question-answer' ) . '</p>';
		return;
	}

	$args = array(
		'hidden_fields' => array(
			array(
				'name'  => 'action',
				'value' => 'asqa_form_answer',
			),
			array(
				'name'  => 'question_id',
				'value' => (int) $question_id,
			),
		),
	);

	$values         = array();
	$session_values = smartqa()->session->get( 'form_answer_' . $question_id );

	// Add value when editing post.
	if ( $editing ) {
		$answer = asqa_get_post( $editing_id );

		$form['editing']      = true;
		$form['editing_id']   = $editing_id;
		$form['submit_label'] = __( 'Update Answer', 'smart-question-answer' );

		$values['post_title']   = $answer->post_title;
		$values['post_content'] = $answer->post_content;
		$values['is_private']   = 'private_post' === $answer->post_status ? true : false;

		if ( isset( $values['anonymous_name'] ) ) {
			$fields = asqa_get_post_field( 'fields', $answer );

			$values['anonymous_name'] = ! empty( $fields['anonymous_name'] ) ? $fields['anonymous_name'] : '';
		}

		$args['hidden_fields'][] = array(
			'name'  => 'post_id',
			'value' => (int) $editing_id,
		);
	} elseif ( ! empty( $session_values ) ) {
		// Set last session values if not editing.
		$values = $session_values;
	}

	smartqa()->get_form( 'answer' )->set_values( $values )->generate( $args );
}

/**
 * Generate comment form.
 *
 * @param  false|integer $post_id  Question or answer id.
 * @param  false|object  $_comment Comment id or object.
 * @return void
 *
 * @since 1.0.0
 * @since 1.0.0 Don't use asqa_ajax.
 */
function asqa_comment_form( $post_id = false, $_comment = false ) {
	if ( false === $post_id ) {
		$post_id = get_the_ID();
	}

	if ( ! asqa_user_can_comment( $post_id ) ) {
		return;
	}

	$args = array(
		'hidden_fields' => array(
			array(
				'name'  => 'post_id',
				'value' => $post_id,
			),
			array(
				'name'  => 'action',
				'value' => 'asqa_form_comment',
			),
		),
	);

	$form = smartqa()->get_form( 'comment' );

	// Add value when editing post.
	if ( false !== $_comment && ! empty( $_comment ) ) {
		$_comment = get_comment( $_comment );
		$values   = array();

		$args['hidden_fields'][] = array(
			'name'  => 'comment_id',
			'value' => $_comment->comment_ID,
		);

		$values['content'] = $_comment->comment_content;

		if ( empty( $_comment->user_id ) ) {
			$values['author'] = $_comment->comment_author;
			$values['email']  = $_comment->comment_author_email;
			$values['url']    = $_comment->comment_author_url;
		}

		$form->set_values( $values );
	}

	$form->generate( $args );
}

/**
 * Include tinymce assets.
 *
 * @return void
 * @since 1.0.0
 */
function asqa_ajax_tinymce_assets() {
	if ( ! class_exists( '_WP_Editors' ) ) {
		require ABSPATH . WPINC . '/class-wp-editor.php';
	}

	\_WP_Editors::enqueue_scripts();

	ob_start();
	print_footer_scripts();
	$scripts = ob_get_clean();

	echo str_replace( 'jquery-core,', '', $scripts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	\_WP_Editors::editor_js();
}

/**
 * All pages required of SmartQa.
 *
 * @return array
 * @since 1.0.0
 */
function asqa_main_pages() {
	$pages = array(
		'base_page'       => array(
			'label'      => __( 'Archives page', 'smart-question-answer' ),
			'desc'       => __( 'Page used to display question archive (list). Sometimes this page is used for displaying other subpages of SmartQa.<br/>This page is also referred as <b>Base Page</b> in SmartQa documentations and support forum.', 'smart-question-answer' ),
			'post_title' => __( 'Questions', 'smart-question-answer' ),
			'post_name'  => 'questions',
		),
		'ask_page'        => array(
			'label'      => __( 'Ask page', 'smart-question-answer' ),
			'desc'       => __( 'Page used to display ask form.', 'smart-question-answer' ),
			'post_title' => __( 'Ask a question', 'smart-question-answer' ),
			'post_name'  => 'ask',
		),
		'user_page'       => array(
			'label'      => __( 'User page', 'smart-question-answer' ),
			'desc'       => __( 'Page used to display user profile.', 'smart-question-answer' ),
			'post_title' => __( 'Profile', 'smart-question-answer' ),
			'post_name'  => 'profile',
		),
		'categories_page' => array(
			'label'      => __( 'Categories page', 'smart-question-answer' ),
			'desc'       => __( 'Page used to display question categories. NOTE: Categories addon must be enabled to render this page.', 'smart-question-answer' ),
			'post_title' => __( 'Categories', 'smart-question-answer' ),
			'post_name'  => 'categories',
		),
		'tags_page'       => array(
			'label'      => __( 'Tags page', 'smart-question-answer' ),
			'desc'       => __( 'Page used to display question tags. NOTE: Tags addon must be enabled to render this page.', 'smart-question-answer' ),
			'post_title' => __( 'Tags', 'smart-question-answer' ),
			'post_name'  => 'tags',
		),
		'activities_page' => array(
			'label'      => __( 'Activities page', 'smart-question-answer' ),
			'desc'       => __( 'Page used to display all smartqa activities.', 'smart-question-answer' ),
			'post_title' => __( 'Activities', 'smart-question-answer' ),
			'post_name'  => 'activities',
		),
	);

	/**
	 * Hook for filtering main pages of SmartQa. Custom main pages
	 * can be registered using this hook.
	 *
	 * @param array $pages Array of pages.
	 * @since 1.0.0
	 */
	return apply_filters( 'asqa_main_pages', $pages );
}

/**
 * Return post IDs of main pages.
 *
 * @return array
 * @since 1.0.0
 */
function asqa_main_pages_id() {
	$main_pages = array_keys( asqa_main_pages() );
	$pages_id   = array();

	foreach ( $main_pages as $slug ) {
		$pages_id[ $slug ] = asqa_opt( $slug );
	}

	return $pages_id;
}

/**
 * Get current user id for SmartQa profile.
 *
 * This function must be used only in SmartQa profile. This function checks for
 * user ID in queried object, hence if not in user page
 *
 * @return integer Always returns 0 if not in SmartQa profile page.
 * @since 1.0.0
 */
function asqa_current_user_id() {
	if ( 'user' === asqa_current_page() ) {
		$query_object = get_queried_object();

		if ( $query_object instanceof WP_User ) {
			return $query_object->ID;
		}
	}

	return 0;
}

/**
 * Check if post object is SmartQa CPT i.e. question or answer.
 *
 * @param WP_Post $_post WordPress post object.
 * @return boolean
 * @since 1.0.0
 */
function asqa_is_cpt( $_post ) {
	return ( in_array( $_post->post_type, array( 'answer', 'question' ), true ) );
}

/**
 * Removes all filters from a WordPress filter, and stashes them in the smartqa()
 * global in the event they need to be restored later.
 * Copied directly from bbPress plugin.
 *
 * @global WP_filter $wp_filter
 * @global array $merged_filters
 *
 * @param string $tag Hook name.
 * @param int    $priority Hook priority.
 * @return bool
 *
 * @since 1.0.0
 */
function asqa_remove_all_filters( $tag, $priority = false ) {
	global $wp_filter, $merged_filters;

	$asqa = smartqa();

	if ( ! is_object( $asqa->new_filters ) ) {
		$asqa->new_filters = new stdClass();
	}

	// Filters exist.
	if ( isset( $wp_filter[ $tag ] ) ) {

		// Filters exist in this priority.
		if ( ! empty( $priority ) && isset( $wp_filter[ $tag ][ $priority ] ) ) {

			// Store filters in a backup.
			$asqa->new_filters->wp_filter[ $tag ][ $priority ] = $wp_filter[ $tag ][ $priority ];

			// Unset the filters.
			unset( $wp_filter[ $tag ][ $priority ] );
		} else {
			// Store filters in a backup.
			$asqa->new_filters->wp_filter[ $tag ] = $wp_filter[ $tag ];

			// Unset the filters.
			unset( $wp_filter[ $tag ] );
		}
	}

	// Check merged filters.
	if ( isset( $merged_filters[ $tag ] ) ) {

		// Store filters in a backup.
		$asqa->new_filters->merged_filters[ $tag ] = $merged_filters[ $tag ];

		// Unset the filters.
		unset( $merged_filters[ $tag ] );
	}

	return true;
}

/**
 * Return current timestamp.
 *
 * @return int
 * @since 1.0.0
 */
function asqa_get_current_timestamp() {
	$local_time = current_datetime();

	return $local_time->getTimestamp() + $local_time->getOffset();
}
