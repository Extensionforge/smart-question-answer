<?php
/**
 * Class for smartqa theme
 *
 * @package      SmartQa
 * @subpackage   Theme Hooks
 * @author       Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license      GPL-3.0+
 * @link         https://extensionforge.com
 * @copyright    2014 Peter Mertzlin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
add_action('set_logged_in_cookie', 'custom_get_logged_in_cookie_asqa', 10, 6);
function custom_get_logged_in_cookie_asqa($logged_in_cookie, $expire, $expiration, $user_id, $logged_in_text, $token)
{
    global $wpdb;
      if (isset($_COOKIE['asqa_guest_cookie'])) {
		    	
		    	$cookieValue = $_COOKIE['asqa_guest_cookie'];
		    	$neueposts = explode (",", $cookieValue);
		    	
		    	
		    	$table_name = $wpdb->prefix . 'posts';
		    	$activity = $wpdb->prefix . 'asqa_activity';

		    	foreach($neueposts as $single){ $newid = intval($single);
		    		$wpdb->update($table_name, array(
			            'post_author' => $user_id
			        ) , array(
			            'id' => $newid
			        ) , array(
			            '%d'
			        ) , array(
			            '%d'
			        ));

			        $wpdb->update($table_name, array(
			            'post_status' => 'publish'
			        ) , array(
			            'id' => $newid
			        ) , array(
			            '%s'
			        ) , array(
			            '%d'
			        ));

			        $wpdb->update($activity, array(
			            'activity_user_id' => $user_id
			        ) , array(
			            'activity_q_id' => $newid, 'activity_action' => 'new_q'
			        ) , array(
			            '%d'
			        ) , array(
			            '%d','%s'
			        ));


		    	}
		    	
		    }
		  
}
/**
 * Holds all hooks related to frontend layout/theme
 */
class SmartQa_Theme {
	/**
	 * Function get called on init
	 */
	public static function init_actions() {
		// Register smartqa shortcode.
		add_shortcode( 'smartqa', array( SmartQa_BasePage_Shortcode::get_instance(), 'smartqa_sc' ) );

		// Register question shortcode.
		add_shortcode( 'question', array( SmartQa_Question_Shortcode::get_instance(), 'smartqa_question_sc' ) );
	}

	/**
	 * The main filter used for theme compatibility and displaying custom SmartQa
	 * theme files.
	 *
	 * @param string $template Current template file.
	 * @return string Template file to use.
	 *
	 * @since 1.0.0
	 */
	public static function template_include( $template = '' ) {
		return apply_filters( 'asqa_template_include', $template );
	}

	/**
	 * Reset main query vars and filter 'the_content' to output a SmartQa
	 * template part as needed.
	 *
	 * @param string $template Template name.
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function template_include_theme_compat( $template = '' ) {
		if ( asqa_current_page( 'question' ) ) {
			ob_start();
			echo '<div class="smartqa" id="smartqa">';
			SmartQa_Common_Pages::question_page();
			echo '</div>';
			$html = ob_get_clean();

			asqa_theme_compat_reset_post(
				array(
					'ID'             => get_question_id(),
					'post_title'     => get_the_title( get_question_id() ),
					'post_author'    => get_post_field( 'post_author', get_question_id() ),
					'post_date'      => get_post_field( 'post_date', get_question_id() ),
					'post_content'   => $html,
					'post_type'      => 'question',
					'post_status'    => get_post_status( get_question_id() ),
					'is_single'      => true,
					'comment_status' => 'closed',
				)
			);
		}

		if ( true === smartqa()->theme_compat->active ) {
			asqa_remove_all_filters( 'the_content' );
		}

		return $template;
	}

	/**
	 * SmartQa theme function as like WordPress theme function.
	 *
	 * @return void
	 */
	public static function includes_theme() {
		require_once asqa_get_theme_location( 'functions.php' );
	}

	/**
	 * Add answer-seleted class in post_class.
	 *
	 * @param  array $classes Post class attribute.
	 * @return array
	 * @since 1.0.0
	 * @since 1.0.0 Fixes #426: Undefined property `post_type`.
	 */
	public static function question_answer_post_class( $classes ) {
		global $post;

		if ( ! $post ) {
			return $classes;
		}

		if ( 'question' === $post->post_type ) {
			if ( asqa_have_answer_selected( $post->ID ) ) {
				$classes[] = 'answer-selected';
			}

			if ( asqa_is_featured_question( $post->ID ) ) {
				$classes[] = 'featured-question';
			}

			$classes[] = 'answer-count-' . asqa_get_answers_count();
		} elseif ( 'answer' === $post->post_type ) {
			if ( asqa_is_selected( $post->ID ) ) {
				$classes[] = 'best-answer';
			}

			if ( ! asqa_user_can_read_answer( $post ) ) {
				$classes[] = 'no-permission';
			}
		}

		return $classes;
	}

	/**
	 * Add smartqa classes to body.
	 *
	 * @param  array $classes Body class attribute.
	 * @return array
	 * @since 1.0.0
	 */
	public static function body_class( $classes ) {
		// Add smartqa class to body.
		if ( is_smartqa() ) {
			$classes[] = 'smartqa-content';
			$classes[] = 'asqa-page-' . asqa_current_page();
		}

		return $classes;
	}

	/**
	 * Filter wp_title.
	 *
	 * @param string $title WP page title.
	 * @return string
	 * @since 1.0.0 Do not override title of all pages except single question.
	 */
	public static function asqa_title( $title ) {
		if ( is_smartqa() ) {
			remove_filter( 'wp_title', array( __CLASS__, 'asqa_title' ) );

			if ( is_question() ) {
				return asqa_question_title_with_solved_prefix() . ' | ';
			}
		}

		return $title;
	}

