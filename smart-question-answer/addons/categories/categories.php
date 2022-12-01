<?php
/**
 * Add category support in SmartQa questions.
 *
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 * @copyright  2014 extensionforge.com & Peter Mertzlin
 * @license    GPL-3.0+ https://www.gnu.org/licenses/gpl-3.0.txt
 * @link       https://extensionforge.com
 * @package    SmartQa
 * @subpackage Categories Addon
 * @since      1.0.0
 * @since      1.0.0 Moved addon settings to settings page.
 */

namespace SmartQa\Addons;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Categories addon class.
 */
class Categories extends \SmartQa\Singleton {

	/**
	 * Refers to a single instance of this class.
	 *
	 * @var null|object
	 * @since 1.0.0
	 */
	public static $instance = null;

	/**
	 * Initialize the class.
	 *
	 * @since 4.0.0
	 * @since 1.0.0 Replaced action `asqa_form_addon-categories` by `asqa_all_options`.
	 */
	protected function __construct() {
		asqa_register_page( 'category', __( 'Category', 'smart-question-answer' ), array( $this, 'category_page' ), false );
		asqa_register_page( 'categories', __( 'Categories', 'smart-question-answer' ), array( $this, 'categories_page' ) );

		smartqa()->add_action( 'init', $this, 'register_question_categories', 1 );
		smartqa()->add_action( 'asqa_settings_menu_features_groups', $this, 'load_options' );
		smartqa()->add_filter( 'asqa_form_options_features_category', $this, 'register_general_settings_form' );
		smartqa()->add_action( 'admin_enqueue_scripts', $this, 'admin_enqueue_scripts' );
		smartqa()->add_action( 'asqa_load_admin_assets', $this, 'asqa_load_admin_assets' );
		smartqa()->add_action( 'asqa_admin_menu', $this, 'admin_category_menu' );
		smartqa()->add_action( 'asqa_display_question_metas', $this, 'asqa_display_question_metas', 10, 2 );
		smartqa()->add_action( 'asqa_enqueue', $this, 'asqa_assets_js' );
		smartqa()->add_filter( 'term_link', $this, 'term_link_filter', 10, 3 );
		smartqa()->add_action( 'asqa_question_form_fields', $this, 'asqa_question_form_fields' );
		smartqa()->add_action( 'save_post_question', $this, 'after_new_question', 0, 2 );
		smartqa()->add_filter( 'asqa_breadcrumbs', $this, 'asqa_breadcrumbs' );
		smartqa()->add_action( 'terms_clauses', $this, 'terms_clauses', 10, 3 );
		smartqa()->add_filter( 'asqa_list_filters', $this, 'asqa_list_filters' );
		smartqa()->add_action( 'question_category_add_form_fields', $this, 'image_field_new' );
		smartqa()->add_action( 'question_category_edit_form_fields', $this, 'image_field_edit' );
		smartqa()->add_action( 'create_question_category', $this, 'create_save_image_field' );
		smartqa()->add_action( 'edited_question_category', $this, 'save_image_field' );
		smartqa()->add_action( 'asqa_rewrites', $this, 'rewrite_rules', 10, 3 );
		smartqa()->add_filter( 'asqa_main_questions_args', $this, 'asqa_main_questions_args' );
		smartqa()->add_filter( 'asqa_question_subscribers_action_id', $this, 'subscribers_action_id' );
		smartqa()->add_filter( 'asqa_ask_btn_link', $this, 'asqa_ask_btn_link' );
		smartqa()->add_filter( 'wp_head', $this, 'category_feed' );
		smartqa()->add_filter( 'manage_edit-question_category_columns', $this, 'column_header' );
		smartqa()->add_filter( 'manage_question_category_custom_column', $this, 'column_content', 10, 3 );
		smartqa()->add_filter( 'asqa_current_page', $this, 'asqa_current_page' );
		smartqa()->add_action( 'posts_pre_query', $this, 'modify_query_category_archive', 9999, 2 );

		// List filtering.
		smartqa()->add_action( 'asqa_ajax_load_filter_category', $this, 'load_filter_category' );
		smartqa()->add_filter( 'asqa_list_filter_active_category', $this, 'filter_active_category', 10, 2 );

		smartqa()->add_action( 'widgets_init', $this, 'widget' );
	}

