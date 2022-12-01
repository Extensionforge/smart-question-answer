<?php
/**
 * SmartQa options page.
 *
 * @link       https://extensionforge.com
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 * @package    SmartQa
 * @subpackage Admin Pages
 * @since      1.0.0
 * @since      1.0.0 Changed title of page from to `SmartQa Settings`.
 * @since      1.0.0 Fixed: CS bugs. Added: new settings "Features".
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check if user have proper rights.
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_attr__( 'Trying to cheat, huh!', 'smart-question-answer' ) );
}

/**
 * Action triggered before outputting SmartQa options page.
 *
 * @since 1.0.0
 */
do_action( 'asqa_before_options_page' );

$features_groups = array(
	'toggle_features' => array(
		'label'    => __( 'Toggle Features', 'smart-question-answer' ),
		'template' => 'toggle-features.php',
		'info'     => __( 'Some features have additional settings which will be visible in the settings sidebar after they are enabled.', 'smart-question-answer' ),
	),
);

$features_groups = apply_filters( 'asqa_settings_menu_features_groups', $features_groups );

$all_options = array(
	'features'      => array(
		'label'  => __( '�? Features', 'smart-question-answer' ),
		'groups' => $features_groups,
	),
	'general'       => array(
		'label'  => __( '⚙ General', 'smart-question-answer' ),
		'groups' => array(
			'pages'      => array(
				'label' => __( 'Pages', 'smart-question-answer' ),
			),
			'permalinks' => array(
				'label' => __( 'Permalinks', 'smart-question-answer' ),
			),
			'layout'     => array(
				'label' => __( 'Layout', 'smart-question-answer' ),
			),
		),
	),

	'postscomments' => array(
		'label' => __( '📃 Posts & Comments', 'smart-question-answer' ),
	),
	'user'          => array(
		'label'  => __( '👨�?💼 User', 'smart-question-answer' ),
		'groups' => array(
			'activity' => array(
				'label' => __( 'Activity', 'smart-question-answer' ),
			),
		),
	),
	'uac'           => array(
		'label'  => __( '🔑 User Access Control', 'smart-question-answer' ),
		'groups' => array(
			'reading' => array(
				'label' => __( 'Reading Permissions', 'smart-question-answer' ),
			),
			'posting' => array(
				'label' => __( 'Posting Permissions', 'smart-question-answer' ),
			),
			'other'   => array(
				'label' => __( 'Other Permissions', 'smart-question-answer' ),
			),
			'roles'   => array(
				'label'    => __( 'Role Editor', 'smart-question-answer' ),
				'template' => 'roles.php',
			),
		),
	),
	'tools'         => array(
		'label'  => __( '🔨 Tools', 'smart-question-answer' ),
		'groups' => array(
			're-count'  => array(
				'label'    => __( 'Re-count', 'smart-question-answer' ),
				'template' => 'recount.php',
			),
			'uninstall' => array(
				'label'    => __( 'Uninstall', 'smart-question-answer' ),
				'template' => 'uninstall.php',
			),
		),
	),
);

$all_options = apply_filters( 'asqa_all_options', $all_options );

/**
 * Action used to register SmartQa options.
 *
 * @since 1.0.0
 * @since Fixed: rewrite rules  not getting flushed on changing permalinks.
 */
do_action( 'asqa_register_options' );

$form_name = asqa_sanitize_unslash( 'asqa_form_name', 'r' );
$updated   = false;

// Process submit form.
if ( ! empty( $form_name ) && smartqa()->get_form( $form_name )->is_submitted() ) {
	$form = smartqa()->get_form( $form_name );

	if ( ! $form->have_errors() ) {
		$values  = $form->get_values();
		$options = get_option( 'smartqa_opt', array() );

		foreach ( $values as $key => $opt ) {
			$options[ $key ] = $opt['value'];
		}

		update_option( 'smartqa_opt', $options );
		wp_cache_delete( 'smartqa_opt', 'ap' );
		wp_cache_delete( 'smartqa_opt', 'ap' );

		// Flush rewrite rules.
		if ( 'form_options_general_pages' === $form_name || 'form_options_general_permalinks' === $form_name ) {
			$main_pages = array_keys( asqa_main_pages() );

			foreach ( $main_pages as $slug ) {
				if ( isset( $values[ $slug ] ) ) {
					$_post = get_post( $values[ $slug ]['value'] );
					asqa_opt( $slug . '_id', $_post->post_name );
				}
			}

			asqa_opt( 'asqa_flush', 'true' );
			flush_rewrite_rules();
		}

		$updated = true;
	}
}

?>

