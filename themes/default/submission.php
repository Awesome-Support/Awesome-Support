<?php
/**
 * This is a built-in template file. If you need to customize it, please,
 * DO NOT modify this file directly. Instead, copy it to your theme's directory
 * and then modify the code. If you modify this file directly, your changes
 * will be overwritten during next update of the plugin.
 */

global $post;
?>

<div class="wpas wpas-submit-ticket">

	<?php

	mumei_ayuda_get_template( 'partials/ticket-navigation' );

	do_action( 'mumei_ayuda_ticket_submission_form_outside_top' );
	?>

	<form class="wpas-form new2" role="form" method="post" action="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" id="wpas-new-ticket" enctype="multipart/form-data">

		<?php
		/**
		 * The mumei_ayuda_submission_form_inside_top has to be placed
		 * inside the form, right in between the form opening tag
		 * and the first field being rendered.
		 *
		 * @since  4.4.0
		 */
		do_action( 'mumei_ayuda_submission_form_inside_top' );

		/**
		 * Filter the subject field arguments
		 *
		 * @since 3.2.0
		 *
		 * Note the use of the The mumei_ayuda_submission_form_inside_before
		 * action hook.  It will be placed inside the form, usually
		 * right in between the form opening tag
		 * and the subject field.
		 *
		 * However, the hook can be moved if the subject field is set
		 * to a different sort order in the custom fields array.
		 *
		 * The mumei_ayuda_submission_form_inside_after_subject action
		 * hook is also declared as a post-render hook.
		 */
		$maximum_ticket_subject_front_end = mumei_ayuda_get_option( 'maximum_ticket_subject_front_end' );
		$subject_args = apply_filters( 'mumei_ayuda_subject_field_args', array(
			'name' => 'title',
			'args' => array(
				'required'   => true,
				'field_type' => 'text',
				'label'      => __( 'Subject', 'ayuda-help-desk' ),
				'sanitize'   => 'sanitize_text_field',
				'order'		 => '-2',
				'pre_render_action_hook_fe'		=> 'mumei_ayuda_submission_form_inside_before_subject',
				'post_render_action_hook_fe'	=> 'mumei_ayuda_submission_form_inside_after_subject',
				'maxlength' => ( (int)$maximum_ticket_subject_front_end > 0 ) ? (int)$maximum_ticket_subject_front_end : '',
			)
		) );

		mumei_ayuda_add_custom_field($subject_args['name'], $subject_args['args']);

		/**
		 * Filter the description field arguments
		 *
		 * @since 3.2.0
		 */
		$body_args = apply_filters( 'mumei_ayuda_description_field_args', array(
			'name' => 'message',
			'args' => array(
				'required'   => true,
				'field_type' => 'wysiwyg',
				'label'      => __( 'Description', 'ayuda-help-desk' ),
				'sanitize'   => 'sanitize_text_field',
				'order'		 => '-1',
				'pre_render_action_hook_fe'		=> 'mumei_ayuda_submission_form_inside_before_description',
				'post_render_action_hook_fe'	=> 'mumei_ayuda_submission_form_inside_after_description',
			)
		) );

		mumei_ayuda_add_custom_field($body_args['name'], $body_args['args']);

		/**
		 * Declare an action hook just before rendering all the fields...
		 */
		do_action( 'mumei_ayuda_submission_form_pre_render_fields' );

		/* All custom fields have been declared so render them all */
		WPAS()->custom_fields->submission_form_fields();

		/**
		 * Declare an action hook just after rendering all the fields...
		 */
		do_action( 'mumei_ayuda_submission_form_post_render_fields' );


		/**
		 * The mumei_ayuda_submission_form_inside_before hook has to be placed
		 * right before the submission button.
		 *
		 * @since  3.0.0
		 */
		do_action( 'mumei_ayuda_submission_form_inside_before_submit' );

		wp_nonce_field( 'new_ticket', 'mumei_ayuda_nonce', true, true );
		mumei_ayuda_make_button( __( 'Submit ticket', 'ayuda-help-desk' ), array( 'name' => 'wpas-submit' ) );

		/**
		 * The mumei_ayuda_submission_form_inside_before hook has to be placed
		 * right before the form closing tag.
		 *
		 * @since  3.0.0
		 */
		do_action( 'mumei_ayuda_submission_form_inside_after' );
		mumei_ayuda_do_field( 'submit_new_ticket' );
		?>
	</form>
</div>
