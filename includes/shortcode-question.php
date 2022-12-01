<?php
/**
 * Class for SmartQa embed question shortcode
 *
 * @package   SmartQa
 * @author    Peter Mertzlin <peter.mertzlin@gmail.com>
 * @license   GPL-2.0+
 * @link      https://extensionforge.com
 * @copyright 2014 Peter Mertzlin
 */

/**
 * Class for SmartQa base page shortcode.
 *
 * @since unknown
 * @since 1.0.0 Fixed: CS bugs.
 */
class SmartQa_Question_Shortcode {
	/**
	 * Instance of this class.
	 *
	 * @var SmartQa_Question|null
	 */
	protected static $instance = null;

	/**
	 * Return singleton instance of this class.
	 *
	 * @return SmartQa_Question
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Control the output of [question] shortcode
	 *
	 * @param  array  $atts Attributes.
	 * @param  string $content Content.
	 * @return string
	 * @since 2.0.0
	 */
	public function smartqa_question_sc( $atts, $content = '' ) {
		ob_start();
		echo '<div id="smartqa" class="asqa-eq">';

		/**
		 * Action is fired before loading SmartQa body.
		 */
		do_action( 'asqa_before_question_shortcode' );

		$id = ! empty( $atts['ID'] ) ? absint( $atts['ID'] ) : absint( $atts['id'] );

		$questions = asqa_get_question( $id );

		if ( $questions->have_posts() ) {
			/**
			 * Set current question as global post
			 *
			 * @since 2.3.3
			 */

			while ( $questions->have_posts() ) :
				$questions->the_post();
				include asqa_get_theme_location( 'shortcode/question.php' );
			endwhile;
		} else {
			esc_attr_e( 'Invalid or non existing question id.', 'smart-question-answer' );
		}

		echo '</div>';
		wp_reset_postdata();

		return ob_get_clean();
	}

}

