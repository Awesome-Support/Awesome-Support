<?php
/**
 * Ticket Stakeholders.
 *
 * This metabox is used to display all parties involved in the ticket resolution.
 *
 * @since 3.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/* Need access to the roles */
global $wp_roles;

/* Add nonce */
wp_nonce_field( Awesome_Support_Admin::$nonce_action, Awesome_Support_Admin::$nonce_name, false, true );

/* Issuer metadata */
$issuer = get_userdata( $post->post_author );

/* Issuer ID */
$issuer_id = $issuer->data->ID;

/* Issuer name */
$issuer_name = $issuer->data->display_name;

/* Issuer tickets link */
$issuer_tickets = admin_url( add_query_arg( array( 'post_type' => 'ticket', 'author' => $issuer_id ), 'edit.php' ) );

/* Prepare the empty users list */
$users = array();

/* Get fields values */
$ccs = wpas_get_cf_value( 'ccs', get_the_ID() );

/* Get ticket assignee */
$assignee = wpas_get_cf_value( 'assignee', get_the_ID() );

/* List available agents */
foreach( $wp_roles->roles as $role => $data ) {

	/* Check if current role can edit tickets */
	if( array_key_exists( 'edit_ticket', $data['capabilities'] ) ) {

		/* Get users with current role */
		$usrs = new WP_User_Query( array( 'role' => $role ) );

		/* Save users in global array */
		$users = array_merge( $users, $usrs->get_results() );
	}
}
?>
<div id="wpas-stakeholders">
	<label for="wpas-issuer"><strong><?php _e( 'Ticket Creator', 'wpas' ); ?></strong></label> 
	<p>

		<?php if ( current_user_can( 'create_ticket' ) ):

			/* List all users */
			$all_users = get_users();

			/* Set $selected as false so that we can use it as a marker in case the issuer is an agent */
			/**
			 * @todo there is an issue with $selected. if the selected user is in the middle of the list, the other users will have the selected attribute too. alos, there is currently no link between the current user selected state and the regular list $selected
			 */
			$selected = false; ?>

			<select name="post_author_override" id="wpas-issuer">
				<?php
				/* First of all let's add the current user */
				global $current_user;

				$current_id   = $current_user->ID;
				$current_name = $current_user->data->user_nicename;
				$current_sel  = ( $current_id == $post->post_author ) ? "selected='selected'" : '';

				/* The ticket is being created, use the current user by default */
				if ( !isset( $_GET['post'] ) ) {
					echo "<option value='$current_id'>$current_name</option>";
				}

				foreach ( $all_users as $user ) {

					/* Exclude agents */
					if ( !$user->has_cap( 'create_ticket' ) ) {

						$user_id   = $user->ID;
						$user_name = $user->data->user_nicename;

						if( $user_id == $post->post_author )
							$selected  = "selected='selected'";

						/* Output the option */
						echo "<option value='$user_id' $selected>$user_name</option>";
					}

				}

				/* In case there is no selected user yet we add the post author (most likely an admin) */
				if ( false === $selected ) {
					echo "<option value='$issuer_id'>$issuer_name</option>";
				}
				?>
			</select>

		<?php else: ?>
			<a id="wpas-issuer" href="<?php echo $issuer_tickets; ?>"><?php echo $issuer_name; ?></a></p>
		<?php endif; ?>

	<?php if( WPAS_FIELDS_DESC ): ?><p class="description"><?php printf( __( 'This ticket has been raised by the user hereinabove.', 'wpas' ), '#' ); ?></p><?php endif; ?>
	<hr>

	<label for="wpas-assignee"><strong><?php _e( 'Support Staff', 'wpas' ); ?></strong></label>
	<p>
		<select name="wpas_assignee" id="wpas-assignee" <?php if( !current_user_can( 'assign_ticket' ) ) { echo 'disabled'; } ?>>
			<?php
			foreach( $users as $usr => $data ) {
				?><option value="<?php echo $data->ID; ?>" <?php if( $data->ID == $assignee || '' == $assignee && $current_user->data->ID == $data->ID ) { echo 'selected="selected"'; } ?>><?php echo $data->data->display_name; ?></option><?php
			}
			?>
		</select>
	</p>
	<?php if( WPAS_FIELDS_DESC ): ?><p class="description"><?php printf( __( 'The above agent is currently responsible for this ticket.', 'wpas' ), '#' ); ?></p><?php endif; ?>
	
	<!-- <hr>

	<label for="wpas-ccs"><strong><?php _e( 'CCs', 'wpas' ); ?></strong></label>
	<p><input type="text" id="wpas-ccs" name="wpas_ccs" value="<?php echo $ccs; ?>" style="width:100%" /></p>
	<?php if( WPAS_FIELDS_DESC ): ?><p class="description"><?php printf( __( 'If you want to send a copy of the e-mails to another person, add the address(es) separated by a comma.', 'wpas' ), '#' ); ?></p><?php endif; ?> -->
</div>