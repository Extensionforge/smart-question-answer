<?php
/**
 * SmartQa admin section recount section
 *
 * @link https://extensionforge.com
 * @since 4.0.5
 * @author Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package SmartQa
 * @since 1.0.0 Fixed: CS bugs.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$recounts = array(
	'votes'       => array(
		'label' => __( 'Recount Votes', 'smart-question-answer' ),
		'desc'  => __( 'Recount votes of questions and answers.', 'smart-question-answer' ),
	),
	'answers'     => array(
		'label' => __( 'Recount Answers', 'smart-question-answer' ),
		'desc'  => __( 'Recount answers of every question.', 'smart-question-answer' ),
	),
	'flagged'     => array(
		'label' => __( 'Recount Flags', 'smart-question-answer' ),
		'desc'  => __( 'Recount flags on questions and answers.', 'smart-question-answer' ),
	),
	'subscribers' => array(
		'label' => __( 'Recount Subscribers', 'smart-question-answer' ),
		'desc'  => __( 'Recount subscribers of questions.', 'smart-question-answer' ),
	),
	'reputation'  => array(
		'label' => __( 'Recount Reputation', 'smart-question-answer' ),
		'desc'  => __( 'Recount reputation of all users.', 'smart-question-answer' ),
	),
);

?>
<style>

	.btn-container .button{
		transition: padding-right 0.5s;
	}

	.btn-container span.success, .btn-container span.failed{
		background: none;
	}
	.hide{
		display: none !important;
	}
</style>

<div class="wrap">
	<?php do_action( 'asqa_before_admin_page_title' ); ?>
	<table class="form-table">
		<tbody>

			<?php foreach ( $recounts as $rc => $args ) : ?>
				<tr>
					<th scope="row" valign="top">
						<label><?php echo esc_attr( $args['label'] ); ?></label>
					</th>
					<td>
						<?php
							$btn_args = wp_json_encode(
								array(
									'action'  => 'asqa_recount_' . $rc,
									'__nonce' => wp_create_nonce( 'recount_' . $rc ),
								)
							);
						?>
						<div class="btn-container asqa-recount-<?php echo esc_attr( $rc ); ?>">
							<button class="button asqa-recount-btn" data-query="<?php echo esc_js( $btn_args ); ?>"><?php echo esc_attr( $args['label'] ); ?></button>
							<span class="recount-msg"></span>
						</div>
						<p class="description"><?php echo esc_attr( $args['desc'] ); ?></p>
					</td>
				</tr>
			<?php endforeach; ?>

			<tr>
				<th scope="row" valign="top">
					<label><?php esc_attr_e( 'Views', 'smart-question-answer' ); ?></label>
				</th>
				<td>
					<?php
						$btn_args = wp_json_encode(
							array(
								'action'  => 'asqa_recount_views',
								'__nonce' => wp_create_nonce( 'recount_views' ),
							)
						);
						?>
					<div class="btn-container asqa-recount-views">
						<button class="button asqa-recount-btn" data-query="<?php echo esc_js( $btn_args ); ?>"><?php esc_attr_e( 'Recount question views', 'smart-question-answer' ); ?></button>

						<span class="recount-msg"></span>
					</div>
					<p class="description"><?php esc_attr_e( 'Recount views count of all questions.', 'smart-question-answer' ); ?></p>
					<br />
					<form class="counter-args">
						<p><strong><?php esc_attr_e( 'Add fake views if views table is empty', 'smart-question-answer' ); ?></strong></p>
						<label>
							<?php esc_attr_e( 'Add fake views', 'smart-question-answer' ); ?>
							<input type="checkbox" name="fake_views" value="1" />
						</label>
						<br />
						<br />
						<label><?php esc_attr_e( 'Minimum and maximum views', 'smart-question-answer' ); ?></label>
						<input type="text" value="500" name="min_views" placeholder="<?php esc_attr_e( 'Min. views', 'smart-question-answer' ); ?>" />
						<input type="text" value="1000" name="max_views" placeholder="<?php esc_attr_e( 'Max. views', 'smart-question-answer' ); ?>" />
					</form>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<script type="text/javascript">
	(function($){
		var viewsArgs;

		function apRecount(query){
			query.args = viewsArgs;
			SmartQa.ajax({
				data: query,
				success: function(data){
					SmartQa.hideLoading($(data.el).find('.button'));
					$(data.el).find('.recount-msg').text(data.msg);
					if(typeof data.q !== 'undefined'){
						SmartQa.showLoading($(data.el).find('.button'));
						apRecount(data.q);
					}
				}
			});
		}

		$('.asqa-recount-btn').on('click',function(e){
			e.preventDefault();
			var query = $(this).data('query');
			SmartQa.showLoading($(this));
			viewsArgs = $(this).closest('td').find('form').serialize();
			apRecount(query);
		})
	})(jQuery);

</script>
