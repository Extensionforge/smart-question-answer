<?php
/**
 * The WordPress Question and Answer Plugin.
 *
 * The most advance community question and answer system for WordPress
 *
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @copyright Copyright (c) 2011.0.00, Peter Mertzlin. 2020, LattePress
 * @license   GPL-3.0+ https://www.gnu.org/licenses/gpl-3.0.txt
 * @link      https://extensionforge.com
 * @package   SmartQa
 *
 * @wordpress-plugin
 * Plugin Name:       SmartQa Question Answer
 * Plugin URI:        https://extensionforge.com
 * Description:       The most advance community question and answer system for WordPress
 * Donate link:       https://paypal.me/smartqa
 * Version:           1.0.0
 * Author:            Steve Kraft & Peter Mertzlin
 * Author URI:        https://extensionforge.com
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       smart-question-answer
 * Domain Path:       /languages
 * GitHub Plugin URI: smartqa/smartqa
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define database version.
define( 'SMARTQA_DB_VERSION', 1 );

// Check if using required PHP version.
if ( version_compare( PHP_VERSION, '7.2' ) < 0 ) {

	/**
	 * Checks PHP version before initiating SmartQa.
	 */
	function asqa_admin_php_version__error() {
		$class    = 'notice notice-error';
		$message  = '<strong>' . __( 'SmartQa is not running!', 'smart-question-answer' ) . '</strong><br />';
		$message .= sprintf(
			// translators: %s contain server PHP version.
			__( 'Upps! At least PHP version 7.2 is required to run SmartQa. Current PHP version is %s. Please ask hosting provider to update your PHP version.', 'smart-question-answer' ),
			PHP_VERSION
		);
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	add_action( 'admin_notices', 'asqa_admin_php_version__error' );
	return;
}

