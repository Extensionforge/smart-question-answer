<?php
/**
 * SmartQa breadcrumbs widget
 *
 * @package SmartQa
 * @author  Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license GPL 2+ GNU GPL licence above 2+
 * @link    https://extensionforge.com
 * @since   2.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * SmartQa breadcrumbs widget.
 */
class SmartQa_Breadcrumbs_Widget extends WP_Widget {

	/**
	 * Initialize the class
	 */
	public function __construct() {
		parent::__construct(
			'asqa_breadcrumbs_widget',
			__( '(SmartQa) Breadcrumbs', 'smart-question-answer' ),
			array( 'description' => __( 'Show current smartqa page navigation', 'smart-question-answer' ) )
		);
	}


	/**
	 * Get breadcrumbs array
	 *
	 * @return array
	 */
	public static function get_breadcrumbs() {
		$current_page = asqa_current_page();
		$title        = asqa_page_title();
		$a            = array();

		$a['base'] = array(
			'title' => asqa_opt( 'base_page_title' ),
			'link'  => asqa_base_page_link(),
			'order' => 0,
		);

		$current_page = $current_page ? $current_page : '';

		if ( is_question() ) {
			$a['page'] = array(
				'title' => $title,
				'link'  => get_permalink( get_question_id() ),
				'order' => 10,
			);
		} elseif ( 'base' !== $current_page && '' !== $current_page ) {
			$a['page'] = array(
				'title' => $title,
				'link'  => $current_page,
				'order' => 10,
			);
		}

		$a = apply_filters( 'asqa_breadcrumbs', $a );

		return is_array( $a ) ? asqa_sort_array_by_order( $a ) : array();
	}

	/**
	 * Output SmartQa breadcrumbs.
	 *
	 * @return void
	 */
	public static function breadcrumbs() {
		$navs = self::get_breadcrumbs();

		echo '<ul class="asqa-breadcrumbs clearfix">';
		echo '<li class="asqa-breadcrumbs-home"><a href="' . esc_url( home_url( '/' ) ) . '" class="apicon-home"></a></li>';
		echo '<li><i class="apicon-chevron-right"></i></li>';

		$i         = 1;
		$total_nav = count( $navs );

		foreach ( $navs as $k => $nav ) {
			if ( ! empty( $nav ) ) {
				echo '<li>';
				echo '<a href="' . esc_url( $nav['link'] ) . '">' . esc_attr( $nav['title'] ) . '</a>';
				echo '</li>';

				if ( $total_nav !== $i ) {
					echo '<li><i class="apicon-chevron-right"></i></li>';
				}
			}
			++$i;
		}

		echo '</ul>';
	}

	/**
	 * Output widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		echo wp_kses_post( $args['before_widget'] );
		self::breadcrumbs();
		echo wp_kses_post( $args['after_widget'] );
	}
}

/**
 * Register breadcrumbs widget.
 *
 * @return void
 */
function register_smartqa_breadcrumbs() {
	register_widget( 'SmartQa_Breadcrumbs_Widget' );
}
add_action( 'widgets_init', 'register_smartqa_breadcrumbs' );
