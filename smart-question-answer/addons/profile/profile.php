<?php
/**
 * An SmartQa add-on to for displaying user profile.
 *
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 * @copyright  2014 extensionforge.com & Peter Mertzlin
 * @license    GPL-3.0+ https://www.gnu.org/licenses/gpl-3.0.txt
 * @link       https://extensionforge.com
 * @package    SmartQa
 * @subpackage User Profile Addon
 *
 * @smartqa-addon
 * Addon Name:    User Profile
 * Addon URI:     https://extensionforge.com
 * Description:   Display user profile.
 * Author:        Peter Mertzlin
 * Author URI:    https://extensionforge.com
 */

namespace SmartQa\Addons;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * User profile hooks.
 */
class Profile extends \SmartQa\Singleton {
	/**
	 * Instance of this class.
	 *
	 * @var     object
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since 4.0.0
	 */
	protected function __construct() {
		asqa_add_default_options(
			array(
				'user_page_slug_questions'  => 'questions',
				'user_page_slug_answers'    => 'answers',
				'user_page_title_questions' => __( 'Questions', 'smart-question-answer' ),
				'user_page_title_answers'   => __( 'Answers', 'smart-question-answer' ),
			)
		);

		smartqa()->add_filter( 'asqa_settings_menu_features_groups', $this, 'add_to_settings_page' );
		smartqa()->add_action( 'asqa_form_options_features_profile', $this, 'options' );
		asqa_register_page( 'user', __( 'User profile', 'smart-question-answer' ), array( $this, 'user_page' ), true, true );

		smartqa()->add_action( 'asqa_rewrites', $this, 'rewrite_rules', 10, 3 );
		smartqa()->add_action( 'asqa_ajax_user_more_answers', $this, 'load_more_answers', 10, 2 );
		smartqa()->add_filter( 'wp_title', $this, 'page_title' );
		smartqa()->add_action( 'the_post', $this, 'filter_page_title' );
		smartqa()->add_filter( 'asqa_current_page', $this, 'asqa_current_page' );
		smartqa()->add_filter( 'posts_pre_query', $this, 'modify_query_archive', 999, 2 );
	}

	/**
	 * Add tags settings to features settings page.
	 *
	 * @param array $groups Features settings group.
	 * @return array
	 * @since 1.0.0
	 */
	public function add_to_settings_page( $groups ) {
		$groups['profile'] = array(
			'label' => __( 'Profile', 'smart-question-answer' ),
		);

		return $groups;
	}

	/**
	 * Register profile options
	 */
	public function options() {
		$opt = asqa_opt();

		$form = array(
			'fields' => array(
				'user_page_title_questions' => array(
					'label' => __( 'Questions page title', 'smart-question-answer' ),
					'desc'  => __( 'Custom title for user profile questions page', 'smart-question-answer' ),
					'value' => $opt['user_page_title_questions'],
				),
				'user_page_slug_questions'  => array(
					'label' => __( 'Questions page slug', 'smart-question-answer' ),
					'desc'  => __( 'Custom slug for user profile questions page', 'smart-question-answer' ),
					'value' => $opt['user_page_slug_questions'],
				),
				'user_page_title_answers'   => array(
					'label' => __( 'Answers page title', 'smart-question-answer' ),
					'desc'  => __( 'Custom title for user profile answers page', 'smart-question-answer' ),
					'value' => $opt['user_page_title_answers'],
				),
				'user_page_slug_answers'    => array(
					'label' => __( 'Answers page slug', 'smart-question-answer' ),
					'desc'  => __( 'Custom slug for user profile answers page', 'smart-question-answer' ),
					'value' => $opt['user_page_slug_answers'],
				),
			),
		);

		return $form;
	}

	/**
	 * Layout of base page
	 */
	public function user_page() {
		$this->user_pages();
		dynamic_sidebar( 'asqa-top' );

		echo '<div id="asqa-user" class="asqa-row">';
		include asqa_get_theme_location( 'addons/user/index.php' );
		echo '</div>';
	}

	/**
	 * Add category pages rewrite rule.
	 *
	 * @param  array   $rules SmartQa rules.
	 * @param  string  $slug Slug.
	 * @param  integer $base_page_id Base page ID.
	 * @return array
	 */
	public function rewrite_rules( $rules, $slug, $base_page_id ) {
		$base_slug = get_page_uri( asqa_opt( 'user_page' ) );
		update_option( 'asqa_user_path', $base_slug, true );

		$new_rules = array();
		$new_rules = array(
			$base_slug . '/([^/]+)/([^/]+)/page/?([0-9]{1,})/?' => 'index.php?author_name=$matches[#]&asqa_page=user&user_page=$matches[#]&asqa_paged=$matches[#]',
			$base_slug . '/([^/]+)/([^/]+)/?' => 'index.php?author_name=$matches[#]&asqa_page=user&user_page=$matches[#]',
			$base_slug . '/([^/]+)/?'         => 'index.php?author_name=$matches[#]&asqa_page=user',
			$base_slug . '/?'                 => 'index.php?asqa_page=user',
		);

		return $new_rules + $rules;
	}

