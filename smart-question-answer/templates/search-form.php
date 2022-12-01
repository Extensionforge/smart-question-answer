<?php
/**
 * Template for search form.
 * Different from WP default searchfrom.php. This only search for question and answer.
 *
 * @package SmartQa
 * @author  Peter Mertzlin <peter.mertzlin@gmail.com>
 *
 * @since   3.0.0
 * @since   4.1.0 Changed action link to home. Added post_type hidden field.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<form id="asqa-search-form" class="asqa-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<button class="asqa-btn asqa-search-btn" type="submit"><?php esc_attr_e( 'Search', 'smart-question-answer' ); ?></button>
	<div class="asqa-search-inner no-overflow">
		<input name="s" type="text" class="asqa-search-input asqa-form-input" placeholder="<?php esc_attr_e( 'Search questions...', 'smart-question-answer' ); ?>" value="<?php the_search_query(); ?>" />
		<input type="hidden" name="post_type" value="question" />
	</div>
</form>