if ( ! class_exists( 'SmartQa' ) ) {

	/**
	 * Main SmartQa class.
	 */
	class SmartQa {

		/**
		 * SmartQa version
		 *
		 * @access private
		 * @var string
		 */
		private $_plugin_version = '1.0.0'; // phpcs:ignore

		/**
		 * Class instance
		 *
		 * @access public
		 * @static
		 * @var SmartQa
		 */
		public static $instance = null;

		/**
		 * SmartQa pages
		 *
		 * @access public
		 * @var array All SmartQa pages
		 */
		public $pages;

		/**
		 * SmartQa menu
		 *
		 * @access public
		 * @var array SmartQa menu
		 */
		public $menu;

		/**
		 * SmartQa question loop
		 *
		 * @access public
		 * @var null|WP_Query SmartQa question query loop
		 */
		public $questions;

		/**
		 * Current question.
		 *
		 * @var WP_Post|null
		 */
		public $current_question;

		/**
		 * SmartQa answers loop.
		 *
		 * @var WP_Query|null Answer query loop
		 */
		public $answers;

		/**
		 * Current answer.
		 *
		 * @var WP_Post|null
		 */
		public $current_answer;

		/**
		 * The array of actions registered with WordPress.
		 *
		 * @since  1.0.0
		 * @access protected
		 * @var array The actions registered with WordPress to fire when the plugin loads.
		 */
		protected $actions;

		/**
		 * The array of filters registered with WordPress.
		 *
		 * @since  1.0.0
		 * @access protected
		 * @var array The filters registered with WordPress to fire when the plugin loads.
		 */
		protected $filters;

		/**
		 * SmartQa reputation events.
		 *
		 * @access public
		 * @var object
		 */
		public $reputation_events;

		/**
		 * SmartQa user pages.
		 *
		 * @access public
		 * @var object
		 */
		public $user_pages;

		/**
		 * SmartQa question rewrite rules.
		 *
		 * @var array
		 * @since 1.0.0
		 */
		public $question_rule = array();

		/**
		 * The forms.
		 *
		 * @var array
		 * @since 1.0.0
		 */
		public $forms = array();

		/**
		 * The activity object.
		 *
		 * @var void|object
		 * @since 1.0.0
		 */
		public $activity;

		/**
		 * The session.
		 *
		 * @var SmartQa\Session
		 * @since 1.0.0
		 */
		public $session;

		/**
		 * Used for storing new filters.
		 *
		 * @since 1.0.0
		 * @var object
		 */
		public $new_filters;

		/**
		 * Initializes the plugin by setting localization, hooks, filters, and administrative functions.
		 *
		 * @access public
		 * @static
		 *
		 * @return SmartQa
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
				self::$instance = new self();
				self::$instance->setup_constants();
				self::$instance->actions = array();
				self::$instance->filters = array();

				self::$instance->includes();
				self::$instance->session = SmartQa\Session::init();

				self::$instance->site_include();
				self::$instance->ajax_hooks();
				SmartQa_PostTypes::init();

				/*
				* Dashboard and Administrative Functionality
				*/
				if ( is_admin() ) {
					require_once SMARTQA_DIR . 'admin/smartqa-admin.php';
					require_once SMARTQA_DIR . 'admin/class-list-table-hooks.php';

					SmartQa_Admin::init();
					SmartQa_Post_Table_Hooks::init();
				}

				new SmartQa_Process_Form();

				/*
				 * Hooks for extension to load their codes after SmartQa is loaded.
				 */
				do_action( 'smartqa_loaded' );

				if ( class_exists( 'WP_CLI' ) ) {
					WP_CLI::add_command( 'smartqa', 'SmartQa_Cli' );
				}
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants.
		 *
		 * @since  1.0.0
		 * @access private
		 * @since 1.0.0 Made constants compatible for code editors.
		 */
		private function setup_constants() {
			$plugin_dir = wp_normalize_path( plugin_dir_path( __FILE__ ) );

			define( 'DS', DIRECTORY_SEPARATOR );
			define( 'ASQA_VERSION', $this->_plugin_version );
			define( 'SMARTQA_DIR', $plugin_dir );
			define( 'SMARTQA_URL', plugin_dir_url( __FILE__ ) );
			define( 'SMARTQA_WIDGET_DIR', $plugin_dir . 'widgets/' );
			define( 'SMARTQA_THEME_DIR', $plugin_dir . 'templates' );
			define( 'SMARTQA_THEME_URL', SMARTQA_URL . 'templates' );
			define( 'SMARTQA_CACHE_DIR', WP_CONTENT_DIR . '/cache/smartqa' );
			define( 'SMARTQA_CACHE_TIME', HOUR_IN_SECONDS );
			define( 'SMARTQA_ADDONS_DIR', $plugin_dir . 'addons' );
		}

		/**
		 * Include required files.
		 *
		 * @access private
		 * @since  1.0.0
		 * @since  1.0.0 Added categories/categories.php
		 */
		private function includes() {
			require_once SMARTQA_DIR . 'loader.php';
			require_once SMARTQA_DIR . 'includes/activity.php';
			require_once SMARTQA_DIR . 'includes/common-pages.php';
			require_once SMARTQA_DIR . 'includes/class-theme.php';
			require_once SMARTQA_DIR . 'includes/class-form-hooks.php';
			require_once SMARTQA_DIR . 'includes/options.php';
			require_once SMARTQA_DIR . 'includes/functions.php';
			require_once SMARTQA_DIR . 'includes/hooks.php';
			require_once SMARTQA_DIR . 'includes/question-loop.php';
			require_once SMARTQA_DIR . 'includes/answer-loop.php';
			require_once SMARTQA_DIR . 'includes/qameta.php';
			require_once SMARTQA_DIR . 'includes/qaquery.php';
			require_once SMARTQA_DIR . 'includes/qaquery-hooks.php';
			require_once SMARTQA_DIR . 'includes/post-types.php';
			require_once SMARTQA_DIR . 'includes/post-status.php';
			require_once SMARTQA_DIR . 'includes/votes.php';
			require_once SMARTQA_DIR . 'includes/views.php';
			require_once SMARTQA_DIR . 'includes/theme.php';
			require_once SMARTQA_DIR . 'includes/shortcode-basepage.php';
			require_once SMARTQA_DIR . 'includes/process-form.php';
			require_once SMARTQA_DIR . 'includes/rewrite.php';
			require_once SMARTQA_DIR . 'includes/deprecated.php';
			require_once SMARTQA_DIR . 'includes/flag.php';
			require_once SMARTQA_DIR . 'includes/shortcode-question.php';
			require_once SMARTQA_DIR . 'includes/akismet.php';
			require_once SMARTQA_DIR . 'includes/comments.php';
			require_once SMARTQA_DIR . 'includes/upload.php';
			require_once SMARTQA_DIR . 'includes/taxo.php';
			require_once SMARTQA_DIR . 'includes/reputation.php';
			require_once SMARTQA_DIR . 'includes/subscribers.php';
			require_once SMARTQA_DIR . 'includes/class-query.php';
			require_once SMARTQA_DIR . 'includes/class/class-activity-helper.php';
			require_once SMARTQA_DIR . 'includes/class/class-activity.php';
			require_once SMARTQA_DIR . 'includes/class/class-session.php';

			require_once SMARTQA_DIR . 'widgets/search.php';
			require_once SMARTQA_DIR . 'widgets/question_stats.php';
			require_once SMARTQA_DIR . 'widgets/questions.php';
			require_once SMARTQA_DIR . 'widgets/breadcrumbs.php';
			require_once SMARTQA_DIR . 'widgets/ask-form.php';
			require_once SMARTQA_DIR . 'widgets/leaderboard.php';

			require_once SMARTQA_DIR . 'lib/class-smartqa-upgrader.php';
			require_once SMARTQA_DIR . 'lib/class-form.php';
			require_once SMARTQA_DIR . 'lib/form/class-field.php';
			require_once SMARTQA_DIR . 'lib/form/class-input.php';
			require_once SMARTQA_DIR . 'lib/form/class-group.php';
			require_once SMARTQA_DIR . 'lib/form/class-repeatable.php';
			require_once SMARTQA_DIR . 'lib/form/class-checkbox.php';
			require_once SMARTQA_DIR . 'lib/form/class-select.php';
			require_once SMARTQA_DIR . 'lib/form/class-editor.php';
			require_once SMARTQA_DIR . 'lib/form/class-upload.php';
			require_once SMARTQA_DIR . 'lib/form/class-tags.php';
			require_once SMARTQA_DIR . 'lib/form/class-radio.php';
			require_once SMARTQA_DIR . 'lib/form/class-textarea.php';
			require_once SMARTQA_DIR . 'lib/class-validate.php';
			require_once SMARTQA_DIR . 'lib/class-wp-async-task.php';

			require_once SMARTQA_DIR . 'includes/class-async-tasks.php';

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				require_once SMARTQA_DIR . 'lib/class-smartqa-cli.php';
			}
		}

		/**
		 * Register ajax hooks
		 *
		 * @access public
		 */
		public function ajax_hooks() {
			// Load ajax hooks only if DOING_AJAX defined.
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				require_once SMARTQA_DIR . 'admin/ajax.php';
				require_once SMARTQA_DIR . 'includes/ajax-hooks.php';

				SmartQa_Ajax::init();
				SmartQa_Admin_Ajax::init();
			}
		}

		/**
		 * Include all public classes
		 *
		 * @access public
		 * @since 1.0.0
		 * @since 1.0.0 Load all addons if constant `SMARTQA_ENABLE_ADDONS` is set.
		 */
		public function site_include() {
			$this->theme_compat = new stdClass(); // Base theme compatibility class.

			$this->theme_compat->active = false;

			\SmartQa_Hooks::init();
			$this->activity = SmartQa\Activity_Helper::get_instance();
			\SmartQa_Views::init();

			// Load all addons if constant set.
			if ( defined( 'SMARTQA_ENABLE_ADDONS' ) && SMARTQA_ENABLE_ADDONS ) {
				foreach ( asqa_get_addons() as $name => $data ) {
					asqa_activate_addon( $name );
				}
			}

			foreach ( (array) asqa_get_addons() as $data ) {
				if ( $data['active'] && file_exists( $data['path'] ) ) {
					require_once $data['path'];
				}
			}
		}

		/**
		 * Add a new action to the collection to be registered with WordPress.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @param string            $hook          The name of the WordPress action that is being registered.
		 * @param object            $component     A reference to the instance of the object on which the action is defined.
		 * @param string            $callback      The name of the function definition on the $component.
		 * @param int      Optional $priority      The priority at which the function should be fired.
		 * @param int      Optional $accepted_args The number of arguments that should be passed to the $callback.
		 */
		public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
			$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
		}

		/**
		 * Add a new filter to the collection to be registered with WordPress.
		 *
		 * @since  2.4
		 * @access public
		 *
		 * @param string            $hook          The name of the WordPress filter that is being registered.
		 * @param object            $component     A reference to the instance of the object on which the filter is defined.
		 * @param string            $callback      The name of the function definition on the $component.
		 * @param int      Optional $priority      The priority at which the function should be fired.
		 * @param int      Optional $accepted_args The number of arguments that should be passed to the $callback.
		 */
		public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
			$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
		}

		/**
		 * A utility function that is used to register the actions and hooks into a single
		 * collection.
		 *
		 * @since  2.4
		 * @access private
		 *
		 * @param array  $hooks         The collection of hooks that is being registered (that is, actions or filters).
		 * @param string $hook          The name of the WordPress filter that is being registered.
		 * @param object $component     A reference to the instance of the object on which the filter is defined.
		 * @param string $callback      The name of the function definition on the $component.
		 * @param int    $priority      The priority at which the function should be fired.
		 * @param int    $accepted_args The number of arguments that should be passed to the $callback.
		 *
		 * @return type The collection of actions and filters registered with WordPress.
		 */
		private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
			$hooks[] = array(
				'hook'          => $hook,
				'component'     => $component,
				'callback'      => $callback,
				'priority'      => $priority,
				'accepted_args' => $accepted_args,
			);

			return $hooks;
		}

		/**
		 * Register the filters and actions with WordPress.
		 *
		 * @access public
		 */
		public function setup_hooks() {
			foreach ( $this->filters as $hook ) {
				add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
			}

			foreach ( $this->actions as $hook ) {
				add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
			}
		}

		/**
		 * Get specific SmartQa form.
		 *
		 * @param string $name Name of form.
		 * @return false|object
		 * @throws \Exception Throws when requested from does not exits.
		 * @since 1.0.0
		 * @since 1.0.0 Fixed: Only variable references should be returned by reference.
		 */
		public function &get_form( $name ) {
			$name = preg_replace( '/^form_/i', '', $name );

			if ( $this->form_exists( $name ) ) {
				return $this->forms[ $name ];
			}

			throw new \Exception(
				sprintf(
					// translators: %s contains name of the form requested.
					__( 'Requested form: %s is not registered .', 'smart-question-answer' ),
					$name
				)
			);
		}

		/**
		 * Check if a form exists in SmartQa, if not then tries to register.
		 *
		 * @param string $name Name of form.
		 * @return boolean
		 * @since 1.0.0
		 */
		public function form_exists( $name ) {
			$name = preg_replace( '/^form_/i', '', $name );

			if ( isset( $this->forms[ $name ] ) ) {
				return true;
			}

			/**
			 * Register a form in SmartQa.
			 *
			 * @param array $form {
			 *      Form options and fields. Check @see `SmartQa\Form` for more detail.
			 *
			 *      @type string  $submit_label Custom submit button label.
			 *      @type boolean $editing      Pass true if currently in editing mode.
			 *      @type integer $editing_id   If editing then pass editing post or comment id.
			 *      @type array   $fields       Fields. For more detail on field option check documentations.
			 * }
			 * @since 1.0.0
			 * @todo  Add detailed docs for `$fields`.
			 */
			$args = apply_filters( 'asqa_form_' . $name, null );

			if ( ! is_null( $args ) && ! empty( $args ) ) {
				$this->forms[ $name ] = new SmartQa\Form( 'form_' . $name, $args );

				return true;
			}

			return false;
		}
	}
}

