<?php
/**
 * SmartQa ask widget form.
 *
 * @package SmartQa
 * @author  Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license GPL 3+ GNU GPL licence above 3+
 * @link    https://extensionforge.com
 * @since   2.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Ask from widget.
 */
class ASQA_Askform_Widget extends WP_Widget {

	/**
	 * Initialize the class
	 */
	public function __construct() {
		parent::__construct(
			'asqa_askform_widget',
			__( '(SmartQa) Ask form', 'smart-question-answer' ),
			array( 'description' => __( 'SmartQa ask form widget', 'smart-question-answer' ) )
		);
	}

	/**
	 * Render widget.
	 *
	 * @param array $args     Arguments.
	 * @param array $instance Instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		/**
		 * This filter is documented in widgets/question_stats.php
		 */
		$title = apply_filters( 'widget_title', $instance['title'] );
		$title = is_string( $title ) ? $title : '';

		echo wp_kses_post( $args['before_widget'] );

		if ( ! empty( $title ) ) {
			echo wp_kses_post( $args['before_title'] . esc_html( $title ) . $args['after_title'] );
		}

		wp_enqueue_script( 'smartqa-theme' );
		?>
		<div id="asqa-ask-page" class="asqa-widget-inner">
			<?php asqa_ask_form(); ?>
		</div>
		<?php
		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Form.
	 *
	 * @param array $instance Instacne.
	 * @return string
	 */
	public function form( $instance ) {
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Ask questions', 'smart-question-answer' );
		}
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_attr_e( 'Title:', 'smart-question-answer' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<?php

		return 'noform';
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Old widget values.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}

/**
 * Register ask form widget.
 *
 * @return void
 */
function asqa_quickask_register_widgets() : void {
	register_widget( 'ASQA_Askform_Widget' );
}
add_action( 'widgets_init', 'asqa_quickask_register_widgets' );
