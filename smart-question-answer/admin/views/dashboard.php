<?php
/**
 * Control the output of SmartQa dashboard
 *
 * @link https://extensionforge.com
 * @since 2.0.0
 * @author Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package SmartQa
 * @since 1.0.0 Fixed: CS bugs.
 *
 * @todo Improve this page.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Dashboard class.
 */
class SmartQa_Dashboard {
	/**
	 * Init class.
	 */
	public static function init() {
		add_action( 'admin_footer', array( __CLASS__, 'admin_footer' ) );

		add_meta_box( 'asqa-mb-attn', '<i class="apicon-alert"></i>' . __( 'Require Attention', 'smart-question-answer' ), array( __CLASS__, 'smartqa_attn' ), 'smartqa', 'column1', 'core' );

		add_meta_box( 'asqa-mb-qstats', '<i class="apicon-question"></i>' . __( 'Questions', 'smart-question-answer' ), array( __CLASS__, 'smartqa_stats' ), 'smartqa', 'column2', 'core' );

		add_meta_box( 'asqa-mb-latestq', __( 'Latest Questions', 'smart-question-answer' ), array( __CLASS__, 'smartqa_latestq' ), 'smartqa', 'column2', 'core' );

		add_meta_box( 'asqa-mb-astats', '<i class="apicon-answer"></i>' . __( 'Answer', 'smart-question-answer' ), array( __CLASS__, 'smartqa_astats' ), 'smartqa', 'column3', 'core' );

		add_meta_box( 'asqa-mb-latesta', __( 'Latest Answers', 'smart-question-answer' ), array( __CLASS__, 'smartqa_latesta' ), 'smartqa', 'column3', 'core' );
	}

