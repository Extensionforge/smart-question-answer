<?php
/**
 * Add tags support in SmartQa questions.
 *
 * @author       Peter Mertzlin <peter.mertzlin@gmail.com>
 * @copyright    2014 extensionforge.com & Peter Mertzlin
 * @license      GPL-3.0+ https://www.gnu.org/licenses/gpl-3.0.txt
 * @link         https://extensionforge.com
 * @package      SmartQa
 * @subpackage   Tags Addon
 */

namespace SmartQa\Addons;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Tags addon for SmartQa
 */
class Tags extends \SmartQa\Singleton {
	/**
	 * Instance of this class.
	 *
	 * @var     object
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * Initialize the class
	 *
	 * @since 1.0.0 Added filter `asqa_category_questions_args`.
	 * @since 1.0.0 Added hook `asqa_settings_menu_features_groups`.
	 */
	protected function __construct() {
		asqa_register_page( 'tag', __( 'Tag', 'smart-question-answer' ), array( $this, 'tag_page' ), false );
		asqa_register_page( 'tags', __( 'Tags', 'smart-question-answer' ), array( $this, 'tags_page' ) );

		smartqa()->add_action( 'asqa_settings_menu_features_groups', $this, 'add_to_settings_page' );
		smartqa()->add_action( 'asqa_form_options_features_tag', $this, 'option_fields' );
		smartqa()->add_action( 'widgets_init', $this, 'widget_positions' );
		smartqa()->add_action( 'init', $this, 'register_question_tag', 1 );
		smartqa()->add_action( 'asqa_admin_menu', $this, 'admin_tags_menu' );
		smartqa()->add_action( 'asqa_display_question_metas', $this, 'asqa_display_question_metas', 10, 2 );
		smartqa()->add_action( 'asqa_question_info', $this, 'asqa_question_info' );
		smartqa()->add_action( 'asqa_enqueue', $this, 'asqa_assets_js' );
		smartqa()->add_action( 'asqa_enqueue', $this, 'asqa_localize_scripts' );
		smartqa()->add_filter( 'term_link', $this, 'term_link_filter', 10, 3 );
		smartqa()->add_action( 'asqa_question_form_fields', $this, 'asqa_question_form_fields' );
		smartqa()->add_action( 'asqa_processed_new_question', $this, 'after_new_question', 0, 2 );
		smartqa()->add_action( 'asqa_processed_update_question', $this, 'after_new_question', 0, 2 );
		smartqa()->add_filter( 'asqa_page_title', $this, 'page_title' );
		smartqa()->add_filter( 'asqa_breadcrumbs', $this, 'asqa_breadcrumbs' );
		smartqa()->add_action( 'wp_ajax_asqa_tags_suggestion', $this, 'asqa_tags_suggestion' );
		smartqa()->add_action( 'wp_ajax_nopriv_asqa_tags_suggestion', $this, 'asqa_tags_suggestion' );
		smartqa()->add_action( 'asqa_rewrites', $this, 'rewrite_rules', 10, 3 );
		smartqa()->add_filter( 'asqa_main_questions_args', $this, 'asqa_main_questions_args' );
		smartqa()->add_filter( 'asqa_category_questions_args', $this, 'asqa_main_questions_args' );
		smartqa()->add_filter( 'asqa_current_page', $this, 'asqa_current_page' );
		smartqa()->add_action( 'posts_pre_query', $this, 'modify_query_archive', 9999, 2 );

		// List filtering.
		smartqa()->add_filter( 'asqa_list_filters', $this, 'asqa_list_filters' );
		smartqa()->add_action( 'asqa_ajax_load_filter_qtag', $this, 'load_filter_tag' );
		smartqa()->add_action( 'asqa_ajax_load_filter_tags_order', $this, 'load_filter_tags_order' );
		smartqa()->add_filter( 'asqa_list_filter_active_qtag', $this, 'filter_active_tag', 10, 2 );
		smartqa()->add_filter( 'asqa_list_filter_active_tags_order', $this, 'filter_active_tags_order', 10, 2 );
	}

