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

		$subject = new WPAS_Custom_Field( 'title', array( 'name' => 'title', 'args' => array( 'required' => true, 'field_type' => 'text', 'label' => 'Subject' ) ) );
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

		$body = new WPAS_Custom_Field( 'message', array( 'name' => 'message', 'args' => array( 'required' => true, 'field_type' => 'wysiwyg', 'label' => 'Description' ) ) );
		echo $body->get_output();

		/**
		 * The wpas_submission_form_inside_before hook has to be placed
		 * right before the submission button.
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_submission_form_inside_before_submit' );

		wp_nonce_field( 'new_ticket', 'wpas_nonce', false, true );
		wpas_make_button( __( 'Submit ticket', 'wpas' ), array( 'name' => 'wpas-submit', 'onsubmit' => __( 'Please Wait...', 'wpas' ) ) );
		
		/**
		 * The wpas_submission_form_inside_before hook has to be placed
		 * right before the form closing tag.
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_submission_form_inside_after' );
		?>
	</form>
</div>