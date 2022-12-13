<?php
/**
 * Notify admin and users by email.
 *
 * @author     Peter Mertzlin <peter.mertzlin@gmail.com>
 * @copyright  2014 extensionforge.com & Peter Mertzlin
 * @license    GPL-3.0+ https://www.gnu.org/licenses/gpl-3.0.txt
 * @link       https://extensionforge.com
 * @package    SmartQa
 * @subpackage Email Addon
 *
 * @smartqa-addon
 * Addon Name:    Email
 * Addon URI:     https://extensionforge.com
 * Description:   Notify admin and users by email.
 * Author:        Peter Mertzlin
 * Author URI:    https://extensionforge.com
 */

namespace Smartqa\Addons;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use \Smartqa\Addons\Email\Helper as EmailHelper;

// Include captcha field.
require_once SMARTQA_ADDONS_DIR . '/email/class-helper.php';

/**
 * Email addon for SmartQa.
 *
 * @since unknown
 * @since 1.0.0 Renamed class from @see SmartQa_Email_Hooks to SmartQa\Addons\Email.
 */
class Email extends \SmartQa\Singleton {
	/**
	 * Refers to a single instance of this class.
	 *
	 * @var null|object
	 * @since 1.0.0
	 */
	public static $instance = null;

	/**
	 * All emails to send notification.
	 *
	 * @var array
	 */
	public $emails = array();

	/**
	 * Subject of email to send.
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * Email body.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Initialize the class.
	 *
	 * @since unknown
	 * @since 1.0.0 Added new action for `asqa_all_options`.
	 * @since 1.0.0 Added action `asqa_form_options_email_general`.
	 */
	protected function __construct() {
		$this->asqa_default_options();
		smartqa()->add_filter( 'asqa_settings_menu_features_groups', $this, 'load_options' );
		smartqa()->add_filter( 'asqa_form_options_features_email', $this, 'register_option' );
		smartqa()->add_filter( 'asqa_form_email_template', $this, 'register_email_template' );
		smartqa()->add_filter( 'asqa_all_options', $this, 'asqa_all_options', 3, 2 );
		smartqa()->add_action( 'wp_ajax_asqa_email_template', $this, 'asqa_email_template' );
		smartqa()->add_action( 'asqa_ajax_form_email_template', $this, 'save_email_template_form', 11 );
		smartqa()->add_action( 'asqa_email_default_template_new_question', $this, 'template_new_question' );
		smartqa()->add_action( 'asqa_email_default_template_new_answer', $this, 'template_new_answer' );
		smartqa()->add_action( 'asqa_email_default_template_select_answer', $this, 'template_select_answer' );
		smartqa()->add_action( 'asqa_email_default_template_new_comment', $this, 'template_new_comment' );
		smartqa()->add_action( 'asqa_email_default_template_edit_question', $this, 'template_edit_question' );
		smartqa()->add_action( 'asqa_email_default_template_edit_answer', $this, 'template_edit_answer' );
		smartqa()->add_action( 'asqa_email_default_template_trash_question', $this, 'template_trash_question' );
		smartqa()->add_action( 'asqa_email_default_template_trash_answer', $this, 'template_trash_answer' );
		smartqa()->add_action( 'asqa_email_form_allowed_tags', $this, 'form_allowed_tags' );

		smartqa()->add_filter( 'comment_notification_recipients', $this, 'default_recipients', 10, 2 );

		smartqa()->add_action( 'asqa_after_new_question', $this, 'asqa_after_new_question' );
		smartqa()->add_action( 'asqa_after_new_answer', $this, 'asqa_after_new_answer' );
		smartqa()->add_action( 'asqa_select_answer', $this, 'select_answer' );
		smartqa()->add_action( 'asqa_publish_comment', $this, 'new_comment' );
		smartqa()->add_action( 'asqa_processed_update_question', $this, 'asqa_after_update_question', 10, 2 );
		smartqa()->add_action( 'asqa_processed_update_answer', $this, 'asqa_after_update_answer', 10, 2 );
		smartqa()->add_action( 'asqa_trash_question', $this, 'asqa_trash_question', 10, 2 );
		smartqa()->add_action( 'asqa_trash_answer', $this, 'asqa_trash_answer', 10, 2 );
	}

	/**
	 * Return empty recipients for default comment notifications.
	 *
	 * @param array   $recipients Array of recipients.
	 * @param integer $comment_id Comment ID.
	 * @return array
	 */
	public function default_recipients( $recipients, $comment_id ) {
		$_comment = get_comment( $comment_id );

		if ( 'smartqa' === $_comment->comment_type ) {
			return array();
		}

		return $recipients;
	}