<div id="smartqa" class="wrap">
	<h2 class="admin-title">
		<?php esc_html_e( 'SmartQa Settings', 'smart-question-answer' ); ?>
		<div class="social-links clearfix">
			<a href="https://github.com/smartqa/smartqa" target="_blank">GitHub</a>
			<a href="https://wordpress.org/plugins/smart-question-answer/" target="_blank">WordPress.org</a>
			<a href="https://twitter.com/smartqa_io" target="_blank">@smartqa_io</a>
			<a href="https://www.facebook.com/wp.smartqa" target="_blank">Facebook</a>
		</div>
	</h2>
	<div class="clear"></div>

	<div class="asqa-optionpage-wrap no-overflow">
		<div class="asqa-wrap">
			<?php
				$active_tab = asqa_sanitize_unslash( 'active_tab', 'r', 'general' );
				$form       = asqa_sanitize_unslash( 'asqa_form_name', 'r' );
				$action_url = admin_url( 'admin.php?page=smartqa_options&active_tab=' . $active_tab );
			?>

			<div class="smartqa-options">
				<div class="smartqa-options-tab clearfix">
					<?php
					$active_tab = asqa_sanitize_unslash( 'active_tab', 'r', 'general' );

					foreach ( $all_options as $key => $args ) {
						$tab_url = admin_url( 'admin.php?page=smartqa_options' ) . '&active_tab=' . esc_attr( $key );

						echo '<div class="smartqa-options-menu' . ( $key === $active_tab ? ' smartqa-options-menu-active' : '' ) . '">';
						echo '<a href="' . esc_url( $tab_url ) . '" class="smartqa-options-menu-' . esc_attr( $key ) . '">' . esc_html( $args['label'] ) . '</a>';

						if ( ! empty( $args['groups'] ) && count( $args['groups'] ) > 1 ) {
							echo '<div class="smartqa-options-menu-subs">';
							foreach ( $args['groups'] as $groupkey => $sub_args ) {
								echo '<a href="' . esc_url( $tab_url . '#' . esc_attr( $key . '-' . $groupkey ) ) . '">' . esc_attr( $sub_args['label'] ) . '</a>';
							}
							echo '</div>';
						}

						echo '</div>';

						if ( isset( $args['sep'] ) && $args['sep'] ) {
							echo '<div class="smartqa-options-menu-sep"></div>';
						}
					}

					/**
					 * Action triggered right after SmartQa options tab links.
					 * Can be used to show custom tab links.
					 *
					 * @since 1.0.0
					 */
					do_action( 'asqa_options_tab_links' );
					?>
				</div>
				<div class="smartqa-options-body">
					<div class="asqa-group-options">

						<?php if ( isset( $all_options[ $active_tab ] ) ) : ?>

							<?php if ( true === $updated ) : ?>
								<div class="notice notice-success is-dismissible">
									<p><?php esc_html_e( 'SmartQa option updated successfully!', 'smart-question-answer' ); ?></p>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $all_options[ $active_tab ]['groups'] ) ) : ?>

								<?php foreach ( $all_options[ $active_tab ]['groups'] as $groupkey => $args ) : ?>
									<div class="postbox">
										<h3 id="<?php echo esc_attr( $active_tab . '-' . $groupkey ); ?>"><?php echo esc_attr( $args['label'] ); ?></h3>
										<div class="inside smartqa-options-inside-<?php echo esc_attr( $groupkey ); ?>">
											<?php
											if ( ! empty( $args['info'] ) ) {
												echo '<p class="smartqa-options-info">💡 ' . wp_kses_post( $args['info'] ) . '</p>';
											}

											if ( isset( $args['template'] ) ) {
												include SMARTQA_DIR . '/admin/views/' . $args['template'];
											} else {
												smartqa()->get_form( 'options_' . $active_tab . '_' . $groupkey )->generate(
													array(
														'form_action' => $action_url . '#form_options_' . $active_tab . '_' . $groupkey,
														'ajax_submit' => false,
													)
												);
											}
											?>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<?php $active_option = $all_options[ $active_tab ]; ?>
								<div class="postbox">
									<h3 id="pages-options"><?php echo esc_attr( $active_option['label'] ); ?></h3>
									<div class="inside">
										<?php
										if ( isset( $active_option['template'] ) ) {
											include SMARTQA_DIR . '/admin/views/' . $active_option['template'];
										} else {
											smartqa()->get_form( 'options_' . $active_tab )->generate(
												array(
													'form_action' => $action_url . '#form_options_' . $active_tab,
													'ajax_submit' => false,
												)
											);
										}
										?>
									</div>
								</div>

							<?php endif; ?>

						<?php endif; ?>

						<?php
							/**
							 * Action triggered in SmartQa options page content.
							 * This action can be used to show custom options fields.
							 *
							 * @since 1.0.0
							 */
							do_action( 'asqa_option_page_content' );
						?>
					</div>
				</div>
				<div class="smartqa-options-right">
					<?php if ( 'features' !== $active_tab ) : ?>
						<div class="asqa-features-info">
							<div>
								💡
								<p><?php esc_attr_e( 'Functions such as email notifications, categories, tags and many more are disabled by default. Please carefully check and enable them if needed.', 'smart-question-answer' ); ?></p>
							</div>
							<div>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=smartqa_options&active_tab=features' ) ); ?>" class="button"><?php esc_attr_e( 'Enable features', 'smart-question-answer' ); ?></a>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
/**
 * Action triggered after outputting SmartQa options page.
 *
 * @since 1.0.0
 */
do_action( 'asqa_after_options_page' );

?>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$('.postbox > h3').on('click', function(){
			$(this).closest('.postbox').toggleClass('closed');
		});
		$('#form_options_general_pages-question_page_slug').on('keyup', function(){
			$('.asqa-base-slug').text($(this).val());
		})
	});
</script>
