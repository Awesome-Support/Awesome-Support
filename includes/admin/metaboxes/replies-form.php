<?php
/**
 * @package   Ayuda – Help Desk/Admin/Reply
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


add_filter( 'mumei_ayuda_admin_tabs_after_reply_wysiwyg', 'mumei_ayuda_add_reply_form_tab' , 8, 1 );
add_filter( 'mumei_ayuda_admin_tabs_after_reply_wysiwyg_reply_form_content','mumei_ayuda_reply_form_tab_content' , 11, 1 );

/**
 * Add Reply form tab in ticket edit page
 *
 * @param array $tabs
 *
 * @return array
 */
function mumei_ayuda_add_reply_form_tab( $tabs ) {
	$tabs['reply_form'] = __( 'Reply', 'ayuda-help-desk' );

	return $tabs;
}

/**
 * Return content for reply tab
 *
 * @global Object $post
 *
 * @param string $content
 *
 * @return string
 */
function mumei_ayuda_reply_form_tab_content( $content = '' ) {
	global $post;

	ob_start();
	?>

	<h2>
		<?php
		/**
		 * mumei_ayuda_write_reply_title_admin filter
		 *
		 * @since  3.1.5
		 *
		 * @param  string  Title to display
		 * @param  WP_Post Current post object
		 */

		// translators: %s is the title of the reply.
		$x_content = _x( 'Write a reply to %s', 'Title of the reply editor in the back-end', 'ayuda-help-desk' );
		echo wp_kses_post( apply_filters( 'mumei_ayuda_write_reply_title_admin', sprintf( esc_html($x_content), '&laquo;' . esc_attr( get_the_title( $post->ID ) ) . '&raquo;' ), $post ) ); ?>
	</h2>

	<div class="wpas-wp-editor-reply-tab-div">
		<?php
		// Load the WordPress WYSIWYG with minimal options
		wp_editor( apply_filters( 'mumei_ayuda_admin_reply_form_reply_content', '' ), 'mumei_ayuda_reply', apply_filters( 'mumei_ayuda_admin_reply_form_args', array(
				'media_buttons' => false,
				'teeny'         => true,
				'quicktags'     => true,
			)
		) );
		?>
	</div>

	<?php

	$content = ob_get_clean();

	return $content;
}


/**
 * Add a hook after the WYSIWYG editor
 * for tickets reply.
 *
 * @MUMEI_AYUDA_Quick_Replies::echoMarkup()
 */
do_action( 'mumei_ayuda_admin_after_wysiwyg' );

/**
 * Add a nonce for the reply
 */
wp_nonce_field( 'reply_ticket', 'mumei_ayuda_reply_ticket', false, true );
?>

<div class="wpas-reply-actions">
	<?php
	/**
	 * Where should the user be redirected after submission.
	 *
	 * @var string
	 */
	global $current_user;
	$where = get_user_option( 'mumei_ayuda_after_reply', $current_user->ID );

	switch ( $where ):

		case false:
		case '':
		case 'back': ?>
			<input type="hidden" name="mumei_ayuda_back_to_list" value="1">
			<button type="submit" name="mumei_ayuda_do" class="button-primary mumei_ayuda_btn_reply" value="reply"><?php esc_html_e( 'Reply', 'ayuda-help-desk' ); ?></button>
			<?php break;

			break;

		case 'stay':
			?>
			<button type="submit" name="mumei_ayuda_do" class="button-primary mumei_ayuda_btn_reply" value="reply"><?php esc_html_e( 'Reply', 'ayuda-help-desk' ); ?></button><?php
			break;

		case 'ask': ?>
			<fieldset>
				<strong><?php esc_html_e( 'After Replying', 'ayuda-help-desk' ); ?></strong><br>
				<label for="back_to_list"><input type="radio" id="back_to_list" name="where_after" value="back_to_list" checked="checked"> <?php esc_html_e( 'Back to list', 'ayuda-help-desk' ); ?></label>
				<label for="stay_here"><input type="radio" id="stay_here" name="where_after" value="stay_here"> <?php esc_html_e( 'Stay on ticket screen', 'ayuda-help-desk' ); ?></label>
				<label for="next_ticket"><input type="radio" id="next_ticket" name="where_after" value="next_ticket"> <?php esc_html_e( 'Go to the next ticket', 'ayuda-help-desk' ); ?></label>
				<label for="previous_ticket"><input type="radio" id="previous_ticket" name="where_after" value="previous_ticket"> <?php esc_html_e( 'Go to the previous ticket', 'ayuda-help-desk' ); ?></label>
			</fieldset>
			<button type="submit" name="mumei_ayuda_do" class="button-primary mumei_ayuda_btn_reply" value="reply"><?php esc_html_e( 'Reply', 'ayuda-help-desk' ); ?></button>
			<?php break;

	endswitch;
	?>

	<?php if ( current_user_can( 'close_ticket' ) ): ?>
		<button type="submit" name="mumei_ayuda_do" class="button-secondary mumei_ayuda_btn_reply_close" value="reply_close"><?php esc_html_e( 'Reply & Close', 'ayuda-help-desk' ); ?></button>
	<?php endif;

	/**
	 * Fired after all the submission form buttons were output
	 *
	 * @since 3.2.6
	 *
	 * @param int $post_id Ticket ID
	 */
	do_action( 'mumei_ayuda_post_reply_buttons_after', $post->ID );

	// Link to close the ticket
	if ( 'open' === get_post_meta( get_the_ID(), '_mumei_ayuda_status', true ) && current_user_can( 'close_ticket' ) ) : ?>
		<a class="mumei_ayuda_btn_close_bottom" href="<?php echo esc_url( mumei_ayuda_get_close_ticket_url( $post->ID ) ); ?>"><?php echo esc_html_x( 'Close', 'Close the ticket', 'ayuda-help-desk' ); ?></a>
	<?php endif; ?>
</div>