	/**
	 * Append default options
	 *
	 * @since   4.0.0
	 */
	public function asqa_default_options() {
		$defaults = array(
			'email_admin_emails'         => get_option( 'admin_email' ),
			'email_admin_new_question'   => true,
			'email_admin_new_answer'     => true,
			'email_admin_new_comment'    => true,
			'email_admin_edit_question'  => true,
			'email_admin_edit_answer'    => true,
			'email_admin_trash_question' => true,
			'email_admin_trash_answer'   => true,
			'email_user_new_question'    => true,
			'email_user_new_answer'      => true,
			'email_user_select_answer'   => true,
			'email_user_new_comment'     => true,
			'email_user_edit_question'   => true,
			'email_user_edit_answer'     => true,
		);

		$defaults['trash_answer_email_subject'] = __( 'An answer is trashed by {user}', 'smart-question-answer' );
		$defaults['trash_answer_email_body']    = __( "Hello!\nAnswer on '{question_title}' is trashed by {user}.\n", 'smart-question-answer' );

		asqa_add_default_options( $defaults );
	}

	/**
	 * Register Categories options.
	 *
	 * @param array $groups Group tabs.
	 * @since 1.0.0
	 */
	public function load_options( $groups ) {
		$groups['email'] = array(
			'label' => __( 'Email', 'smart-question-answer' ),
			'info'  => sprintf(
				// translators: placeholder contains link to email templates.
				__( 'Email templates can be customized here %s.', 'smart-question-answer' ),
				'<a href="' . admin_url( 'admin.php?page=smartqa_options&active_tab=emails' ) . '">' . esc_attr__( 'Customize email templates', 'smart-question-answer' ) . '</a>'
			),
		);

		return $groups;
	}