/**
 * Run SmartQa thingy
 *
 * @return object
 */
if ( ! function_exists( 'smartqa' ) ) {
	/**
	 * Initialize SmartQa.
	 */
	function smartqa() {
		return SmartQa::instance();
	}
}

if ( ! class_exists( 'SmartQa_Init' ) ) {

	/**
	 * SmartQa initialization class.
	 */
	class SmartQa_Init { // phpcs:ignore

		/**
		 * Load smartqa.
		 *
		 * @access public
		 * @static
		 */
		public static function load_smartqa() {
			/*
			 * Action before loading SmartQa.
			 * @since 2.4.7
			 */
			do_action( 'before_loading_smartqa' );
			smartqa()->setup_hooks();
		}

		/**
		 * Load translations.
		 *
		 * @since  1.0.0
		 * @access public
		 * @static
		 */
		public static function load_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'smart-question-answer' );
			$loaded = load_textdomain( 'smart-question-answer', trailingslashit( WP_LANG_DIR ) . "smart-question-answer/smart-question-answer-{$locale}.mo" );

			if ( $loaded ) {
				return $loaded;
			} else {
				load_plugin_textdomain( 'smart-question-answer', false, basename( dirname( __FILE__ ) ) . '/languages/' );
			}
		}

		/**
		 * Creating table whenever a new blog is created
		 *
		 * @access public
		 * @static
		 *
		 * @param  integer $blog_id Blog id.
		 * @param  integer $user_id User id.
		 * @param  string  $domain  Domain.
		 * @param  string  $path    Path.
		 * @param  integer $site_id Site id.
		 * @param  array   $meta    Site meta.
		 */
		public static function create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
			if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
				switch_to_blog( $blog_id ); // @codingStandardsIgnoreLine
				require_once dirname( __FILE__ ) . '/activate.php';
				ASQA_Activate::get_instance( true );
				restore_current_blog();
			}
		}

		/**
		 * Deleting the table whenever a blog is deleted
		 *
		 * @access public
		 * @static
		 *
		 * @param  array $tables  Table names.
		 * @param  int   $blog_id Blog ID.
		 *
		 * @return array
		 */
		public static function drop_blog_tables( $tables, $blog_id ) {
			if ( empty( $blog_id ) || 1 === (int) $blog_id || $blog_id !== $GLOBALS['blog_id'] ) {
				return $tables;
			}

			global $wpdb;

			$tables[] = $wpdb->prefix . 'asqa_views';
			$tables[] = $wpdb->prefix . 'asqa_qameta';
			$tables[] = $wpdb->prefix . 'asqa_activity';
			$tables[] = $wpdb->prefix . 'asqa_votes';
			return $tables;
		}
	}
}

