<?php
/**
 * User question template
 * Display user profile questions.
 *
 * @link https://extensionforge.com
 * @since 4.0.0
 * @package SmartQa
 *
 * @since 4.1.13 Fixed pagination issue when in main user page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp;

?>

<?php if ( asqa_have_questions() ) : ?>
	<div class="asqa-questions">
		<?php
		/* Start the Loop */
		while ( asqa_have_questions() ) :
			asqa_the_question();
			asqa_get_template_part( 'question-list-item' );
		endwhile;
		?>
	</div>

	<?php
		asqa_pagination( false, smartqa()->questions->max_num_pages, '?paged=%#%', asqa_user_link( asqa_current_user_id(), 'questions' ) );
	?>

	<?php
	else :
		asqa_get_template_part( 'content-none' );
	endif;
	?>