	/**
	 * Register options
	 */
	public function register_option() {
		$opt = asqa_opt();

		$form = array(
			'fields' => array(
				'sep1'                       => array(
					'html' => '<h3>' . __( 'Admin Notifications', 'smart-question-answer' ) . '<p>' . __( 'Select types of notification which will be sent to admin.', 'smart-question-answer' ) . '</p></h3>',
				),
				'email_admin_emails'         => array(
					'label' => __( 'Admin email(s)', 'smart-question-answer' ),
					'desc'  => __( 'Email where all admin notification will be sent. It can have multiple emails separated by comma.', 'smart-question-answer' ),
					'value' => $opt['email_admin_emails'],
				),
				'email_admin_new_question'   => array(
					'label' => __( 'New question', 'smart-question-answer' ),
					'desc'  => __( 'Send new question notification to admin.', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_admin_new_question'],
				),
				'email_admin_new_answer'     => array(
					'label' => __( 'New answer', 'smart-question-answer' ),
					'desc'  => __( 'Send new answer notification to admin.', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_admin_new_answer'],
				),
				'email_admin_new_comment'    => array(
					'label' => __( 'New comment', 'smart-question-answer' ),
					'desc'  => __( 'Send new comment notification to admin.', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_admin_new_comment'],
				),
				'email_admin_edit_question'  => array(
					'label' => __( 'Edit question', 'smart-question-answer' ),
					'desc'  => __( 'Send notification to admin when question is edited.', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_admin_edit_question'],
				),
				'email_admin_edit_answer'    => array(
					'label' => __( 'Edit answer', 'smart-question-answer' ),
					'desc'  => __( 'Send email to admin when answer is edited.', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_admin_edit_answer'],
				),
				'email_admin_trash_question' => array(
					'label' => __( 'Delete question', 'smart-question-answer' ),
					'desc'  => __( 'Send email to admin when question is trashed.', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_admin_trash_question'],
				),
				'email_admin_trash_answer'   => array(
					'label' => __( 'Delete answer', 'smart-question-answer' ),
					'desc'  => __( 'Send email to admin when answer is trashed.', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_admin_trash_answer'],
				),
				'sep2'                       => array(
					'html' => '<h3>' . __( 'User Notifications', 'smart-question-answer' ) . '<p>' . __( 'Select the types of notification which will be sent to user.', 'smart-question-answer' ) . '</p></h3>',
				),
				'email_user_new_question'    => array(
					'label' => __( 'New question', 'smart-question-answer' ),
					'desc'  => __( 'Send new question notification to user?', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_user_new_question'],
				),
				'email_user_new_answer'      => array(
					'label' => __( 'New answer', 'smart-question-answer' ),
					'desc'  => __( 'Send new answer notification to user?', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_user_new_answer'],
				),
				'email_user_new_comment'     => array(
					'label' => __( 'New comment', 'smart-question-answer' ),
					'desc'  => __( 'Send new comment notification to user?', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_user_new_comment'],
				),
				'email_user_edit_question'   => array(
					'label' => __( 'Edit question', 'smart-question-answer' ),
					'desc'  => __( 'Send edit question notification to user?', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_user_edit_question'],
				),
				'email_user_edit_answer'     => array(
					'label' => __( 'Edit answer', 'smart-question-answer' ),
					'desc'  => __( 'Send edit answer notification to user?', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_user_edit_answer'],
				),
				'email_user_select_answer'   => array(
					'label' => __( 'Answer selected', 'smart-question-answer' ),
					'desc'  => __( 'Send notification to user when their answer get selected?', 'smart-question-answer' ),
					'type'  => 'checkbox',
					'value' => $opt['email_user_select_answer'],
				),
			),
		);

		return $form;
	}

	/**
	 * Register email templates form.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function register_email_template() {
		$form = array(
			'fields' => array(
				'subject' => array(
					'label' => __( 'Email subject', 'smart-question-answer' ),
				),
				'body'    => array(
					'label'       => __( 'Email body', 'smart-question-answer' ),
					'type'        => 'editor',
					'editor_args' => array(
						'quicktags' => true,
						'tinymce'   => true,
					),
				),
				'tags'    => array(
					'html' => '<label class="asqa-form-label" for="form_email_template-allowed_tags">' . __( 'Allowed tags', 'smart-question-answer' ) . '</label><div class="asqa-email-allowed-tags">' . apply_filters( 'asqa_email_form_allowed_tags', '' ) . '</div>',
				),
			),
		);

		return $form;
	}

	/**
	 * Get admin emails to notify based on option.
	 *
	 * @param string $opt Option key.
	 * @return false|array Return array of emails else false.
	 */
	public function get_admin_emails( $opt ) {
		$current_user = wp_get_current_user();

		if ( asqa_opt( $opt ) ) {
			$admin_emails = explode( ',', preg_replace( '/\s+/', '', asqa_opt( 'email_admin_emails' ) ) );
			// Don't bother if current user is admin.
			if ( empty( $admin_emails ) || in_array( $current_user->user_email, $admin_emails, true ) ) {
				return false;
			}

			return $admin_emails;
		}

		return false;
	}

	/**
	 * Send email to admin when new question is created.
	 *
	 * @param  integer $question_id Question ID.
	 * @since 1.0
	 * @since 1.0.0 Updated to use new EmailHelper class.
	 */
	public function asqa_after_new_question( $question_id ) {
		$args = array();

		global $wpdb;
		$table_name = $wpdb->prefix . 'term_relationships';
		$admin_emails = $this->get_admin_emails( 'email_admin_new_question' );
		$cat = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE object_id='%d'", $question_id));
		$category_id = $cat->term_taxonomy_id;
		
		$table_name = $wpdb->prefix . 'asqa_moderators';
		$mods = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE cat_id='%d'", $category_id));

		foreach($mods as $moderator) {
			$modid = $moderator->user_id;
			$user = get_user_by('id', $modid);
			$modemail = $user->user_email;
			$insert = asqa_new_subscriber( $modid, 'question', $question_id );
			$admin_emails[] = $modemail;
		}

		$admin_emails = array_unique($admin_emails);

		if ( ! empty( $admin_emails ) ) {
			$args['users'] = $admin_emails;
		}

		// Return if no users.
		if ( empty( $args['users'] ) ) {
			return;
		}

		$question     = asqa_get_post( $question_id );
		$args['tags'] = array(
			'{asker}'            => asqa_user_display_name( $question->post_author ),
			'{question_title}'   => $question->post_title,
			'{question_link}'    => wp_get_shortlink( $question->ID ),
			'{question_content}' => apply_filters( 'the_content', $question->post_content ),
			'{question_excerpt}' => asqa_truncate_chars( wp_strip_all_tags( $question->post_content ), 100 ),
		);

		$email = new EmailHelper( 'new_question', $args );
		$email->send_emails();
	}

	/**
	 * Send email after new answer.
	 *
	 * @param integer $answer_id Answer ID.
	 * @since 1.0.0 Updated to use new email class.
	 */
	public function asqa_after_new_answer( $answer_id ) {
		$current_user = wp_get_current_user();
		$args         = array(
			'users' => array(),
		);

		$admin_emails = $this->get_admin_emails( 'email_admin_new_answer' );
		if ( ! empty( $admin_emails ) ) {
			$args['users'] = $admin_emails;
		}

		$answer = asqa_get_post( $answer_id );

		if ( asqa_opt( 'email_user_new_answer' ) && 'private_post' !== $answer->post_status && 'moderate' !== $answer->post_status ) {
			$subscribers = asqa_get_subscribers(
				array(
					'subs_event'  => 'question',
					'subs_ref_id' => $answer->post_parent,
				)
			);

			foreach ( (array) $subscribers as $s ) {
				if ( asqa_user_can_view_post( $answer ) && $s->user_email !== $current_user->user_email ) {
					$args['users'][] = $s->user_email;
				}
			}
		}

		$args['tags'] = array(
			'{answerer}'       => asqa_user_display_name( $answer->post_author ),
			'{question_title}' => get_the_title( $answer->post_parent ),
			'{answer_link}'    => wp_get_shortlink( $answer->ID ),
			'{answer_content}' => $answer->post_content,
			'{answer_excerpt}' => asqa_truncate_chars( wp_strip_all_tags( $answer->post_content ), 100 ),
		);

		$email = new EmailHelper( 'new_answer', $args );
		$email->send_emails();
	}

	/**
	 * Notify answer author that his answer is selected as best.
	 *
	 * @param  object $_post Selected answer object.
	 * @since 1.0.0 Updated to use new email class.
	 */
	public function select_answer( $_post ) {
		if ( get_current_user_id() === $_post->post_author || ! asqa_opt( 'email_user_select_answer' ) ) {
			return;
		}

		$args = array(
			'users' => array(
				get_the_author_meta( 'email', $_post->post_author ),
			),
		);

		$args['tags'] = array(
			'{selector}'       => asqa_user_display_name( get_current_user_id() ),
			'{question_title}' => $_post->post_title,
			'{answer_link}'    => wp_get_shortlink( $_post->ID ),
			'{answer_content}' => $_post->post_content,
			'{answer_excerpt}' => asqa_truncate_chars( wp_strip_all_tags( $_post->post_content ), 100 ),
		);

		$email = new EmailHelper( 'select_answer', $args );
		$email->send_emails();
	}

	/**
	 * Notify admin on new comment and is not approved.
	 *
	 * @param object $comment Comment object.
	 * @since 1.0.0 Updated to use new email class.
	 */
	public function new_comment( $comment ) {
		$args = array();

		$admin_emails = $this->get_admin_emails( 'email_admin_new_comment' );

		if ( ! empty( $admin_emails ) ) {
			$args['users'] = $admin_emails;
		}

		if ( asqa_opt( 'email_user_new_comment' ) ) {
			$current_user = wp_get_current_user();
			$post         = asqa_get_post( $comment->comment_post_ID );
			$subscribers  = asqa_get_subscribers( array( 'subs_event' => 'comment_' . $comment->comment_post_ID ) );

			if ( get_current_user_id() !== $post->post_author ) {
				$args['users'][] = $post->post_author;
			}

			foreach ( (array) $subscribers as $s ) {
				if ( asqa_user_can_view_post( $post ) && $s->user_email !== $current_user->user_email ) {
					$args['users'][] = $s->user_email;
				}
			}
		}

		// Check if have emails before proceeding.
		if ( empty( $args['users'] ) ) {
			return;
		}

		$args['tags'] = array(
			'{commenter}'       => asqa_user_display_name( $comment->user_id ),
			'{question_title}'  => $post->post_title,
			'{comment_link}'    => get_comment_link( $comment ),
			'{comment_content}' => $comment->comment_content,
		);

		$email = new EmailHelper( 'new_comment', $args );
		$email->send_emails();
	}

	/**
	 * Notify after question get updated.
	 *
	 * @param int    $post_id  Question id.
	 * @param object $question Question object.
	 *
	 * @since 1.0.0 Updated to use new email class.
	 */
	public function asqa_after_update_question( $post_id, $question ) {
		if ( in_array( $question->post_status, array( 'trash', 'draft' ), true ) ) {
			return;
		}

		$args = array();

		$admin_emails = $this->get_admin_emails( 'email_admin_edit_question' );
		if ( ! empty( $admin_emails ) ) {
			$args['users'] = $admin_emails;
		}

		$current_user = wp_get_current_user();
		$email        = new EmailHelper( 'edit_question', $args );

		$subscribers = asqa_get_subscribers(
			array(
				'subs_event'  => 'question',
				'subs_ref_id' => $question->ID,
			)
		);

		// Exclude current author.
		if ( get_current_user_id() !== (int) $question->post_author ) {
			$email->add_user( $question->post_author );
		}

		foreach ( (array) $subscribers as $s ) {
			if ( asqa_user_can_view_post( $question ) && ! empty( $s->user_email ) &&
				$s->user_email !== $current_user->user_email ) {
				$email->add_email( $s->user_email );
			}
		}

		$email->add_template_tags(
			array(
				'asker'            => asqa_user_display_name( $question->post_author ),
				'editor'           => asqa_user_display_name( get_current_user_id() ),
				'question_title'   => $question->post_title,
				'question_link'    => get_permalink( $question->ID ),
				'question_content' => $question->post_content,
				'question_excerpt' => asqa_truncate_chars( wp_strip_all_tags( $question->post_content ), 100 ),
			)
		);

		$email->send_emails();
	}

	/**
	 * Notify users after answer gets updated.
	 *
	 * @param int    $answer_id Answer id.
	 * @param object $answer    Event post object.
	 */
	public function asqa_after_update_answer( $answer_id, $answer ) {
		if ( in_array( $answer->post_status, array( 'trash', 'draft' ), true ) ) {
			return;
		}

		$current_user = wp_get_current_user();

		$args = array();

		$admin_emails = $this->get_admin_emails( 'email_admin_edit_answer' );
		if ( ! empty( $admin_emails ) ) {
			$args['users'] = $admin_emails;
		}

		$email = new EmailHelper( 'edit_answer', $args );

		$a_subscribers = (array) asqa_get_subscribers( array( 'subs_event' => 'answer_' . $answer->post_parent ) );
		$q_subscribers = (array) asqa_get_subscribers(
			array(
				'subs_event'  => 'question',
				'subs_ref_id' => $answer->post_parent,
			)
		);
		$subscribers   = array_merge( $a_subscribers, $q_subscribers );

		// Exclude current post author email.
		if ( ! empty( $answer->post_author ) ) {
			$post_author = get_user_by( 'id', $answer->post_author );

			if ( $post_author && ! asqa_in_array_r( $post_author->data->user_email, $subscribers ) &&
				$current_user->user_email !== $post_author->data->user_email ) {
				$email->add_email( $post_author->data->user_email );
			}
		}

		foreach ( (array) $subscribers as $s ) {
			if ( asqa_user_can_view_post( $answer ) && ! empty( $s->user_email ) &&
				$s->user_email !== $current_user->user_email ) {
					$email->add_email( $s->user_email );
			}
		}

		$email->add_template_tags(
			array(
				'answerer'       => asqa_user_display_name( $answer->post_author ),
				'editor'         => asqa_user_display_name( get_current_user_id() ),
				'question_title' => $answer->post_title,
				'answer_link'    => get_permalink( $answer->ID ),
				'answer_content' => $answer->post_content,
			)
		);

		$email->send_emails();
	}

	/**
	 * Notify admin on trashing a question.
	 *
	 * @param integer $post_id Post ID.
	 * @param object  $_post Post object.
	 */
	public function asqa_trash_question( $post_id, $_post ) {
		if ( ! asqa_opt( 'email_admin_trash_question' ) ) {
			return;
		}

		$args         = array();
		$current_user = wp_get_current_user();
		$admin_emails = $this->get_admin_emails( 'email_admin_trash_question' );

		if ( ! empty( $admin_emails ) ) {
			$args['users'] = $admin_emails;
		}

		$args['tags'] = array(
			'{user}'           => asqa_user_display_name( get_current_user_id() ),
			'{question_title}' => $_post->post_title,
			'{question_link}'  => wp_get_shortlink( $_post->ID ),
		);

		$email = new EmailHelper( 'trash_question', $args );
		$email->send_emails();
	}

	/**
	 * Notify admin on trashing a answer.
	 *
	 * @param integer $post_id Post ID.
	 * @param object  $_post Post object.
	 */
	public function asqa_trash_answer( $post_id, $_post ) {
		if ( ! asqa_opt( 'email_admin_trash_answer' ) ) {
			return;
		}

		$args         = array();
		$current_user = wp_get_current_user();
		$admin_emails = $this->get_admin_emails( 'email_admin_trash_question' );

		if ( ! empty( $admin_emails ) ) {
			$args['users'] = $admin_emails;
		}

		$args['tags'] = array(
			'{user}'           => asqa_user_display_name( get_current_user_id() ),
			'{question_title}' => $_post->post_title,
			'{question_link}'  => wp_get_shortlink( $_post->post_parent ),
		);

		$email = new EmailHelper( 'trash_question', $args );
		$email->send_emails();
	}

	/**
	 * Add reputation events option in SmartQa options.
	 *
	 * @param array $all_options Options.
	 * @return array
	 * @since 1.0.0
	 */
	public function asqa_all_options( $all_options ) {
		$all_options['emails'] = array(
			'label'    => __( '📧 Email Templates', 'smart-question-answer' ),
			'template' => 'emails.php',
		);

		return $all_options;
	}

	/**
	 * Save email templates.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function save_email_template_form() {
		$editing       = false;
		$template_slug = asqa_sanitize_unslash( 'template', 'r' );
		$template_id   = (int) asqa_opt( 'email_template_' . $template_slug );

		$form   = smartqa()->get_form( 'email_template' );
		$values = $form->get_values();

		// Check nonce and is valid form.
		if ( ! $form->is_submitted() || ! current_user_can( 'manage_options' ) ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array( 'message' => __( 'Trying to cheat?!', 'smart-question-answer' ) ),
				)
			);
		}

		$post_args = array(
			'post_title'   => $values['subject']['value'],
			'post_content' => $values['body']['value'],
		);

		$_post = get_post( $template_id );
		if ( $_post ) {
			$editing         = true;
			$post_args['ID'] = $_post->ID;
			// Check if valid post type and user can edit.
			if ( $_post && 'smartqa_email' !== $_post->post_type ) {
				asqa_ajax_json( 'something_wrong' );
			}
		}

		// Post status.
		$post_args['post_status'] = 'publish';

		if ( $form->have_errors() ) {
			asqa_ajax_json(
				array(
					'success'       => false,
					'snackbar'      => array( 'message' => __( 'Unable to post answer.', 'smart-question-answer' ) ),
					'form_errors'   => $form->errors,
					'fields_errors' => $form->get_fields_errors(),
				)
			);
		}

		if ( ! $editing ) {
			$post_args['post_type'] = 'smartqa_email';
			$post_id                = wp_insert_post( $post_args, true );
		} else {
			$post_id = wp_update_post( $post_args, true );
		}

		// If error return and send error message.
		if ( is_wp_error( $post_id ) ) {
			asqa_ajax_json(
				array(
					'success'  => false,
					'snackbar' => array(
						'message' => sprintf(
							// Translators: placeholder contain error message.
							__( 'Unable to save template. Error: %s', 'smart-question-answer' ),
							$post_id->get_error_message()
						),
					),
				)
			);
		}

		asqa_opt( 'email_template_' . $template_slug, $post_id );

		$form->after_save(
			false,
			array(
				'post_id' => $post_id,
			)
		);

		// Clear temporary images.
		if ( $post_id ) {
			asqa_clear_unattached_media();
		}

		asqa_ajax_json(
			array(
				'success'  => true,
				'snackbar' => array(
					'message' => __( 'Post updated successfully', 'smart-question-answer' ),
				),
				'post_id'  => $post_id,
			)
		);
	}

	/**
	 * Ajax callback for loading email template form.
	 *
	 * @return void
	 * @since 1.0.0
	 * @since 1.0.0 Check for permission.
	 */
	public function asqa_email_template() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}

		check_ajax_referer( 'asqa_email_template', '__nonce' );
		$template_slug = asqa_sanitize_unslash( 'template', 'r' );

		\SmartQa\Addons\Email::init()->template_form( $template_slug );
		die();
	}

	/**
	 * Generate email template form.
	 *
	 * @param string $active Currently active template.
	 * @return void
	 * @since 1.0.0
	 */
	public function template_form( $active ) {
		$form     = smartqa()->get_form( 'email_template' );
		$template = get_post( asqa_opt( 'email_template_' . $active ) );

		if ( $template ) {
			$form->set_values(
				array(
					'subject' => $template->post_title,
					'body'    => $template->post_content,
				)
			);
		} else {
			$default_template = $this->get_default_template( $active );
			$form->set_values(
				array(
					'subject' => $default_template['subject'],
					'body'    => $default_template['body'],
				)
			);
		}

		$form->generate(
			array(
				'ajax_submit'   => true,
				'hidden_fields' => array(
					array(
						'name'  => 'action',
						'value' => 'asqa_ajax',
					),
					array(
						'name'  => 'asqa_ajax_action',
						'value' => 'form_email_template',
					),
					array(
						'name'  => 'template',
						'value' => $active,
					),
				),
			)
		);
	}

	/**
	 * Return default template for an event.
	 *
	 * @param string $event Event name.
	 * @return array.
	 */
	public function get_default_template( $event ) {
		$template = array(
			'subject' => '',
			'body'    => '',
		);

		/**
		 * Used for registering a default email template for an event.
		 *
		 * @param array $template Template.
		 * @since 1.0.0
		 */
		return apply_filters( "asqa_email_default_template_{$event}", $template );
	}

	/**
	 * Add default new question email template.
	 *
	 * @param array $template Template subject and body.
	 * @return array
	 * @since 1.0.0
	 */
	public function template_new_question( $template ) {
		$template['subject'] = __( '{asker} have posted a new question', 'smart-question-answer' );
		$body                = '';
		$body               .= '<div class="asqa-email-event">';
		$body               .= __( 'A new question is posted by', 'smart-question-answer' ) . ' <b class="user-name">{asker}</b>';
		$body               .= '</div>';
		$body               .= '<div class="asqa-email-body">';
		$body               .= '<h1 class="asqa-email-title"><a href="{question_link}">{question_title}</a></h1>';
		$body               .= '<div class="asqa-email-content">';
		$body               .= '{question_content}';
		$body               .= '</div>';
		$body               .= '</div>';
		$template['body']    = $body;

		return $template;
	}

	/**
	 * Add default new answer email template.
	 *
	 * @param array $template Template subject and body.
	 * @return array
	 * @since 1.0.0
	 */
	public function template_new_answer( $template ) {
		$template['subject'] = __( 'New answer posted by {answerer}', 'smart-question-answer' );
		$body                = '';
		$body               .= '<div class="asqa-email-event">';
		$body               .= __( 'A new answer is posted by', 'smart-question-answer' ) . ' <b class="user-name">{answerer}</b>';
		$body               .= '</div>';
		$body               .= '<div class="asqa-email-body">';
		$body               .= '<h1 class="asqa-email-title"><a href="{answer_link}">{question_title}</a></h1>';
		$body               .= '<div class="asqa-email-content">';
		$body               .= '{answer_excerpt} ';
		$body               .= '</div>';
		$body               .= '</div>';
		$template['body']    = $body;

		return $template;
	}

	/**
	 * Add default selected answer email template.
	 *
	 * @param array $template Template subject and body.
	 * @return array
	 * @since 1.0.0
	 */
	public function template_select_answer( $template ) {
		$template['subject'] = __( 'Your answer is selected as best!', 'smart-question-answer' );
		$body                = '';
		$body               .= '<div class="asqa-email-event">';
		$body               .= __( 'Your answer is selected as best by ', 'smart-question-answer' ) . ' <b class="user-name">{selector}</b>';
		$body               .= '</div>';
		$body               .= '<div class="asqa-email-body">';
		$body               .= '<h1 class="asqa-email-title"><a href="{answer_link}">{question_title}</a></h1>';
		$body               .= '<div class="asqa-email-content">';
		$body               .= '{answer_content}';
		$body               .= '</div>';
		$body               .= '</div>';
		$template['body']    = $body;

		return $template;
	}

	/**
	 * Add default selected answer email template.
	 *
	 * @param array $template Template subject and body.
	 * @return array
	 * @since 1.0.0
	 */
	public function template_new_comment( $template ) {
		$template['subject'] = __( 'New comment by {commenter}', 'smart-question-answer' );
		$body                = '';
		$body               .= '<div class="asqa-email-event">';
		$body               .= __( 'A new comment posted by', 'smart-question-answer' ) . ' <b class="user-name">{commenter}</b>';
		$body               .= '</div>';
		$body               .= '<div class="asqa-email-body">';
		$body               .= '<h1 class="asqa-email-title"><a href="{comment_link}">{question_title}</a></h1>';
		$body               .= '<div class="asqa-email-content">';
		$body               .= '{comment_content}';
		$body               .= '</div>';
		$body               .= '</div>';
		$template['body']    = $body;

		return $template;
	}

	/**
	 * Add default edit question email template.
	 *
	 * @param array $template Template subject and body.
	 * @return array
	 * @since 1.0.0
	 */
	public function template_edit_question( $template ) {
		$template['subject'] = __( 'A question is edited by {editor}', 'smart-question-answer' );
		$body                = '';
		$body               .= '<div class="asqa-email-event">';
		$body               .= __( 'A question is edited by', 'smart-question-answer' ) . ' <b class="user-name">{editor}</b>';
		$body               .= '</div>';
		$body               .= '<div class="asqa-email-body">';
		$body               .= '<h1 class="asqa-email-title"><a href="{question_link}">{question_title}</a></h1>';
		$body               .= '<div class="asqa-email-content">';
		$body               .= '{question_content}';
		$body               .= '</div>';
		$body               .= '</div>';
		$template['body']    = $body;

		return $template;
	}

	/**
	 * Add default edit answer email template.
	 *
	 * @param array $template Template subject and body.
	 * @return array
	 * @since 1.0.0
	 */
	public function template_edit_answer( $template ) {
		$template['subject'] = __( 'A answer is edited by {editor}', 'smart-question-answer' );
		$body                = '';
		$body               .= '<div class="asqa-email-event">';
		$body               .= __( 'A answer is edited by', 'smart-question-answer' ) . ' <b class="user-name">{editor}</b>';
		$body               .= '</div>';
		$body               .= '<div class="asqa-email-body">';
		$body               .= '<h1 class="asqa-email-title"><a href="{answer_link}">{question_title}</a></h1>';
		$body               .= '<div class="asqa-email-content">';
		$body               .= '{answer_content}';
		$body               .= '</div>';
		$body               .= '</div>';
		$template['body']    = $body;

		return $template;
	}

	/**
	 * Add default trash question email template.
	 *
	 * @param array $template Template subject and body.
	 * @return array
	 * @since 1.0.0
	 */
	public function template_trash_question( $template ) {
		$template['subject'] = __( 'A question is trashed by {user}', 'smart-question-answer' );
		$body                = '';
		$body               .= '<div class="asqa-email-event">';
		$body               .= __( 'A question is trashed by', 'smart-question-answer' ) . ' <b class="user-name">{user}</b>';
		$body               .= '</div>';
		$body               .= '<div class="asqa-email-body">';
		$body               .= '<h1 class="asqa-email-title"><a href="{question_link}">{question_title}</a></h1>';
		$body               .= '</div>';
		$template['body']    = $body;

		return $template;
	}

	/**
	 * Add default trash answer email template.
	 *
	 * @param array $template Template subject and body.
	 * @return array
	 * @since 1.0.0
	 */
	public function template_trash_answer( $template ) {
		$template['subject'] = __( 'An answer is trashed by {user}', 'smart-question-answer' );
		$body                = '';
		$body               .= '<div class="asqa-email-event">';
		$body               .= __( 'An answer is trashed by', 'smart-question-answer' ) . ' <b class="user-name">{user}</b>';
		$body               .= '</div>';
		$body               .= '<div class="asqa-email-body">';
		$body               .= '<h1 class="asqa-email-title"><a href="{answer_link}">{question_title}</a></h1>';
		$body               .= '</div>';
		$template['body']    = $body;

		return $template;
	}

	/**
	 * Filter allowed tags.
	 */
	public function form_allowed_tags() {
		$active = asqa_isset_post_value( 'template', 'new_question' );

		$tags = array(
			'site_name',
			'site_url',
			'site_description',
		);

		$type_tags = array();

		if ( in_array( $active, array( 'new_question', 'edit_question' ), true ) ) {
			$type_tags = array(
				'asker',
				'question_title',
				'question_link',
				'question_content',
				'question_excerpt',
			);
		} elseif ( in_array( $active, array( 'new_answer', 'edit_answer' ), true ) ) {
			$type_tags = array(
				'answerer',
				'question_title',
				'answer_link',
				'answer_content',
				'answer_excerpt',
			);
		} elseif ( 'select_answer' === $active ) {
			$type_tags = array(
				'selector',
				'question_title',
				'answer_link',
				'answer_content',
				'answer_excerpt',
			);
		} elseif ( 'new_comment' === $active ) {
			$type_tags = array(
				'commenter',
				'question_title',
				'comment_link',
				'comment_content',
			);
		}

		$tags = array_merge( $tags, $type_tags );

		$html = '';
		foreach ( $tags as $tag ) {
			$html .= '<pre>{' . esc_html( $tag ) . '}</pre>';
		}

		return $html;
	}
}

// Init addon.
Email::init();