	/**
	 * Register user profile pages.
	 */
	public function user_pages() {
		if ( ! empty( smartqa()->user_pages ) ) {
			return;
		}

		smartqa()->user_pages = array(
			array(
				'slug'  => 'questions',
				'label' => __( 'Questions', 'smart-question-answer' ),
				'icon'  => 'apicon-question',
				'cb'    => array( $this, 'question_page' ),
				'order' => 2,
			),
			array(
				'slug'  => 'answers',
				'label' => __( 'Answers', 'smart-question-answer' ),
				'icon'  => 'apicon-answer',
				'cb'    => array( $this, 'answer_page' ),
				'order' => 2,
			),
		);

		do_action( 'asqa_user_pages' );

		foreach ( (array) smartqa()->user_pages as $key => $args ) {
			$rewrite = asqa_opt( 'user_page_slug_' . $args['slug'] );
			$title   = asqa_opt( 'user_page_title_' . $args['slug'] );

			// Override user page slug.
			if ( empty( $args['rewrite'] ) ) {
				smartqa()->user_pages[ $key ]['rewrite'] = ! empty( $rewrite ) ? sanitize_title( $rewrite ) : $args['slug'];
			}

			// Override user page title.
			if ( ! empty( $title ) ) {
				smartqa()->user_pages[ $key ]['label'] = $title;
			}

			// Add default order.
			if ( ! isset( $args['order'] ) ) {
				smartqa()->user_pages[ $key ]['order'] = 10;
			}
		}

		smartqa()->user_pages = asqa_sort_array_by_order( smartqa()->user_pages );
	}

	/**
	 * Output user profile menu.
	 *
	 * @param int|false $user_id Id of user, default is current user.
	 * @param string    $class   CSS class.
	 */
	public function user_menu( $user_id = false, $class = '' ) {
		$user_id     = false !== $user_id ? $user_id : asqa_current_user_id();
		$current_tab = get_query_var( 'user_page', asqa_opt( 'user_page_slug_questions' ) );
		$asqa_menu     = apply_filters( 'asqa_user_menu_items', smartqa()->user_pages, $user_id );

		echo '<ul class="asqa-tab-nav clearfix ' . esc_attr( $class ) . '">';

		foreach ( (array) $asqa_menu as $args ) {
			if ( empty( $args['private'] ) || ( true === $args['private'] && get_current_user_id() === $user_id ) ) {
				echo '<li class="asqa-menu-' . esc_attr( $args['slug'] ) . ( $args['rewrite'] === $current_tab ? ' active' : '' ) . '">';

				$url = isset( $args['url'] ) ? $args['url'] : asqa_user_link( $user_id, $args['rewrite'] );
				echo '<a href="' . esc_url( $url ) . '">';

				// Show icon.
				if ( ! empty( $args['icon'] ) ) {
					echo '<i class="' . esc_attr( $args['icon'] ) . '"></i>';
				}

				echo esc_attr( $args['label'] );

				// Show count.
				if ( ! empty( $args['count'] ) ) {
					echo '<span>' . esc_attr( number_format_i18n( $args['count'] ) ) . '</span>';
				}

				echo '</a>';
				echo '</li>';
			}
		}

		echo '</ul>';
	}

	/**
	 * Profile page title.
	 *
	 * @return string
	 */
	public function user_page_title() {
		$this->user_pages();
		$title       = asqa_user_display_name( asqa_current_user_id() );
		$current_tab = sanitize_title( get_query_var( 'user_page', asqa_opt( 'user_page_slug_questions' ) ) );
		$page        = asqa_search_array( smartqa()->user_pages, 'rewrite', $current_tab );

		if ( ! empty( $page ) ) {
			return $title . ' | ' . $page[0]['label'];
		}
	}

	/**
	 * Add user page title.
	 *
	 * @param  string $title SmartQa page title.
	 * @return string
	 */
	public function page_title( $title ) {
		if ( 'user' === asqa_current_page() ) {
			return $this->user_page_title() . ' | ';
		}

		return $title;
	}

	/**
	 * Filter user page title.
	 *
	 * @param object $_post WP post object.
	 * @return void
	 */
	public function filter_page_title( $_post ) {
		if ( 'user' === asqa_current_page() && asqa_opt( 'user_page' ) == $_post->ID && ! is_admin() ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$_post->post_title = $this->user_page_title();
		}
	}

	/**
	 * Render sub page template.
	 */
	public function sub_page_template() {
		$current      = get_query_var( 'user_page', asqa_opt( 'user_page_slug_questions' ) );
		$current_page = asqa_search_array( smartqa()->user_pages, 'rewrite', $current );

		if ( ! empty( $current_page ) ) {
			$current_page = $current_page[0];

			// Callback.
			if ( isset( $current_page['cb'] ) && is_array( $current_page['cb'] ) && method_exists( $current_page['cb'][0], $current_page['cb'][1] ) ) {
				call_user_func( $current_page['cb'] );
			} elseif ( function_exists( $current_page['cb'] ) ) {
				call_user_func( $current_page['cb'] );
			} else {
				esc_attr_e( 'Callback function not found for rendering this page', 'smart-question-answer' );
			}
		}
	}