	/**
	 * Tag page layout.
	 *
	 * @since 1.0.0 Use `get_queried_object()` to get current term.
	 */
	public function tag_page() {
		global $question_tag;
		$question_tag = get_queried_object();

		$question_args = array(
			'paged'     => max( 1, get_query_var( 'asqa_paged' ) ),
			'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'question_tag',
					'field'    => 'id',
					'terms'    => array( $question_tag->term_id ),
				),
			),
		);

		$question_args = apply_filters( 'asqa_tag_question_query_args', $question_args );

		if ( $question_tag ) {
			smartqa()->questions = asqa_get_questions( $question_args );
			include asqa_get_theme_location( 'addons/tag/tag.php' );
		}
	}

	/**
	 * Tags page layout
	 */
	public function tags_page() {
		global $question_tags, $asqa_max_num_pages, $asqa_per_page, $tags_rows_found;
		$paged    = max( 1, get_query_var( 'paged' ) );
		$per_page = (int) asqa_opt( 'tags_per_page' );
		$per_page = 0 === $per_page ? 1 : $per_page;
		$offset   = $per_page * ( $paged - 1 );

		$tag_args = array(
			'taxonomy'      => 'question_tag',
			'asqa_tags_query' => true,
			'parent'        => 0,
			'number'        => $per_page,
			'offset'        => $offset,
			'hide_empty'    => false,
			'order'         => 'DESC',
		);

		$asqa_sort = asqa_isset_post_value( 'tags_order', 'count' );

		if ( 'new' === $asqa_sort ) {
			$tag_args['orderby'] = 'id';
			$tag_args['order']   = 'DESC';
		} elseif ( 'name' === $asqa_sort ) {
			$tag_args['orderby'] = 'name';
			$tag_args['order']   = 'ASC';
		} else {
			$tag_args['orderby'] = 'count';
		}

		if ( asqa_isset_post_value( 'asqa_s' ) ) {
			$tag_args['search'] = asqa_sanitize_unslash( 'asqa_s', 'r' );
		}

		/**
		 * Filter applied before getting tags.
		 *
		 * @var array
		 */
		$tag_args = apply_filters( 'asqa_tags_shortcode_args', $tag_args );

		$query = new \WP_Term_Query( $tag_args );

		// Count terms.
		$tag_args['fields'] = 'count';
		$found_query        = new \WP_Term_Query( $tag_args );
		$tags_rows_found    = $found_query->get_terms();
		$asqa_max_num_pages   = ceil( count( $tags_rows_found ) / $per_page );
		$question_tags      = $query->get_terms();

		include asqa_get_theme_location( 'addons/tag/tags.php' );
	}

	/**
	 * Register widget position.
	 */
	public function widget_positions() {
		register_sidebar(
			array(
				'name'          => __( '(SmartQa) Tags', 'smart-question-answer' ),
				'id'            => 'asqa-tags',
				'before_widget' => '<div id="%1$s" class="asqa-widget-pos %2$s">',
				'after_widget'  => '</div>',
				'description'   => __( 'Widgets in this area will be shown in smartqa tags page.', 'smart-question-answer' ),
				'before_title'  => '<h3 class="asqa-widget-title">',
				'after_title'   => '</h3>',
			)
		);
	}

	/**
	 * Register tag taxonomy for question cpt.
	 *
	 * @return void
	 * @since 2.0
	 */
	public function register_question_tag() {
		asqa_add_default_options(
			array(
				'max_tags'      => 5,
				'min_tags'      => 1,
				'tags_per_page' => 20,
				'tag_page_slug' => 'tag',
			)
		);

		$tag_labels = array(
			'name'               => __( 'Question Tags', 'smart-question-answer' ),
			'singular_name'      => __( 'Tag', 'smart-question-answer' ),
			'all_items'          => __( 'All Tags', 'smart-question-answer' ),
			'add_new_item'       => __( 'Add New Tag', 'smart-question-answer' ),
			'edit_item'          => __( 'Edit Tag', 'smart-question-answer' ),
			'new_item'           => __( 'New Tag', 'smart-question-answer' ),
			'view_item'          => __( 'View Tag', 'smart-question-answer' ),
			'search_items'       => __( 'Search Tag', 'smart-question-answer' ),
			'not_found'          => __( 'Nothing Found', 'smart-question-answer' ),
			'not_found_in_trash' => __( 'Nothing found in Trash', 'smart-question-answer' ),
			'parent_item_colon'  => '',
		);

		/**
		 * FILTER: asqa_question_tag_labels
		 * Filter ic called before registering question_tag taxonomy
		 */
		$tag_labels = apply_filters( 'asqa_question_tag_labels', $tag_labels );
		$tag_args   = array(
			'hierarchical' => true,
			'labels'       => $tag_labels,
			'rewrite'      => false,
		);

		/**
		 * FILTER: asqa_question_tag_args
		 * Filter ic called before registering question_tag taxonomy
		 */
		$tag_args = apply_filters( 'asqa_question_tag_args', $tag_args );

		/**
		 * Now let WordPress know about our taxonomy
		 */
		register_taxonomy( 'question_tag', array( 'question' ), $tag_args );
	}

	/**
	 * Add tags menu in wp-admin.
	 */
	public function admin_tags_menu() {
		add_submenu_page( 'smartqa', __( 'Question Tags', 'smart-question-answer' ), __( 'Tags', 'smart-question-answer' ), 'manage_options', 'edit-tags.php?taxonomy=question_tag' );
	}

	/**
	 * Add tags settings to features settings page.
	 *
	 * @param array $groups Features settings group.
	 * @return array
	 * @since 1.0.0
	 */
	public function add_to_settings_page( $groups ) {
		$groups['tag'] = array(
			'label' => __( 'Tag', 'smart-question-answer' ),
		);

		return $groups;
	}

	/**
	 * Register option fields.
	 */
	public function option_fields() {
		$opt = asqa_opt();

		$form = array(
			'fields' => array(
				'tags_per_page' => array(
					'label'       => __( 'Tags to show', 'smart-question-answer' ),
					'description' => __( 'Numbers of tags to show in tags page.', 'smart-question-answer' ),
					'subtype'     => 'number',
					'value'       => $opt['tags_per_page'],
				),
				'max_tags'      => array(
					'label'       => __( 'Maximum tags', 'smart-question-answer' ),
					'description' => __( 'Maximum numbers of tags that user can add when asking.', 'smart-question-answer' ),
					'subtype'     => 'number',
					'value'       => $opt['max_tags'],
				),
				'min_tags'      => array(
					'label'       => __( 'Minimum tags', 'smart-question-answer' ),
					'description' => __( 'minimum numbers of tags that user must add when asking.', 'smart-question-answer' ),
					'subtype'     => 'number',
					'value'       => $opt['min_tags'],
				),
				'tag_page_slug' => array(
					'label' => __( 'Tag page slug', 'smart-question-answer' ),
					'desc'  => __( 'Slug for tag page', 'smart-question-answer' ),
					'value' => $opt['tag_page_slug'],
				),
			),
		);

		return $form;
	}


	/**
	 * Append meta display.
	 *
	 * @param  array $metas Display metas.
	 * @param  array $question_id Post ID.
	 * @return array
	 * @since 2.0
	 */
	public function asqa_display_question_metas( $metas, $question_id ) {
		if ( asqa_post_have_terms( $question_id, 'question_tag' ) ) {
			$metas['tags'] = asqa_question_tags_html(
				array(
					'label' => '<i class="apicon-tag"></i>',
					'show'  => 1,
				)
			); }

		return $metas;
	}

	/**
	 * Hook tags after post.
	 *
	 * @param object $post Post object.
	 * @since 1.0
	 */
	public function asqa_question_info( $post ) {
		if ( asqa_question_have_tags() ) {
			echo wp_kses_post( '<div class="widget"><span class="asqa-widget-title">' . esc_attr__( 'Tags', 'smart-question-answer' ) . '</span>' );

			echo wp_kses_post(
				'<div class="asqa-post-tags clearfix">' .
				asqa_question_tags_html(
					array(
						'list'  => true,
						'label' => '',
					)
				) .
				'</div></div>'
			);
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function asqa_assets_js() {
		wp_enqueue_script( 'smartqa-tags', SMARTQA_URL . 'assets/js/tags.js', array( 'smartqa-list' ), ASQA_VERSION, true );
	}

	/**
	 * Add translated strings to the javascript files
	 */
	public function asqa_localize_scripts() {
		$l10n_data = array(
			'deleteTag'            => __( 'Delete Tag', 'smart-question-answer' ),
			'addTag'               => __( 'Add Tag', 'smart-question-answer' ),
			'tagAdded'             => __( 'added to the tags list.', 'smart-question-answer' ),
			'tagRemoved'           => __( 'removed from the tags list.', 'smart-question-answer' ),
			'suggestionsAvailable' => __( 'Suggestions are available. Use the up and down arrow keys to read it.', 'smart-question-answer' ),
		);

		wp_localize_script(
			'smartqa-tags',
			'apTagsTranslation',
			$l10n_data
		);
	}

	/**
	 * Filter tag term link.
	 *
	 * @param  string $url      Default URL of taxonomy.
	 * @param  object $term     Term array.
	 * @param  string $taxonomy Taxonomy type.
	 * @return string           New URL for term.
	 */
	public function term_link_filter( $url, $term, $taxonomy ) {
		if ( 'question_tag' === $taxonomy ) {
			if ( get_option( 'permalink_structure' ) !== '' ) {
				$opt = get_option( 'asqa_tags_path', 'tags' );
				return user_trailingslashit( home_url( $opt ) . '/' . $term->slug );
			} else {
				return add_query_arg(
					array(
						'asqa_page'      => 'tag',
						'question_tag' => $term->slug,
					),
					home_url()
				);
			}
		}
		return $url;
	}

	/**
	 * Add tag field in question form.
	 *
	 * @param array $form SmartQa form arguments.
	 * @since 1.0.0
	 */
	public function asqa_question_form_fields( $form ) {
		$editing_id = asqa_sanitize_unslash( 'id', 'r' );

		$form['fields']['tags'] = array(
			'label'      => __( 'Tags', 'smart-question-answer' ),
			'desc'       => sprintf(
				// Translators: %1$d contain minimum tags required and %2$d contain maximum tags allowed.
				__( 'Tagging will helps others to easily find your question. Minimum %1$d and maximum %2$d tags.', 'smart-question-answer' ),
				asqa_opt( 'min_tags' ),
				asqa_opt( 'max_tags' )
			),
			'type'       => 'tags',
			'array_max'  => asqa_opt( 'max_tags' ),
			'array_min'  => asqa_opt( 'min_tags' ),
			'js_options' => array(
				'create' => true,
			),
		);

		// Add value when editing post.
		if ( ! empty( $editing_id ) ) {
			$tags = get_the_terms( $editing_id, 'question_tag' );
			if ( $tags ) {
				$tags                            = wp_list_pluck( $tags, 'term_id' );
				$form['fields']['tags']['value'] = $tags;
			}
		}

		return $form;
	}

	/**
	 * Things to do after creating a question.
	 *
	 * @param  integer $post_id Post ID.
	 * @param  object  $post Post object.
	 * @since 1.0
	 */
	public function after_new_question( $post_id, $post ) {
		$values = smartqa()->get_form( 'question' )->get_values();

		if ( isset( $values['tags'], $values['tags']['value'] ) ) {
			wp_set_object_terms( $post_id, $values['tags']['value'], 'question_tag' );
		}
	}

	/**
	 * Tags page title.
	 *
	 * @param  string $title Title.
	 * @return string
	 */
	public function page_title( $title ) {
		if ( is_question_tags() ) {
			$title = asqa_opt( 'tags_page_title' );
		} elseif ( is_question_tag() ) {
			$tag_id = sanitize_title( get_query_var( 'q_tag' ) );
			$tag = get_term_by( 'slug', $tag_id, 'question_tag' ); // @codingStandardsIgnoreLine.
			$title  = $tag->name;
		}

		return $title;
	}

	/**
	 * Hook into SmartQa breadcrums to show tags page.
	 *
	 * @param  array $navs Breadcrumbs navs.
	 * @return array
	 */
	public function asqa_breadcrumbs( $navs ) {
		if ( is_question_tag() ) {
			$tag_id       = sanitize_title( get_query_var( 'q_tag' ) );
			$tag = get_term_by( 'slug', $tag_id, 'question_tag' ); // @codingStandardsIgnoreLine.
			$navs['page'] = array(
				'title' => __( 'Tags', 'smart-question-answer' ),
				'link'  => asqa_get_link_to( 'tags' ),
				'order' => 8,
			);

			if ( $tag ) {
				$navs['tag'] = array(
					'title' => $tag->name,
					'link'  => get_term_link( $tag, 'question_tag' ), // @codingStandardsIgnoreLine.
					'order' => 8,
				);
			}
		} elseif ( is_question_tags() ) {
			$navs['page'] = array(
				'title' => __( 'Tags', 'smart-question-answer' ),
				'link'  => asqa_get_link_to( 'tags' ),
				'order' => 8,
			);
		}

		return $navs;
	}

	/**
	 * Handle tags suggestion on question form
	 */
	public function asqa_tags_suggestion() {
		$keyword = asqa_sanitize_unslash( 'q', 'r' );

		$tags = get_terms(
			'question_tag',
			array(
				'orderby'    => 'count',
				'order'      => 'DESC',
				'hide_empty' => false,
				'search'     => $keyword,
				'number'     => 8,
			)
		);

		if ( $tags ) {
			$items = array();
			foreach ( $tags as $k => $t ) {
				$items [ $k ] = $t->slug;
			}

			$result = array(
				'status' => true,
				'items'  => $items,
			);
			wp_send_json( $result );
		}

		wp_send_json( array( 'status' => false ) );
	}

	/**
	 * Add category pages rewrite rule.
	 *
	 * @param array  $rules SmartQa rules.
	 * @param string $slug Slug.
	 * @param int    $base_page_id SmartQa base page id.
	 * @return array
	 */
	public function rewrite_rules( $rules, $slug, $base_page_id ) {
		$base_slug = get_page_uri( asqa_opt( 'tags_page' ) );
		update_option( 'asqa_tags_path', $base_slug, true );

		$cat_rules = array(
			$base_slug . '/([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?question_tag=$matches[#]&asqa_paged=$matches[#]&asqa_page=tag',
			$base_slug . '/([^/]+)/?$'                   => 'index.php?question_tag=$matches[#]&asqa_page=tag',
		);

		return $cat_rules + $rules;
	}

	/**
	 * Filter main questions query args. Modify and add tags args.
	 *
	 * @param  array $args Questions args.
	 * @return array
	 */
	public function asqa_main_questions_args( $args ) {
		global $wp;
		$query = $wp->query_vars;

		$current_filter = asqa_get_current_list_filters( 'qtag' );
		$tags_operator  = ! empty( $wp->query_vars['asqa_tags_operator'] ) ? $wp->query_vars['asqa_tags_operator'] : 'IN';

		if ( isset( $query['asqa_tags'] ) && is_array( $query['asqa_tags'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'question_tag',
				'field'    => 'slug',
				'terms'    => $query['asqa_tags'],
				'operator' => $tags_operator,
			);
		} elseif ( ! empty( $current_filter ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'question_tag',
				'field'    => 'term_id',
				'terms'    => $current_filter,
				'operator' => 'IN',
			);
		}

		return $args;
	}

	/**
	 * Add tags sorting in list filters
	 *
	 * @param array $filters Filters.
	 * @return array
	 */
	public function asqa_list_filters( $filters ) {
		global $wp;

		if ( ! isset( $wp->query_vars['asqa_tags'] ) ) {
			$filters['qtag'] = array(
				'title'    => __( 'Tag', 'smart-question-answer' ),
				'search'   => true,
				'multiple' => true,
			);
		}

		if ( 'tags' === asqa_current_page() ) {
			return array(
				'tags_order' => array(
					'title' => __( 'Order', 'smart-question-answer' ),
				),
			);
		}

		return $filters;
	}

	/**
	 * Ajax callback for loading order by filter.
	 *
	 * @since 4.0.0
	 */
	public function load_filter_tag() {
		$filter = asqa_sanitize_unslash( 'filter', 'r' );
		check_ajax_referer( 'filter_' . $filter, '__nonce' );
		$search = asqa_sanitize_unslash( 'search', 'r', false );

		asqa_ajax_json(
			array(
				'success'  => true,
				'items'    => asqa_get_tag_filter( $search ),
				'multiple' => true,
				'nonce'    => wp_create_nonce( 'filter_' . $filter ),
			)
		);
	}

	/**
	 * Ajax callback for loading order by filter for tags.
	 *
	 * @since 4.0.0
	 */
	public function load_filter_tags_order() {
		$filter = asqa_sanitize_unslash( 'filter', 'r' );
		check_ajax_referer( 'filter_' . $filter, '__nonce' );

		asqa_ajax_json(
			array(
				'success' => true,
				'items'   => array(
					array(
						'key'   => 'tags_order',
						'value' => 'popular',
						'label' => __( 'Popular', 'smart-question-answer' ),
					),
					array(
						'key'   => 'tags_order',
						'value' => 'new',
						'label' => __( 'New', 'smart-question-answer' ),
					),
					array(
						'key'   => 'tags_order',
						'value' => 'name',
						'label' => __( 'Name', 'smart-question-answer' ),
					),
				),
				'nonce'   => wp_create_nonce( 'filter_' . $filter ),
			)
		);
	}

	/**
	 * Output active tag in filter
	 *
	 * @param bool  $active Is active.
	 * @param mixed $filter Current filter.
	 * @since 4.0.0
	 */
	public function filter_active_tag( $active, $filter ) {
		$current_filters = asqa_get_current_list_filters( 'qtag' );

		if ( ! empty( $current_filters ) ) {
			$args = array(
				'hierarchical'  => true,
				'hide_if_empty' => true,
				'number'        => 2,
				'include'       => $current_filters,
			);

			$terms = get_terms( 'question_tag', $args );

			if ( $terms ) {
				$active_terms = array();
				foreach ( (array) $terms as $t ) {
					$active_terms[] = $t->name;
				}

				$count = count( $current_filters );

				// translators: Placeholder contains count.
				$more_label = sprintf( __( ', %d+', 'smart-question-answer' ), $count - 2 );

				return ': <span class="asqa-filter-active">' . implode( ', ', $active_terms ) . ( $count > 2 ? $more_label : '' ) . '</span>';
			}
		}
	}

	/**
	 * Output active tags_order in filter
	 *
	 * @param string $active Active filter.
	 * @param array  $filter Filter.
	 * @since 1.0.0
	 */
	public function filter_active_tags_order( $active, $filter ) {
		$tags_order = asqa_get_current_list_filters( 'tags_order' );
		$tags_order = ! empty( $tags_order ) ? $tags_order : 'popular';

		$orders = array(
			'popular' => __( 'Popular', 'smart-question-answer' ),
			'new'     => __( 'New', 'smart-question-answer' ),
			'name'    => __( 'Name', 'smart-question-answer' ),
		);

		$active = isset( $orders[ $tags_order ] ) ? $orders[ $tags_order ] : '';

		return ': <span class="asqa-filter-active">' . $active . '</span>';
	}

	/**
	 * Modify current page to show tag archive.
	 *
	 * @param string $query_var Current page.
	 * @return string
	 * @since 1.0.0
	 */
	public function asqa_current_page( $query_var ) {
		if ( 'tags' === $query_var && 'tag' === get_query_var( 'asqa_page' ) ) {
			return 'tag';
		}

		return $query_var;
	}

	/**
	 * Modify main query to show tag archive.
	 *
	 * @param array|null $posts Array of objects.
	 * @param object     $query Wp_Query object.
	 *
	 * @return array|null
	 * @since 1.0.0
	 */
	public function modify_query_archive( $posts, $query ) {
		if ( $query->is_main_query() &&
			$query->is_tax( 'question_tag' ) &&
			'tag' === get_query_var( 'asqa_page' ) ) {
			$query->found_posts   = 1;
			$query->max_num_pages = 1;
			$page                 = get_page( asqa_opt( 'tags_page' ) );
			$page->post_title     = get_queried_object()->name;
			$posts                = array( $page );
		}

		return $posts;
	}
}


// Init addons.
Tags::init();