	/**
	 * Category page layout.
	 *
	 * @since 1.0.0 Use `get_queried_object()` to get current term.
	 * @since 1.0.0 Added new filter `asqa_category_questions_args`.
	 */
	public function category_page() {
		$question_args = array(
			'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'question_category',
					'field'    => 'id',
					'terms'    => array( get_queried_object_id() ),
				),
			),
		);

		$question_category = get_queried_object();

		if ( $question_category ) {

			/**
			 * Filter category page question list query arguments.
			 *
			 * @param array $args Wp_Query arguments.
			 * @since 1.0.0
			 */
			$question_args = apply_filters( 'asqa_category_questions_args', $question_args );

			smartqa()->questions = asqa_get_questions( $question_args );

			/**
			 * This action can be used to show custom message before category page.
			 *
			 * @param object $question_category Current question category.
			 * @since 1.4.2
			 */
			do_action( 'asqa_before_category_page', $question_category );

			include asqa_get_theme_location( 'addons/category/single-category.php' );
		}
	}

	/**
	 * Categories page layout
	 */
	public function categories_page() {
		global $question_categories, $ap_max_num_pages, $ap_per_page;
		global $wpdb;
		global $category_mods;
    	$users = $wpdb->prefix . 'users';
    	$usersid = $wpdb->prefix . 'users.ID';
    	$capabilities = $wpdb->prefix . 'capabilities';
    	$usermeta = $wpdb->prefix . 'usermeta';
    	$usermetauserid = $wpdb->prefix . 'usermeta.user_id';
    	$usermetakey = $wpdb->prefix . 'usermeta.meta_key';
    	$usermetavalue = $wpdb->prefix . 'usermeta.meta_value';
    
		$category_mods = $wpdb->get_results( "SELECT * FROM $users left join $usermeta on $usersid = $usermetauserid where $usermetakey = '$capabilities' and $usermetavalue like '%asqa_%'"); 

		$paged            = max( 1, get_query_var( 'paged' ) );
		$per_page         = asqa_opt( 'categories_per_page' );
		$total_terms      = wp_count_terms(
			'question_category',
			array(
				'hide_empty' => false,
				'parent'     => 0,
			)
		);
		$offset           = $per_page * ( $paged - 1 );
		$asqa_max_num_pages = ceil( $total_terms / $per_page );

		$order = asqa_opt( 'categories_page_order' ) === 'ASC' ? 'ASC' : 'DESC';

		$cat_args = array(
			'parent'     => 0,
			'number'     => $per_page,
			'offset'     => $offset,
			'hide_empty' => false,
			'orderby'    => asqa_opt( 'categories_page_orderby' ),
			'order'      => $order,
		);

		/**
		 * Filter applied before getting categories.
		 *
		 * @param array $cat_args `get_terms` arguments.
		 * @since 1.0
		 */
		$cat_args = apply_filters( 'asqa_categories_shortcode_args', $cat_args );

		$question_categories = get_terms( 'question_category', $cat_args );
		include asqa_get_theme_location( 'addons/category/categories.php' );
	}

	/**
	 * Register category taxonomy for question cpt.
	 *
	 * @return void
	 * @since 2.0
	 */
	public function register_question_categories() {
		asqa_add_default_options(
			array(
				'form_category_orderby'   => 'count',
				'categories_page_order'   => 'DESC',
				'categories_page_orderby' => 'count',
				'category_page_slug'      => 'category',
				'categories_per_page'     => 20,
				'categories_image_height' => 150,
			)
		);

		/**
		 * Labels for category taxonomy.
		 *
		 * @var array
		 */
		$categories_labels = array(
			'name'               => __( 'Question Categories', 'smart-question-answer' ),
			'singular_name'      => __( 'Category', 'smart-question-answer' ),
			'all_items'          => __( 'All Categories', 'smart-question-answer' ),
			'add_new_item'       => __( 'Add New Category', 'smart-question-answer' ),
			'edit_item'          => __( 'Edit Category', 'smart-question-answer' ),
			'new_item'           => __( 'New Category', 'smart-question-answer' ),
			'view_item'          => __( 'View Category', 'smart-question-answer' ),
			'search_items'       => __( 'Search Category', 'smart-question-answer' ),
			'not_found'          => __( 'Nothing Found', 'smart-question-answer' ),
			'not_found_in_trash' => __( 'Nothing found in Trash', 'smart-question-answer' ),
			'parent_item_colon'  => '',
		);

		/**
		 * FILTER: asqa_question_category_labels
		 * Filter ic called before registering question_category taxonomy
		 */
		$categories_labels = apply_filters( 'asqa_question_category_labels', $categories_labels );

		/**
		 * Arguments for category taxonomy
		 *
		 * @var array
		 * @since 2.0
		 */
		$category_args = array(
			'hierarchical'       => true,
			'labels'             => $categories_labels,
			'rewrite'            => false,
			'publicly_queryable' => true,
		);

		/**
		 * Filter is called before registering question_category taxonomy.
		 *
		 * @param array $category_args Category arguments.
		 */
		$category_args = apply_filters( 'asqa_question_category_args', $category_args );

		/**
		 * Now let WordPress know about our taxonomy
		 */
		register_taxonomy( 'question_category', array( 'question' ), $category_args );
	}

	/**
	 * Register Categories options.
	 *
	 * @param array $options Options.
	 * @since unknown
	 * @since 1.0.0 Moved form registration to another method `register_settings_from`.
	 */
	public function load_options( $options ) {
		$options['category'] = array(
			'label' => __( 'Category', 'smart-question-answer' ),
		);

		return $options;
	}

	/**
	 * Register category general settings.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function register_general_settings_form() {
		$opt = asqa_opt();

		return array(
			'fields' => array(
				'categories_page_info'    => array(
					'html' => '<label class="asqa-form-label" for="form_options_category_general-categories_page_info">' . __( 'Categories base page', 'smart-question-answer' ) . '</label>' . __( 'Base page for categories can be configured in general settings of SmartQa.', 'smart-question-answer' ),
				),
				'form_category_orderby'   => array(
					'label'       => __( 'Ask form category order', 'smart-question-answer' ),
					'description' => __( 'Set how you want to order categories in form.', 'smart-question-answer' ),
					'type'        => 'select',
					'options'     => array(
						'ID'         => __( 'ID', 'smart-question-answer' ),
						'name'       => __( 'Name', 'smart-question-answer' ),
						'slug'       => __( 'Slug', 'smart-question-answer' ),
						'count'      => __( 'Count', 'smart-question-answer' ),
						'term_group' => __( 'Group', 'smart-question-answer' ),
					),
					'value'       => $opt['form_category_orderby'],
				),
				'categories_page_orderby' => array(
					'label'       => __( 'Categories page order by', 'smart-question-answer' ),
					'description' => __( 'Set how you want to order categories in categories page.', 'smart-question-answer' ),
					'type'        => 'select',
					'options'     => array(
						'ID'         => __( 'ID', 'smart-question-answer' ),
						'name'       => __( 'Name', 'smart-question-answer' ),
						'slug'       => __( 'Slug', 'smart-question-answer' ),
						'count'      => __( 'Count', 'smart-question-answer' ),
						'term_group' => __( 'Group', 'smart-question-answer' ),
					),
					'value'       => $opt['categories_page_orderby'],
				),
				'categories_page_order'   => array(
					'label'       => __( 'Categories page order', 'smart-question-answer' ),
					'description' => __( 'Set how you want to order categories in categories page.', 'smart-question-answer' ),
					'type'        => 'select',
					'options'     => array(
						'ASC'  => __( 'Ascending', 'smart-question-answer' ),
						'DESC' => __( 'Descending', 'smart-question-answer' ),
					),
					'value'       => $opt['categories_page_order'],
				),
				'categories_per_page'     => array(
					'label'   => __( 'Category per page', 'smart-question-answer' ),
					'desc'    => __( 'Category to show per page', 'smart-question-answer' ),
					'subtype' => 'number',
					'value'   => $opt['categories_per_page'],
				),
				'categories_image_height' => array(
					'label'   => __( 'Categories image height', 'smart-question-answer' ),
					'desc'    => __( 'Image height in categories page', 'smart-question-answer' ),
					'subtype' => 'number',
					'value'   => $opt['categories_image_height'],
				),
			),
		);
	}

	/**
	 * Enqueue required script
	 */
	public function admin_enqueue_scripts() {
		if ( ! asqa_load_admin_assets() ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/**
	 * Load admin assets in categories page.
	 *
	 * @param boolean $return Return.
	 * @return boolean
	 */
	public function asqa_load_admin_assets( $return ) {
		$page = get_current_screen();
		if ( 'question_category' === $page->taxonomy ) {
			return true;
		}

		return $return;
	}

	/**
	 * Add category menu in wp-admin.
	 *
	 * @since 2.0
	 * @since 1.0.0 Renamed menu from "Category".
	 */
	public function admin_category_menu() {
		add_submenu_page( 'smartqa', __( 'Question Categories', 'smart-question-answer' ), __( 'Question Categories', 'smart-question-answer' ), 'manage_options', 'edit-tags.php?taxonomy=question_category' );
	}

	/**
	 * Append meta display.
	 *
	 * @param   array   $metas Display meta items.
	 * @param   integer $question_id  Question id.
	 * @return  array
	 * @since   1.0
	 */
	public function asqa_display_question_metas( $metas, $question_id ) {
		if ( asqa_post_have_terms( $question_id ) ) {
			$metas['categories'] = asqa_question_categories_html( array( 'label' => '<i class="apicon-category"></i>' ) );
		}

		return $metas;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param array $js JavaScripts.
	 * @since 1.0
	 */
	public function asqa_assets_js( $js ) {
		if ( asqa_current_page() === 'category' ) {
			wp_enqueue_script( 'smartqa-theme' );
		}
	}

	/**
	 * Filter category permalink.
	 *
	 * @param  string $url      Default taxonomy url.
	 * @param  object $term     WordPress term object.
	 * @param  string $taxonomy Current taxonomy slug.
	 * @return string
	 */
	public function term_link_filter( $url, $term, $taxonomy ) {
		if ( 'question_category' === $taxonomy ) {
			if ( get_option( 'permalink_structure' ) !== '' ) {
				$opt = get_option( 'asqa_categories_path', 'categories' );
				return home_url( $opt ) . '/' . $term->slug . '/';
			} else {
				return add_query_arg(
					array(
						'asqa_page'           => 'category',
						'question_category' => $term->slug,
					),
					home_url()
				);
			}
		}

		return $url;
	}

	/**
	 * Add category field in ask form.
	 *
	 * @param   array $form Ask form arguments.
	 * @return  array
	 * @since   1.0.0
	 */
	public function asqa_question_form_fields( $form ) {
		if ( wp_count_terms( 'question_category' ) == 0 ) { // phpcs:ignore
			return $form;
		}

		$editing_id  = asqa_sanitize_unslash( 'id', 'r' );
		$category_id = asqa_sanitize_unslash( 'category', 'r' );

		$form['fields']['category'] = array(
			'label'    => __( 'Category', 'smart-question-answer' ),
			'desc'     => __( 'Select a topic that best fits your question.', 'smart-question-answer' ),
			'type'     => 'select',
			'options'  => 'terms',
			'order'    => 2,
			'validate' => 'required,not_zero',
		);

		// Add value when editing post.
		if ( ! empty( $editing_id ) ) {
			$categories = get_the_terms( $editing_id, 'question_category' );

			if ( $categories ) {
				$form['fields']['category']['value'] = $categories[0]->term_id;
			}
		} elseif ( ! empty( $category_id ) ) {
			$form['fields']['category']['value'] = (int) $category_id;
		}

		return $form;
	}

	/**
	 * Things to do after creating a question.
	 *
	 * @param   integer $post_id    Questions ID.
	 * @param   object  $post       Question post object.
	 * @return  void
	 * @since   1.0
	 */
	public function after_new_question( $post_id, $post ) {
		$values = smartqa()->get_form( 'question' )->get_values();

		if ( isset( $values['category']['value'] ) ) {
			wp_set_post_terms( $post_id, $values['category']['value'], 'question_category' );
		}
	}

	/**
	 * Add category nav in SmartQa breadcrumbs.
	 *
	 * @param  array $navs Breadcrumbs nav array.
	 * @return array
	 */
	public function asqa_breadcrumbs( $navs ) {
		if ( is_question() && taxonomy_exists( 'question_category' ) ) {
			$cats = get_the_terms( get_question_id(), 'question_category' );

			if ( $cats ) {
				$navs['category'] = array( 'title' => $cats[0]->name, 'link' => get_term_link( $cats[0], 'question_category' ), 'order' => 2 ); //@codingStandardsIgnoreLine
			}
		} elseif ( is_question_category() ) {
			$category     = get_queried_object();
			$navs['page'] = array(
				'title' => __( 'Categories', 'smart-question-answer' ),
				'link'  => asqa_get_link_to( 'categories' ),
				'order' => 8,
			);

			$navs['category'] = array(
				'title' => $category->name,
				'link'  => get_term_link( $category, 'question_category' ),
				'order' => 8,
			);
		} elseif ( is_question_categories() ) {
			$navs['page'] = array(
				'title' => __( 'Categories', 'smart-question-answer' ),
				'link'  => asqa_get_link_to( 'categories' ),
				'order' => 8,
			);
		}

		return $navs;
	}

	/**
	 * Modify term clauses.
	 *
	 * @param array $pieces MySql query parts.
	 * @param array $taxonomies Taxonomies.
	 * @param array $args Args.
	 */
	public function terms_clauses( $pieces, $taxonomies, $args ) {
		if ( ! in_array( 'question_category', $taxonomies, true ) || ! isset( $args['asqa_query'] ) || 'subscription' !== $args['asqa_query'] ) {
			return $pieces;
		}

		global $wpdb;

		$pieces['join']  = $pieces['join'] . ' INNER JOIN ' . $wpdb->prefix . 'asqa_meta apmeta ON t.term_id = apmeta.apmeta_actionid';
		$pieces['where'] = $pieces['where'] . " AND apmeta.apmeta_type='subscriber' AND apmeta.apmeta_param='category' AND apmeta.apmeta_userid='" . $args['user_id'] . "'";

		return $pieces;
	}

	/**
	 * Add category sorting in list filters.
	 *
	 * @param array $filters Filters.
	 * @return array
	 */
	public function asqa_list_filters( $filters ) {
		global $wp;

		if ( ! isset( $wp->query_vars['asqa_categories'] ) && ! is_question_category() ) {
			$filters['category'] = array(
				'title'    => __( 'Category', 'smart-question-answer' ),
				'items'    => array(),
				'search'   => true,
				'multiple' => true,
			);
		}

		return $filters;
	}

	/**
	 * Custom question category fields.
	 *
	 * @param  array $term Term.
	 * @return void
	 */
	public function image_field_new( $term ) {
		global $wpdb;
		global $category_mods;
		$users = $wpdb->prefix . 'users';
    	$usersid = $wpdb->prefix . 'users.ID';
    	$capabilities = $wpdb->prefix . 'capabilities';
    	$usermeta = $wpdb->prefix . 'usermeta';
    	$usermetauserid = $wpdb->prefix . 'usermeta.user_id';
    	$usermetakey = $wpdb->prefix . 'usermeta.meta_key';
    	$usermetavalue = $wpdb->prefix . 'usermeta.meta_value';
    	$moderators = $wpdb->prefix . 'asqa_moderators';

		$category_mods = $wpdb->get_results( "SELECT * FROM $users left join $usermeta on $usersid = $usermetauserid where $usermetakey = '$capabilities' and $usermetavalue like '%asqa_moderator%'"); 
		?>
		<div class='form-field form-required term-name-wrap'>
				
					<label for='custom-field'><?php esc_attr_e( 'Moderator', 'smartqa-question-answer' ); ?></label>
			
				<select size="8" name="moderatoren" id="moderatoren" onchange="save_moderatoren()" multiple>
						  <?php 
						  foreach ($category_mods as $mod) {
						  	?><option value="<?php echo $mod->ID; ?>"><?php echo $mod->display_name; ?></option><?php
						  }
						  ?>  
					</select>
<script type="text/javascript" >
function save_moderatoren(){
	 var categoryid = "<?php echo $tcategory_id; ?>";
	 var selected = [];
	 var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
     for (var option of document.getElementById('moderatoren').options)
     {if (option.selected) {selected.push(option.value);}}
  
jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'check_tschaki_ajax',
            selectvalue: selected,
            catid: categoryid          
        },  
       success: function(data) {     
                      //alert(data);
                      //alert('ok');
                      },
                      error: function() {
                        alert('There was some error performing!');
                      }
    });
  }
</script>
						</div>


		<div class='form-field term-image-wrap'>
			<label for='asqa_image'><?php esc_attr_e( 'Image', 'smart-question-answer' ); ?></label>
			<a href="#" id="asqa-category-upload" class="button" data-action="asqa_media_uplaod" data-title="<?php esc_attr_e( 'Upload image', 'smart-question-answer' ); ?>" data-urlc="#asqa_category_media_url" data-idc="#asqa_category_media_id"><?php esc_attr_e( 'Upload image', 'smart-question-answer' ); ?></a>
			<input id="asqa_category_media_url" type="hidden" name="asqa_category_image_url" value="">
			<input id="asqa_category_media_id" type="hidden" name="asqa_category_image_id" value="">

			<p class="description"><?php esc_attr_e( 'Category image', 'smart-question-answer' ); ?></p>
		</div>

		<div class='form-field term-image-wrap'>
			<label for='asqa_icon'><?php esc_attr_e( 'Category icon class', 'smart-question-answer' ); ?></label>
			<input id="asqa_icon" type="text" name="asqa_icon" value="">
			<p class="description"><?php esc_attr_e( 'Font icon class, if image not set', 'smart-question-answer' ); ?></p>
		</div>

		<div class='form-field term-image-wrap'>
			<label for='asqa-category-color'><?php esc_attr_e( 'Icon background color', 'smart-question-answer' ); ?></label>
			<input id="asqa-category-color" type="text" name="asqa_color" value="">
			<p class="description"><?php esc_attr_e( 'Set background color to be used with icon', 'smart-question-answer' ); ?></p>
			<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery('#asqa-category-color').wpColorPicker();
				});
			</script>
		</div>
		<?php
	}

	/**
	 * Image field in category form.
	 *
	 * @param object $term Term.
	 */
	public function image_field_edit( $term ) {
		global $wpdb;
		global $category_mods;
		$users = $wpdb->prefix . 'users';
    	$usersid = $wpdb->prefix . 'users.ID';
    	$capabilities = $wpdb->prefix . 'capabilities';
    	$usermeta = $wpdb->prefix . 'usermeta';
    	$usermetauserid = $wpdb->prefix . 'usermeta.user_id';
    	$usermetakey = $wpdb->prefix . 'usermeta.meta_key';
    	$usermetavalue = $wpdb->prefix . 'usermeta.meta_value';
    	$moderators = $wpdb->prefix . 'asqa_moderators';

		$tcategory_id = intval($_GET['tag_ID']);

		$category_mods = $wpdb->get_results( "SELECT * FROM $users left join $usermeta on $usersid = $usermetauserid where $usermetakey = '$capabilities' and $usermetavalue like '%asqa_moderator%'"); 

		$modsdb = $wpdb->get_results( "SELECT * FROM $moderators where cat_id=".$tcategory_id);

		$term_meta = get_term_meta( $term->term_id, 'asqa_category', true );
		$term_meta = wp_parse_args(
			$term_meta,
			array(
				'image' => array(
					'id'  => '',
					'url' => '',
				),
				'icon'  => '',
				'color' => '',
			)
		);

		?>
		<tr class='form-field form-required term-name-wrap'>
				<th scope='row'>
					<label for='custom-field'><?php esc_attr_e( 'Moderator', 'smartqa-question-answer' ); ?></label>
				</th>
				<td><select size="8" name="moderatoren" id="moderatoren" onchange="save_moderatoren()" multiple>
						  <?php 
						  foreach ($category_mods as $mod) {
						  	?><option value="<?php echo $mod->ID; ?>" <?php $testid2 = intval($mod->ID); 
						  	foreach($modsdb as $moderator){
						  		$testid1 = intval($moderator->user_id);
						  		//echo '<script>alert("Welcome to Geeks for Geeks")</script>';
						  		//echo '<script>alert("'.$testid1.'")</script>';
						  		if ($testid1==$testid2){
						  			echo ' selected ';

						  		}
						  	}
						  	?>
						  	><?php echo $mod->display_name; ?></option><?php
						  }
						  ?> 
						 
					</select>
<script type="text/javascript" >
function save_moderatoren(){
	 var categoryid = "<?php echo $tcategory_id; ?>";
	 var selected = [];
	 var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
     for (var option of document.getElementById('moderatoren').options)
     {if (option.selected) {selected.push(option.value);}}
  
jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'check_tschaki_ajax',
            selectvalue: selected,
            catid: categoryid          
        },  
       success: function(data) {     
                      //alert(data);
                      //alert('ok');
                      },
                      error: function() {
                        alert('There was some error performing!');
                      }
    });
  }