	/**
	 * Add javascript in dashboard footer.
	 */
	public static function admin_footer() {
		?>
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {
				// close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// postboxes setup
				postboxes.add_postbox_toggles('smartqa');
			});
			//]]>
		</script>
		<?php
	}

	/**
	 * Full SmartQa stats.
	 */
	public static function smartqa_stats() {
		$question_count = asqa_total_posts_count( 'question' );
		?>
		<div class="main">
			<ul>
				<li class="post-count">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=question' ) ); ?>" class="publish">
						<?php
							// translators: placeholder contain count.
							echo esc_attr( sprintf( __( '%d Published', 'smart-question-answer' ), $question_count->publish ) );
						?>
					</a>
				</li>
				<li class="post-count">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=question&post_status=private_post' ) ); ?>" class="private">
						<?php
							// translators: placeholder contain count.
							echo esc_attr( sprintf( __( '%d Private', 'smart-question-answer' ), $question_count->private_post ) );
						?>
					</a>
				</li>
				<li class="post-count">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=question&post_status=moderate' ) ); ?>" class="moderate">
						<?php
							// translators: placeholder contain count.
							echo esc_attr( sprintf( __( '%d Moderate', 'smart-question-answer' ), $question_count->moderate ) );
						?>
					</a>
				</li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Show latest questions.
	 */
	public static function smartqa_latestq() {
		global $wpdb;

		$results = $wpdb->get_results( "SELECT date_format(post_date, '%d %a') as post_day, post_date, count(ID) as post_count from {$wpdb->posts} WHERE post_status IN('publish', 'private_post', 'moderate') AND post_type = 'question' AND post_date > (NOW() - INTERVAL 1 MONTH) GROUP BY post_day ORDER BY post_date ASC" ); // phpcs:ignore

		$days   = array();
		$counts = array();

		foreach ( (array) $results as $r ) {
			$days[]   = $r->post_day;
			$counts[] = $r->post_count;
		}
		?>
		<?php if ( $results ) : ?>

		<?php endif; ?>
		<div class="main">

			<?php
			smartqa()->questions = asqa_get_questions(
				array(
					'asqa_order_by' => 'newest',
					'showposts'   => 5,
				)
			);
			?>

			<?php if ( asqa_have_questions() ) : ?>
				<ul class="post-list">
					<?php
					while ( asqa_have_questions() ) :
						asqa_the_question();
						?>
						<li>
							<a target="_blank" href="<?php the_permalink(); ?>"><?php the_title(); ?></a> -
							<span class="posted"><?php the_date(); ?></span>
						</li>
					<?php endwhile; ?>
				</ul>
			<?php endif; ?>

			<?php wp_reset_postdata(); ?>
		</div>
		<?php
	}

	/**
	 * Show latest answers.
	 */
	public static function smartqa_latesta() {
		global $answers, $wpdb;

		$results = $wpdb->get_results( "SELECT date_format(post_date, '%d %a') as post_day, post_date, count(ID) as post_count from {$wpdb->posts} WHERE post_status IN('publish', 'private_post', 'moderate') AND post_type = 'answer' AND post_date > (NOW() - INTERVAL 1 MONTH) GROUP BY post_day ORDER BY post_date ASC" ); // db call okay, cache ok.

		$days   = array();
		$counts = array();

		foreach ( (array) $results as $r ) {
			$days[]   = $r->post_day;
			$counts[] = $r->post_count;
		}
		?>
		<?php if ( $results ) : ?>
		<?php endif; ?>
		<div class="main">
			<?php
			$answers = asqa_get_answers(
				array(
					'asqa_order_by' => 'newest',
					'showposts'   => 5,
				)
			);
			?>

			<?php if ( asqa_have_answers() ) : ?>
				<ul class="post-list">
					<?php
					while ( asqa_have_answers() ) :
						asqa_the_answer();
						?>
						<li>
							<a target="_blank" href="<?php the_permalink(); ?>"><?php the_title(); ?></a> -
							<span class="posted"><?php the_date(); ?></span>
						</li>
					<?php endwhile; ?>
				</ul>
			<?php endif; ?>

			<?php wp_reset_postdata(); ?>
		</div>
		<?php
	}

	/**
	 * Show items which need attention.
	 */
	public static function smartqa_attn() {
		$q_flagged_count = asqa_total_posts_count( 'question', 'flag' );
		$a_flagged_count = asqa_total_posts_count( 'answer', 'flag' );
		$question_count  = wp_count_posts( 'question', 'readable' );
		$answer_count    = wp_count_posts( 'answer', 'readable' );
		?>
		<div class="main attn">
			<?php if ( $q_flagged_count->total || $question_count->moderate ) : ?>
				<strong><?php esc_attr_e( 'Questions', 'smart-question-answer' ); ?></strong>
				<ul>
					<?php if ( $q_flagged_count->total ) : ?>
						<li>
							<a href=""><i class="apicon-flag"></i>
							<?php
								// translators: Placeholder contains total flagged question count.
								echo esc_attr( sprintf( __( '%d Flagged questions', 'smart-question-answer' ), $q_flagged_count->total ) );
							?>
							</a>
						</li>
					<?php endif; ?>

					<?php if ( $question_count->moderate ) : ?>
						<li>
							<a href=""><i class="apicon-stop"></i>
								<?php
									echo esc_attr(
										// translators: placeholder contains total question awaiting moderation.
										sprintf( __( '%d questions awaiting moderation', 'smart-question-answer' ), $question_count->moderate )
									);
								?>
							</a>
						</li>
					<?php endif; ?>
				</ul>
			<?php else : ?>
				<?php esc_attr_e( 'All looks fine', 'smart-question-answer' ); ?>
			<?php endif; ?>

			<?php if ( $a_flagged_count->total || $answer_count->moderate ) : ?>
				<strong><?php esc_attr_e( 'Answers', 'smart-question-answer' ); ?></strong>
				<ul>
					<?php if ( $a_flagged_count->total ) : ?>
						<li>
							<a href="">
								<i class="apicon-flag"></i>
								<?php
									echo esc_attr(
										sprintf(
											// translators: placeholder contains total flagged answers count.
											__( '%d Flagged answers', 'smart-question-answer' ),
											$a_flagged_count->total
										)
									);
								?>
							</a>
						</li>
					<?php endif; ?>

					<?php if ( $answer_count->moderate ) : ?>
						<li>
							<a href="">
								<i class="apicon-stop"></i>
								<?php
									echo esc_attr(
										sprintf(
											// translators: placeholder contains total awaiting moderation question/answer.
											__( '%d answers awaiting moderation', 'smart-question-answer' ),
											$answer_count->moderate
										)
									);
								?>
							</a>
							</li>
					<?php endif; ?>

				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Total Answer stats.
	 */
	public static function smartqa_astats() {
		global $answers;
		$answer_count = asqa_total_posts_count( 'answer' );
		?>
		<div class="main">
			<ul>
				<li class="post-count">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=answer' ) ); ?>" class="publish">
						<?php
							// translators: placeholder contains total number of published answer count.
							echo esc_attr( sprintf( __( '%d Published', 'smart-question-answer' ), $answer_count->publish ) );
						?>
					</a>
				</li>
				<li class="post-count">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=answer&post_status=private_post' ) ); ?>" class="private">
						<?php
							// translators: placeholder contains total numbers of private posts.
							echo esc_attr( sprintf( __( '%d Private', 'smart-question-answer' ), $answer_count->private_post ) );
						?>
					</a>
				</li>
				<li class="post-count">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=answer&post_status=moderate' ) ); ?>" class="moderate">
						<?php
							// translators: Placeholder contain total awaiting moderation answers count.
							echo esc_attr( sprintf( __( '%d Moderate', 'smart-question-answer' ), $answer_count->moderate ) );
						?>
					</a>
				</li>
			</ul>
		</div>
		<?php
	}

}

SmartQa_Dashboard::init();

global $screen_layout_columns;

$screen      = get_current_screen();
$columns     = absint( $screen->get_columns() );
$columns_css = '';

if ( $columns ) {
	$columns_css = " columns-$columns";
}

?>

<div id="smartqa-metaboxes" class="wrap">
	<h1>SmartQa</h1>
	<div class="welcome-panel" id="welcome-panel">
		<div class="welcome-panel-content">
			<h2><?php esc_attr_e( 'Welcome to SmartQa!', 'smart-question-answer' ); ?></h2>
			<p class="about-description">
				<?php esc_attr_e( 'We’ve assembled some links to get you started:', 'smart-question-answer' ); ?>
			</p>
			<div class="welcome-panel-column-container">
				<div class="welcome-panel-column">
					<h3><?php esc_attr_e( 'Get Started', 'smart-question-answer' ); ?></h3>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=smartqa_options' ) ); ?>" class="button button-primary button-hero">
						<?php esc_attr_e( 'SmartQa Options', 'smart-question-answer' ); ?>
					</a>
				</div>
				<div class="welcome-panel-column">
					<h3><?php esc_attr_e( 'Next Steps', 'smart-question-answer' ); ?></h3>
					<ul>
						<li>
							<a class="welcome-icon welcome-write-blog" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=question' ) ); ?>">
								<?php esc_attr_e( 'Write your first question', 'smart-question-answer' ); ?>
							</a>
						</li>
						<li>
							<a class="welcome-icon welcome-add-page" href="<?php echo esc_url( admin_url( 'admin.php?page=asqa_select_question' ) ); ?>">
								<?php esc_attr_e( 'Post an answer', 'smart-question-answer' ); ?>
							</a>
						</li>
						<li>
							<a class="welcome-icon welcome-view-site" href="<?php echo esc_url( asqa_get_link_to( '/' ) ); ?>">
								<?php esc_attr_e( 'View questions', 'smart-question-answer' ); ?>
							</a>
						</li>
					</ul>
				</div>
				<div class="welcome-panel-column welcome-panel-last">
					<h3><?php esc_attr_e( 'More actions', 'smart-question-answer' ); ?></h3>
					<ul>
						<li>
							<div class="welcome-icon welcome-widgets-menus">
								<?php
								printf(
									// translators: %1 is link to themes %2 is link to addons.
									wp_kses_post( 'Get %1$s or %2$s', 'smart-question-answer' ),
									'<a href="' . esc_url( 'https://extensionforge.com/themes/' ) . '" target="_blank">' . esc_attr__( 'Themes', 'smart-question-answer' ) . '</a>',
									'<a href="' . esc_url( 'https://extensionforge.com/extensions/' ) . '" target="_blank">' . esc_attr__( 'Extensions', 'smart-question-answer' ) . '</a>'
								);
								?>
							</div>
						</li>
						<li>
							<a class="welcome-icon welcome-comments" href="https://extensionforge.com/questions/" target="_blank">
								<?php esc_attr_e( 'Help and Support!', 'smart-question-answer' ); ?>
							</a>
						</li>
						<li>
							<a class="welcome-icon welcome-learn-more" href="https://extensionforge.com/docs/">
								<?php esc_attr_e( 'Documents and FAQ', 'smart-question-answer' ); ?>
							</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div id="dashboard-widgets-wrap">
		<div id="dashboard-widgets" class="metabox-holder<?php echo esc_attr( $columns_css ); ?>">
			<div id="postbox-container-1" class="postbox-container">
				<?php do_meta_boxes( 'smartqa', 'column1', '' ); ?>
			</div>

			<div id="postbox-container-2" class="postbox-container">
				<?php do_meta_boxes( 'smartqa', 'column2', '' ); ?>
			</div>

			<div id="postbox-container-3" class="postbox-container">
				<?php do_meta_boxes( 'smartqa', 'column3', '' ); ?>
			</div>

			<div id="postbox-container-4" class="postbox-container">
				<?php do_meta_boxes( 'smartqa', 'column4', '' ); ?>
			</div>
		</div>
	</div>

</div>
<?php

wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
