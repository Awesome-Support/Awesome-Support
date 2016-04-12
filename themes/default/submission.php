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

	<?php wpas_get_template( 'partials/ticket-navigation' ); ?>

	<form class="wpas-form" role="form" method="post" action="<?php echo get_permalink( $post->ID ); ?>" id="wpas-new-ticket" enctype="multipart/form-data">

		<?php
		/**
		 * The wpas_submission_form_inside_before has to be placed
		 * inside the form, right in between the form opening tag
		 * and the subject field.
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_submission_form_inside_before_subject' );

		/**
		 * Filter the subject field arguments
		 *
		 * @since 3.2.0
		 */
		$subject_args = apply_filters( 'wpas_subject_field_args', array(
			'name' => 'title',
			'args' => array(
				'required'   => true,
				'field_type' => 'text',
				'label'      => __( 'Subject', 'awesome-support' ),
				'sanitize'   => 'sanitize_text_field'
			)
		) );

		$subject = new WPAS_Custom_Field( 'title', $subject_args );
		echo $subject->get_output();

		/**
		 * The wpas_submission_form_inside_after_subject hook has to be placed
		 * right after the subject field.
		 *
		 * This hook is very important as this is where the custom fields are hooked.
		 * Without this hook custom fields would not display at all.
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_submission_form_inside_after_subject' );

		/**
		 * Filter the description field arguments
		 *
		 * @since 3.2.0
		 */
		$body_args = apply_filters( 'wpas_description_field_args', array(
			'name' => 'message',
			'args' => array(
				'required'   => true,
				'field_type' => 'wysiwyg',
				'label'      => __( 'Description', 'awesome-support' ),
				'sanitize'   => 'sanitize_text_field'
			)
		) );

		$body = new WPAS_Custom_Field( 'message', $body_args );
		echo $body->get_output();

		/**
		 * The wpas_submission_form_inside_before hook has to be placed
		 * right before the submission button.
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_submission_form_inside_before_submit' );

		wp_nonce_field( 'new_ticket', 'wpas_nonce', true, true );
		wpas_make_button( __( 'Submit ticket', 'awesome-support' ), array( 'name' => 'wpas-submit' ) );
		
		/**
		 * The wpas_submission_form_inside_before hook has to be placed
		 * right before the form closing tag.
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_submission_form_inside_after' );
		wpas_do_field( 'submit_new_ticket' );
		?>
	</form>
</div>