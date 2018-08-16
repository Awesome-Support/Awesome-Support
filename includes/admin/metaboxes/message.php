<div id="wpas-ticket-message" class="wpas-ticket-content">
	<?php
	/**
	 * wpas_frontend_ticket_content_before hook
	 *
	 * @since  3.0.0
	 */
	
	
	// Include ticket toolbar
	include_once( WPAS_PATH . "includes/admin/metaboxes/toolbar-middle.php" );
	
	do_action( 'wpas_backend_ticket_content_before', $post->ID, $post );

	printf(
		'<div class="wpas-main-ticket-message" id="wpas-main-ticket-message">%s</div>',
		apply_filters( 'the_content', $post->post_content ) 
	);

	/**
	 * wpas_backend_ticket_content_after hook
	 *
	 * @since  3.0.0
	 */
	do_action( 'wpas_backend_ticket_content_after', $post->ID, $post );

	/**
	 * Allows certain user roles from Settings -> General -> History
	 *
	 * Administrator should be always on. Both site admin and Super Admin
	 */
	$excluded_roles = wpas_get_option( 'roles_edit_ticket_content', false );
	$current_user_role = wpas_get_current_user_role();
	$role_passed = true;

	/**
	 * Check if the settings has comma separated string for roles
	 * NOTE: If the 'roles_edit_ticket_content' contains 'administrator'
	 * it will be surpassed by is_admin()
	 */
	if( strpos( $excluded_roles, ',' ) !== false ) {
		/**
		 * This should be an array
		 */
		$roles = explode( ',', $excluded_roles );
		if( in_array( $current_user_role, $roles ) ) {
			$role_passed = false;
		}
	}elseif( wpas_get_current_user_role() === $excluded_roles ){
		$role_passed = false;
	}

	/**
	 * Determine if we should allow current user to edit ticket opening content
	 */
	if( wpas_is_asadmin() || $role_passed ) {
	?>
		<div class="wpas-edit-ticket-actions">
			<a href="#" class="button button-primary wpas-save-edit-main-ticket-message" id="wpas-save-edit-main-ticket-message" data-ticketid="<?php echo $post->ID; ?>"><?php _e( 'Save', 'awesome-support' ); ?></a>
			<a href="#" class="button button-secondary wpas-cancel-edit-main-ticket-message" id="wpas-cancel-edit-main-ticket-message" data-ticketid="<?php echo $post->ID; ?>"><?php _e( 'Cancel', 'awesome-support' ); ?></a>
		</div>
	<?php
	}
	?>
</div>