<?php
/**
 * Template for user reputations item.
 *
 * Render reputation item in authors page.
 *
 * @author  Peter Mertzlin <peter.mertzlin@gmail.com>
 * @link    https://extensionforge.com/
 * @since   4.0.0
 * @package SmartQa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<table class="asqa-reputations">
	<tbody>
		<?php
		while ( $reputations->have() ) :
			$reputations->the_reputation();
?>
			<?php asqa_get_template_part( 'addons/reputation/item', [ 'reputations' => $reputations ] ); ?>
		<?php endwhile; ?>
	</tbody>
</table>

<?php if ( $reputations->total_pages > 1 ) : ?>
	<a href="#" asqa-loadmore="
	<?php
	echo esc_js(
		wp_json_encode(
			array(
				'asqa_ajax_action' => 'load_more_reputation',
				'__nonce'        => wp_create_nonce( 'load_more_reputation' ),
				'current'        => 1,
				'user_id'        => $reputations->args['user_id'],
			)
		)
	);
?>
" class="asqa-loadmore asqa-btn" ><?php esc_attr_e( 'Load More', 'smart-question-answer' ); ?></a>
<?php endif; ?>
