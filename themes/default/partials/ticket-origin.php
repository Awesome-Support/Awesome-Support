<?php
/**
 * This is a built-in template file. If you need to customize it, please,
 * DO NOT modify this file directly. Instead, copy it to your theme's directory
 * and then modify the code. If you modify this file directly, your changes
 * will be overwritten during next update of the plugin.
 */

/* Exit if accessed directly */
if( !defined( 'ABSPATH' ) ) {
	exit;
}

/* IMPORTANT: make the $post var global as it is used in this template */
global $post;

/* Get author meta */
$author = get_user_by( 'id', $post->post_author );

/**
 * wpas_before_original_post hook
 */
do_action( 'wpas_before_original_post' ); ?>

<table id="original_ticket" class="table wpas-ticket-responses">
	<thead class="sr-only">
		<tr>
			<td><?php _e( 'User', 'awesome-support' ); ?></td>
			<td><?php _e( 'Message', 'awesome-support' ); ?></td>
		</tr>
	</thead>
	<tbody>
		<tr class="wpas_role wpas_client">
			<td class="tbl_col1">
				<div class="ticket_profile">

					<?php
					/**
					 * If the plugin is set to show Gravatars, we use a 96px Gravatar with the mystery man as a fallback
					 */
					if ( wpas_get_option( 'gravatar_on_front', 'yes' ) == 'yes' ) {
						echo get_avatar( $post->post_author, '96', get_option( 'avatar_default' ) );
					}
					?>

					<div>

						<?php
						/**
						 * Display the ticket's author name (client's name)
						 */
						?><span class="wpas-profilename"><?php echo $author->data->user_nicename; ?></span> 
						<span class="wpas-profiletype"><?php echo wpas_get_user_nice_role( $author->roles[0] ); ?></span> 
						<time class="visible-xs wpas-timestamp" datetime="<?php echo str_replace( ' ', 'T', $post->post_date ); ?>Z">
							<?php printf( __( '%s ago', 'awesome-support' ), human_time_diff( get_the_time( 'U', $post->ID ), current_time( 'timestamp' ) ) ); ?>
						</time>

					</div>	
				</div>
			</td>

			<td class="tbl_col2" <?php if ( wpas_get_option( 'date_position', 'right_side' ) == 'under_avatar' ): ?>colspan="2"<?php endif; ?>>
				<?php
				/**
				 * wpas_original_post_content_before hook
				 *
				 * @since  3.0.0
				 */
				do_action( 'wpas_original_post_content_before' );

				/**
				 * Display the original ticket's content
				 */
				echo apply_filters( 'the_content', $post->post_content );

				/**
				 * wpas_original_post_content_after hook
				 *
				 * @since  3.0.0
				 */
				do_action( 'wpas_original_post_content_after' );

				/**
				 * If any files attached we display them in an unordered list
				 * @var [type]
				 */
				// if( ( $attachments = get_post_meta( $post->ID, WPAS_PREFIX . 'attachments', true ) ) != '' && is_array( $attachments ) ) {

				// 	echo '<div class="attachments"><strong><span aria-hidden="true" class="glyphicon glyphicon-paperclip"></span> '.__('Attached files', 'wpas').':</strong><ul>';

				// 	wpas_get_uploaded_files( $post->ID );

				// 	echo '</ul></div>';
				// }
				?>
			</td>

		</tr>
	</tbody>
</table>

<?php
/**
 * Hook after the original post table
 */
do_action( 'wpas_after_original_post' );