	/**
	 * Add default before body sidebar in SmartQa contents
	 */
	public static function asqa_before_html_body() {
		

		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$data         = array(
				'user_login'   => $current_user->data->user_login,
				'display_name' => $current_user->data->display_name,
				'user_email'   => $current_user->data->user_email,
				'avatar'       => get_avatar( $current_user->ID ),
			);
			?>
				<script type="text/javascript">
					apCurrentUser = <?php echo wp_json_encode( $data ); ?>;
				</script>
			<?php
		}
		dynamic_sidebar( 'asqa-before' );
		?>
		
		
		<?php
	}

	/**
	 * Add feed and links in HEAD of the document
	 *
	 * @since 1.0.0 Removed question sortlink override.
	 */
	public static function wp_head() {
		if ( asqa_current_page( 'base' ) ) {
			$q_feed = get_post_type_archive_feed_link( 'question' );
			$a_feed = get_post_type_archive_feed_link( 'answer' );
			echo '<link rel="alternate" type="application/rss+xml" title="' . esc_attr__( 'Question Feed', 'smart-question-answer' ) . '" href="' . esc_url( $q_feed ) . '" />';
			echo '<link rel="alternate" type="application/rss+xml" title="' . esc_attr__( 'Answers Feed', 'smart-question-answer' ) . '" href="' . esc_url( $a_feed ) . '" />';
		}
	}

	/**
	 * Ajax callback for post actions dropdown.
	 *
	 * @since 1.0.0
	 */
	public static function post_actions() {
		$post_id = (int) asqa_sanitize_unslash( 'post_id', 'r' );

		if ( ! check_ajax_referer( 'post-actions-' . $post_id, 'nonce', false ) || ! is_user_logged_in() ) {
			asqa_ajax_json( 'something_wrong' );
		}

		asqa_ajax_json(
			array(
				'success' => true,
				'actions' => asqa_post_actions( $post_id ),
			)
		);
	}

	/**
	 * Shows lists of attachments of a question
	 */
	public static function question_attachments() {
		if ( asqa_have_attach() ) {
			include asqa_get_theme_location( 'attachments.php' );
		}
	}

	/**
	 * Check if smartqa.php file exists in theme. If exists
	 * then load this template for SmartQa.
	 *
	 * @param  string $template Template.
	 * @return string
	 * @since  1.0.0
	 * @since  1.0.0 Give priority to page templates and then smartqa.php and lastly fallback to page.php.
	 * @since  1.0.0 Load single question template if exists.
	 */
	public static function smartqa_basepage_template( $template ) {
		if ( is_smartqa() ) {
			$templates = array( 'smartqa.php', 'page.php', 'singular.php', 'index.php' );

			if ( is_page() ) {
				$_post = get_queried_object();

				array_unshift( $templates, 'page-' . $_post->ID . '.php' );
				array_unshift( $templates, 'page-' . $_post->post_name . '.php' );

				$page_template = get_post_meta( $_post->ID, '_wp_page_template', true );

				if ( ! empty( $page_template ) && 'default' !== $page_template ) {
					array_unshift( $templates, $page_template );
				}
			} elseif ( is_single() ) {
				$_post = get_queried_object();

				array_unshift( $templates, 'single-' . $_post->ID . '.php' );
				array_unshift( $templates, 'single-' . $_post->post_name . '.php' );
				array_unshift( $templates, 'single-' . $_post->post_type . '.php' );
			} elseif ( is_tax() ) {
				$_term     = get_queried_object();
				$term_type = str_replace( 'question_', '', $_term->taxonomy );
				array_unshift( $templates, 'smartqa-' . $term_type . '.php' );
			}

			$new_template = locate_template( $templates );

			if ( '' !== $new_template ) {
				return $new_template;
			}
		}

		return $template;
	}

	/**
	 * Generate question excerpt if there is not any already.
	 *
	 * @param string      $excerpt Default excerpt.
	 * @param object|null $post    WP_Post object.
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_the_excerpt( $excerpt, $post = null ) {
		$post = get_post( $post );

		if ( 'question' === $post->post_type ) {
			if ( get_query_var( 'answer_id' ) ) {
				$post = asqa_get_post( get_query_var( 'answer_id' ) );
			}

			// Check if excerpt exists.
			if ( ! empty( $post->post_excerpt ) ) {
				return $post->post_excerpt;
			}

			$excerpt_length = apply_filters( 'excerpt_length', 55 );
			$excerpt_more   = apply_filters( 'excerpt_more', ' [&hellip;]' );
			return wp_trim_words( $post->post_content, $excerpt_length, $excerpt_more );
		}

		return $excerpt;
	}

	/**
	 * Remove hentry class from question, answers and main pages .
	 *
	 * @param array   $post_classes Post classes.
	 * @param array   $class        An array of additional classes added to the post.
	 * @param integer $post_id      Post ID.
	 * @return array
	 * @since 1.0.0
	 */
	public static function remove_hentry_class( $post_classes, $class, $post_id ) {
		$_post = asqa_get_post( $post_id );

		if ( $_post && ( in_array( $_post->post_type, array( 'answer', 'question' ), true ) || in_array( $_post->ID, asqa_main_pages_id() ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			return array_diff( $post_classes, array( 'hentry' ) );
		}

		return $post_classes;
	}

	/**
	 * Callback for showing content below question and answer.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function after_question_content() {
		echo wp_kses_post( asqa_post_status_badge() );

		$_post    = asqa_get_post();
		$activity = asqa_recent_activity( null, false );

		if ( ! empty( $activity ) ) {
			echo '<div class="asqa-post-updated"><i class="apicon-clock"></i>' . wp_kses_post( $activity ) . '</div>';
		}
	}
}