add_action( 'plugins_loaded', array( 'SmartQa_Init', 'load_smartqa' ), 1 );
add_action( 'plugins_loaded', array( 'SmartQa_Init', 'load_textdomain' ), 0 );
add_action( 'wpmu_new_blog', array( 'SmartQa_Init', 'create_blog' ), 10, 6 );
add_filter( 'wpmu_drop_tables', array( 'SmartQa_Init', 'drop_blog_tables' ), 10, 2 );

require_once dirname( __FILE__ ) . '/includes/class/roles-cap.php';
require_once dirname( __FILE__ ) . '/includes/class/class-singleton.php';

/**
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
function smartqa_activation() {
	require_once dirname( __FILE__ ) . '/activate.php';
	\ASQA_Activate::get_instance();
}
register_activation_hook( __FILE__, 'smartqa_activation' );


function hook_tschaki_ajax( ){
    wp_enqueue_script( 'script-checker', plugin_dir_url( __FILE__ ) . 'js/script-checker.js' );
    wp_localize_script( 'script-checker', 'account_script_checker', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'fail_message' => __('Connection to server failed. Check the mail credentials.', 'script-checker'),
            'success_message' => __('Connection successful. ', 'script-checker')
        )
    );
}
add_action( 'enqueue_scripts', 'hook_tschaki_ajax' );
add_action( 'admin_enqueue_scripts', 'hook_tschaki_ajax' );

function check_tschaki_ajax( ) {
	global $wpdb;
    $table_name = $wpdb->prefix . 'asqa_moderators';

    $senderid = $_POST['selectvalue'];
    $catid = intval($_POST['catid']);
    // entry here your function for ajax request
    $wpdb->delete($table_name, array('cat_id' => $catid));
    if(is_array($senderid)) {
        //echo "Array"; Speichern meherer Moderatoren
        foreach($senderid as $moderatorid){
             $moderator = intval($moderatorid);
         $wpdb->insert($table_name, array('user_id' => $moderator, 'cat_id' => $catid));
        }
    } else {
        //echo "Not an Array"; Speichern eines Moderators
         
        $senderid = intval($senderid); 
        $wpdb->insert($table_name, array('user_id' => $senderid, 'cat_id' => $catid));
    }
    echo json_encode($senderid);
    }
    
add_action( 'wp_ajax_nopriv_check_tschaki_ajax', 'check_tschaki_ajax' );
add_action( 'wp_ajax_check_tschaki_ajax', 'check_tschaki_ajax' );


function my_asqa_allowed_mimes( $default_mimes ) {
  $default_mimes['pdf']      = 'application/pdf';
  $default_mimes['doc|docx'] = 'application/msword';
  $default_mimes['avi']      = 'video/x-msvideo';
  
  return $default_mimes;
}  
add_filter( 'asqa_allowed_mimes', 'my_asqa_allowed_mimes' );
