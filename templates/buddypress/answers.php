<?php
/**
 * Display answers list
 *
 * This template is used in base page, category, tag , etc
 *
 * @link https://extensionforge.com
 * @since 4.0.0
 *
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="asqa-bp-question">
	<?php if ( asqa_have_answers() ) : ?>
		<?php
			/* Start the Loop */
		while ( asqa_have_answers() ) :
			asqa_the_answer();
			asqa_get_template_part( 'buddypress/answer-item' );
			endwhile;
		?>
		<?php asqa_answers_the_pagination(); ?>
		<?php
		else :
			asqa_get_template_part( 'content-none' );
		endif;
		?>
</div>