</script>
						</td>
</tr>

			<tr class='form-field form-required term-name-wrap'>
				<th scope='row'>
					<label for='custom-field'><?php esc_attr_e( 'Image', 'smart-question-answer' ); ?></label>
				</th>
				<td>
					<a href="#" id="asqa-category-upload" class="button" data-action="asqa_media_uplaod" data-title="<?php esc_attr_e( 'Upload image', 'smart-question-answer' ); ?>" data-idc="#asqa_category_media_id" data-urlc="#asqa_category_media_url"><?php esc_attr_e( 'Upload image', 'smart-question-answer' ); ?></a>

					<?php if ( ! empty( $term_meta['image'] ) && ! empty( $term_meta['image']['url'] ) ) { ?>
						<img id="asqa_category_media_preview" data-action="asqa_media_value" src="<?php echo esc_url( $term_meta['image']['url'] ); ?>" />
					<?php } ?>

					<input id="asqa_category_media_url" type="hidden" data-action="asqa_media_value" name="asqa_category_image_url" value="<?php echo esc_url( $term_meta['image']['url'] ); ?>">

					<input id="asqa_category_media_id" type="hidden" data-action="asqa_media_value" name="asqa_category_image_id" value="<?php echo esc_attr( $term_meta['image']['id'] ); ?>">
					<a href="#" id="asqa-category-upload-remove" data-action="asqa_media_remove"><?php esc_attr_e( 'Remove image', 'smart-question-answer' ); ?></a>

					<p class='description'><?php esc_attr_e( 'Featured image for category', 'smart-question-answer' ); ?></p>
				</td>
			</tr>
			<tr class='form-field term-name-wrap'>
				<th scope='row'>
					<label for='custom-field'><?php esc_attr_e( 'Category icon class', 'smart-question-answer' ); ?></label>
				</th>
				<td>
					<input id="asqa_icon" type="text" name="asqa_icon" value="<?php echo esc_attr( $term_meta['icon'] ); ?>">
					<p class="description"><?php esc_attr_e( 'Font icon class, if image not set', 'smart-question-answer' ); ?></p>
				</td>
			</tr>
			<tr class='form-field term-name-wrap'>
				<th scope='row'>
					<label for='asqa-category-color'><?php esc_attr_e( 'Category icon color', 'smart-question-answer' ); ?></label>
				</th>
				<td>
					<input id="asqa-category-color" type="text" name="asqa_color" value="<?php echo esc_attr( $term_meta['color'] ); ?>">
					<p class="description"><?php esc_attr_e( 'Font icon class, if image not set', 'smart-question-answer' ); ?></p>
					<script type="text/javascript">
						jQuery(document).ready(function(){
							jQuery('#asqa-category-color').wpColorPicker();
						});
					</script>
				</td>
			</tr>
		<?php
	}

	/**
	 * Process and save category images.
	 *
	 * @param  integer $term_id Term id.
	 */
	public function save_image_field( $term_id ) {
		$image_url = asqa_isset_post_value( 'asqa_category_image_url', '' );
		$image_id  = asqa_isset_post_value( 'asqa_category_image_id', '' );
		$icon      = asqa_isset_post_value( 'asqa_icon', '' );
		$color     = asqa_isset_post_value( 'asqa_color', '' );

		if ( current_user_can( 'manage_categories' ) ) {
			// Get options from database - if not a array create a new one.
			$term_meta = get_term_meta( $term_id, 'asqa_category', true );

			if ( ! is_array( $term_meta ) ) {
				$term_meta = array( 'image' => array() );
			}

			if ( ! is_array( $term_meta['image'] ) ) {
				$term_meta['image'] = array();
			}

			// Image url.
			if ( ! empty( $image_url ) ) {
				$term_meta['image']['url'] = esc_url( $image_url );
			} else {
				unset( $term_meta['image']['url'] );
			}

			// Image id.
			if ( ! empty( $image_id ) ) {
				$term_meta['image']['id'] = (int) $image_id;
			} else {
				unset( $term_meta['image']['id'] );
			}

			// Category icon.
			if ( ! empty( $icon ) ) {
				$term_meta['icon'] = sanitize_text_field( $icon );
			} else {
				unset( $term_meta['icon'] );
			}

			// Category color.
			if ( ! empty( $color ) ) {
				$term_meta['color'] = sanitize_text_field( $color );
			} else {
				unset( $term_meta['color'] );
			}

			if ( empty( $term_meta['image'] ) ) {
				unset( $term_meta['image'] );
			}

			// Delete meta if empty.
			if ( empty( $term_meta ) ) {
				delete_term_meta( $term_id, 'asqa_category' );
			} else {
				update_term_meta( $term_id, 'asqa_category', $term_meta );
			}
		}
	}



	/**
	 * Process create and save category images.
	 *
	 * @param  integer $term_id Term id.
	 */
	public function create_save_image_field( $term_id ) {
		$image_url = asqa_isset_post_value( 'asqa_category_image_url', '' );
		$image_id  = asqa_isset_post_value( 'asqa_category_image_id', '' );
		$icon      = asqa_isset_post_value( 'asqa_icon', '' );
		$color     = asqa_isset_post_value( 'asqa_color', '' );

		if ( current_user_can( 'manage_categories' ) ) {
			// Get options from database - if not a array create a new one.
			$term_meta = get_term_meta( $term_id, 'asqa_category', true );

			if ( ! is_array( $term_meta ) ) {
				$term_meta = array( 'image' => array() );
			}

			if ( ! is_array( $term_meta['image'] ) ) {
				$term_meta['image'] = array();
			}

			// Image url.
			if ( ! empty( $image_url ) ) {
				$term_meta['image']['url'] = esc_url( $image_url );
			} else {
				unset( $term_meta['image']['url'] );
			}

			// Image id.
			if ( ! empty( $image_id ) ) {
				$term_meta['image']['id'] = (int) $image_id;
			} else {
				unset( $term_meta['image']['id'] );
			}

			// Category icon.
			if ( ! empty( $icon ) ) {
				$term_meta['icon'] = sanitize_text_field( $icon );
			} else {
				unset( $term_meta['icon'] );
			}

			// Category color.
			if ( ! empty( $color ) ) {
				$term_meta['color'] = sanitize_text_field( $color );
			} else {
				unset( $term_meta['color'] );
			}

			if ( empty( $term_meta['image'] ) ) {
				unset( $term_meta['image'] );
			}

			// Delete meta if empty.
			if ( empty( $term_meta ) ) {
				delete_term_meta( $term_id, 'asqa_category' );
			} else {
				update_term_meta( $term_id, 'asqa_category', $term_meta );
			}
		}
	}

	/**
	 * Add category pages rewrite rule.
	 *
	 * @param  array   $rules SmartQa rules.
	 * @param  string  $slug Slug.
	 * @param  integer $base_page_id Base page ID.
	 * @return array
	 * @since unknown
	 * @since 1.0.0 Fixed: category pagination.
	 */
	public function rewrite_rules( $rules, $slug, $base_page_id ) {
		$base_slug = get_page_uri( asqa_opt( 'categories_page' ) );
		update_option( 'asqa_categories_path', $base_slug, true );

		$cat_rules = array(
			$base_slug . '/([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?question_category=$matches[#]&paged=$matches[#]&asqa_page=category',
			$base_slug . '/([^/]+)/?$'                   => 'index.php?question_category=$matches[#]&asqa_page=category',
		);

		return $cat_rules + $rules;
	}

	/**
	 * Filter main questions query args. Modify and add category args.
	 *
	 * @param  array $args Questions args.
	 * @return array
	 */
	public function asqa_main_questions_args( $args ) {
		global $wp;
		$query = $wp->query_vars;

		$categories_operator = ! empty( $wp->query_vars['asqa_categories_operator'] ) ? $wp->query_vars['asqa_categories_operator'] : 'IN';
		$current_filter      = asqa_get_current_list_filters( 'category' );

		if ( isset( $query['asqa_categories'] ) && is_array( $query['asqa_categories'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'question_category',
				'field'    => 'slug',
				'terms'    => $query['asqa_categories'],
				'operator' => $categories_operator,
			);
		} elseif ( ! empty( $current_filter ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'question_category',
				'field'    => 'term_id',
				'terms'    => explode( ',', sanitize_comma_delimited( $current_filter ) ),
			);
		}

		return $args;
	}

	/**
	 * Subscriber action ID.
	 *
	 * @param  integer $action_id Current action ID.
	 * @return integer
	 */
	public function subscribers_action_id( $action_id ) {
		if ( is_question_category() ) {
			global $question_category;
			$action_id = $question_category->term_id;
		}

		return $action_id;
	}

	/**
	 * Filter ask button link to append current category link.
	 *
	 * @param  string $link Ask button link.
	 * @return string
	 */
	public function asqa_ask_btn_link( $link ) {
		if ( is_question_category() ) {
			$question_category = get_queried_object();
			return $link . '?category=' . $question_category->term_id;
		}

		return $link;
	}

	/**
	 * Filter canonical URL when in category page.
	 *
	 * @param  string $canonical_url url.
	 * @return string
	 * @deprecated 1.0.0
	 */
	public function asqa_canonical_url( $canonical_url ) {
		if ( is_question_category() ) {
			global $question_category;

			if ( ! $question_category ) {
				$question_category = get_queried_object();
			}

			return get_term_link( $question_category ); // @codingStandardsIgnoreLine.
		}

		return $canonical_url;
	}

	/**
	 * Category feed link in head.
	 */
	public function category_feed() {
		if ( is_question_category() ) {
			$question_category = get_queried_object();
			echo '<link href="' . esc_url( home_url( 'feed' ) ) . '?post_type=question&question_category=' . esc_url( $question_category->slug ) . '" title="' . esc_attr__( 'Question category feed', 'smart-question-answer' ) . '" type="application/rss+xml" rel="alternate">';
		}
	}

	/**
	 * Ajax callback for loading order by filter.
	 *
	 * @since 4.0.0
	 */
	public function load_filter_category() {
		$filter = asqa_sanitize_unslash( 'filter', 'r' );
		check_ajax_referer( 'filter_' . $filter, '__nonce' );

		$search = (string) asqa_sanitize_unslash( 'search', 'r', false );
		asqa_ajax_json(
			array(
				'success'  => true,
				'items'    => asqa_get_category_filter( $search ),
				'multiple' => true,
				'nonce'    => wp_create_nonce( 'filter_' . $filter ),
			)
		);
	}

	/**
	 * Output active category in filter
	 *
	 * @param object $active Active term.
	 * @param string $filter Filter.
	 * @since 4.0.0
	 */
	public function filter_active_category( $active, $filter ) {
		$current_filters = asqa_get_current_list_filters( 'category' );

		if ( ! empty( $current_filters ) ) {
			$args = array(
				'hierarchical'  => true,
				'hide_if_empty' => true,
				'number'        => 2,
				'include'       => $current_filters,
			);

			$terms = get_terms( 'question_category', $args );

			if ( $terms ) {
				$active_terms = array();
				foreach ( (array) $terms as $t ) {
					$active_terms[] = $t->name;
				}

				$count = is_array( $current_filters ) ? count( $current_filters ) : 0;

				// translators: placeholder contains count.
				$more_label = sprintf( __( ', %d+', 'smart-question-answer' ), $count - 2 );

				return ': <span class="asqa-filter-active">' . implode( ', ', $active_terms ) . ( $count > 2 ? $more_label : '' ) . '</span>';
			}
		}
	}

	/**
	 * Column header.
	 *
	 * @param array $columns Category columns.
	 * @return array
	 */
	public function column_header( $columns ) {
		$columns['icon'] = 'Icon';
		return $columns;
	}

	/**
	 * Icon column content.
	 *
	 * @param mixed  $value       Value.
	 * @param string $column_name Column name.
	 * @param int    $tax_id      Taxonomy id.
	 */
	public function column_content( $value, $column_name, $tax_id ) {
		if ( 'icon' === $column_name ) {
			asqa_category_icon( $tax_id );
		}
	}

	/**
	 * Modify current page to show category archive.
	 *
	 * @param string $query_var Current page.
	 * @return string
	 * @since 1.0.0
	 */
	public function asqa_current_page( $query_var ) {
		if ( 'categories' === $query_var && 'category' === get_query_var( 'asqa_page' ) ) {
			return 'category';
		}

		return $query_var;
	}

	/**
	 * Modify main query.
	 *
	 * @param array  $posts  Array of post object.
	 * @param object $query Wp_Query object.
	 * @return void|array
	 * @since 1.0.0
	 */
	public function modify_query_category_archive( $posts, $query ) {
		if ( $query->is_main_query() && $query->is_tax( 'question_category' ) && 'category' === get_query_var( 'asqa_page' ) ) {
			$query->found_posts   = 1;
			$query->max_num_pages = 1;
			$page                 = get_page( asqa_opt( 'categories_page' ) );
			$page->post_title     = get_queried_object()->name;
			$posts                = array( $page );
		}

		return $posts;
	}

	/**
	 * Include required files.
	 *
	 * @since 1.0.0
	 */
	public function widget() {
		require_once SMARTQA_ADDONS_DIR . '/categories/widget.php';
		register_widget( 'Smartqa\Widgets\Categories' );
	}
}

// Init addon.
Categories::init();