	/**
	 * Display user questions page.
	 */
	public function question_page() {
		$user_id                        = asqa_current_user_id();
		$args['asqa_current_user_ignore'] = true;
		$args['author']                 = $user_id;

		/**
		* Filter authors question list args
		*
		* @var array
		*/
		$args = apply_filters( 'asqa_authors_questions_args', $args );

		smartqa()->questions = new \Question_Query( $args );

		include asqa_get_theme_location( 'addons/user/questions.php' );
	}

	/**
	 * Display user questions page.
	 */
	public function answer_page() {
		global $answers;

		$user_id = asqa_current_user_id();

		$args['asqa_current_user_ignore'] = true;
		$args['ignore_selected_answer'] = true;
		$args['showposts']              = 10;
		$args['author']                 = $user_id;

		/**
		 * Filter authors question list args
		 *
		 * @var array
		 */
		$args    = apply_filters( 'asqa_user_answers_args', $args );
		$answers = new \Answers_Query( $args );

		smartqa()->answers = $answers;

		asqa_get_template_part( 'addons/user/answers' );
	}

	/**
	 * Ajax callback for loading more answers.
	 *
	 * @return void
	 */
	public function load_more_answers() {
		global $answers;

		$user_id = asqa_sanitize_unslash( 'user_id', 'r' );
		$paged   = asqa_sanitize_unslash( 'current', 'r', 1 ) + 1;

		$args['asqa_current_user_ignore'] = true;
		$args['ignore_selected_answer'] = true;
		$args['showposts']              = 10;
		$args['author']                 = (int) $user_id;

		if ( false !== $paged ) {
			$args['paged'] = $paged;
		}

		/**
		 * Filter authors question list args
		 *
		 * @param array $args WP_Query arguments.
		 */
		$args    = apply_filters( 'asqa_user_answers_args', $args );
		$answers = new \Answers_Query( $args );

		smartqa()->answers = $answers;

		ob_start();
		if ( asqa_have_answers() ) {
			/* Start the Loop */
			while ( asqa_have_answers() ) :
				asqa_the_answer();
				asqa_get_template_part( 'addons/user/answer-item' );
			endwhile;
		}
		$html = ob_get_clean();

		asqa_ajax_json(
			array(
				'success' => true,
				'element' => '#asqa-bp-answers',
				'args'    => array(
					'asqa_ajax_action' => 'user_more_answers',
					'__nonce'        => wp_create_nonce( 'loadmore-answers' ),
					'type'           => 'answers',
					'current'        => $paged,
					'user_id'        => $user_id,
				),
				'html'    => $html,
			)
		);
	}

	/**
	 * Override current page of SmartQa.
	 *
	 * @param string $query_var Current page name.
	 * @return string
	 * @since 1.0.0
	 */
	public function asqa_current_page( $query_var ) {
		if ( is_author() && 'user' === get_query_var( 'asqa_page' ) ) {
			$query_var = 'user';
		}

		return $query_var;
	}

	/**
	 * Modify main query.
	 *
	 * @param  array  $posts  Array of post object.
	 * @param  object $query Wp_Query object.
	 * @return void|array
	 * @since 1.0.0
	 * @since 1.0.0 Redirect to current user profile if no author set.
	 * @since 1.0.0 Check for 404 error.
	 */
	public function modify_query_archive( $posts, $query ) {
		if ( $query->is_main_query() && ! $query->is_404 && 'user' === get_query_var( 'asqa_page' ) ) {
			$query_object = get_queried_object();

			if ( ! $query_object && ! get_query_var( 'author_name' ) && is_user_logged_in() ) {
				wp_safe_redirect( asqa_user_link( get_current_user_id() ) );
				exit;
			} elseif ( $query_object && $query_object instanceof \WP_User ) {
				return array( get_post( asqa_opt( 'user_page' ) ) );
			} else {
				$query->set_404();
				status_header( 404 );
			}
		}

		return $posts;
	}

	/**
	 * Override user page template.
	 *
	 * @param string $template Template file.
	 * @return string
	 * @since 1.0.0
	 */
	public function page_template( $template ) {
		if ( is_author() && 'user' === get_query_var( 'asqa_page' ) ) {
			$user_slug = asqa_opt( 'user_page_id' );
			return locate_template( array( 'page-' . $user_slug . '.php', 'page.php' ) );
		}

		return $template;
	}

	/**
	 * Get current user id for SmartQa profile.
	 *
	 * @return integer
	 * @since 1.0.0
	 */
	public function current_user_id() {
		$query_object = get_queried_object();
		$user_id      = get_queried_object_id();

		// Current user id if queried object is not set.
		if ( ! $query_object instanceof \WP_User || empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		return (int) $user_id;
	}

}

// Init addon.
Profile::init();
