<?php

add_action( 'admin_init', 'wpas_system_tools', 10, 0 );
function wpas_system_tools() {

	if ( ! isset( $_GET['tool'] ) || ! isset( $_GET['_nonce'] ) ) {
		return false;
	}

	if ( ! wp_verify_nonce( $_GET['_nonce'], 'system_tool' ) ) {
		return false;
	}

	switch ( sanitize_text_field( $_GET['tool'] ) ) {

		/* Clear all tickets metas */
		case 'tickets_metas';
			wpas_clear_tickets_metas();
			break;

		case 'agents_metas':
			wpas_clear_agents_metas();
			break;

		case 'clear_taxonomies':
			wpas_clear_taxonomies();
			break;

		case 'resync_products':
			wpas_delete_synced_products( true );
			break;

		case 'delete_products':
			wpas_delete_synced_products();
			break;

		case 'delete_unused_terms':
			wpas_delete_unused_terms();
			break;

		case 'ticket_attachments':
			wpas_delete_unclaimed_attachments();
			break;
		
		case 'update_last_reply':
			wpas_update_last_reply();
			break;

		case 'reset_replies_count':
			wpas_reset_replies_count();
			break;
		
		case 'reset_channels':
			wpas_reset_channel_terms();
			break;
			
		case 'reset_ticket_types':
			wpas_reset_ticket_types();
			break;			

		case 'reset_time_fields':
			wpas_reset_time_fields_to_zero();
			break;
		
		case 'rerun_334_to_400_conversion':			
			wpas_upgrade_406();
			break ;
			
		case 'rerun_400_to_440_conversion':
			wpas_upgrade_440();
			break;

		case 'rerun_400_to_500_conversion':
			wpas_upgrade_511();
			wpas_upgrade_520();
			break;	
			
		case 'rerun_580_to_590_conversion':
			wpas_upgrade_590();
			break;

		case 'install_blue_blocks_email_template':
			wpas_install_email_template( 'blue_blocks' );
			break;
			
		case 'install_blue_blocks_ss_email_template':
			wpas_install_email_template( 'blue_blocks-ss' );
			break;
			
		case 'install_elegant_email_template':
			wpas_install_email_template( 'elegant' );
			break;						

		case 'install_elegant_ss_email_template':
			wpas_install_email_template( 'elegant-ss' );
			break;
			
		case 'install_simple_email_template':
			wpas_install_email_template( 'simple' );
			break;						
			
		case 'install_default_email_template':
			wpas_install_email_template( 'default' );
			break;
			
		case 'install_debug_email_template':
			wpas_install_email_template( 'debug' );
			break;		
		
		case 'mark_all_auto_del_attchmnts':
		case 'remove_mark_all_auto_del_attchmnts':
		case 'mark_open_auto_del_attchmnts':
		case 'remove_mark_open_auto_del_attchmnts':
		case 'mark_closed_auto_del_attchmnts':
		case 'remove_mark_closed_auto_del_attchmnts':
			
			$act = sanitize_text_field( $_GET['tool'] );
			$act_parts = explode( '_', $act );
			$flag_added = 'remove' === substr( $act, 0, 6 ) ? false : true;
			$flag_ticket_type = $flag_added ? $act_parts[1] : $act_parts[2];
			
			WPAS_File_Upload::mark_tickets_auto_delete_attachments( $flag_ticket_type, $flag_added );
			
			break;
	}

	do_action('execute_additional_tools',sanitize_text_field( $_GET['tool'] ));
	
	/* Redirect in "read-only" mode */
	$url = add_query_arg( array(
			'post_type' => 'ticket',
			'page'      => 'wpas-status',
			'tab'       => 'tools',
			'done'      => sanitize_text_field( $_GET['tool'] )
	), admin_url( 'edit.php' )
	);

	wp_redirect( wp_sanitize_redirect( $url ) );
	exit;

}

/**
* Require this file here so that we don't duplicate the upgrade functions. Its used by one of the case statements above to 
* run the 3.3.4 to 4.0.0 upgrade process on demand.
* We can remove it or find a better way to handle it later (after a couple of 4.x releases).
*/
require_once( WPAS_PATH . 'includes/admin/upgrade/functions-upgrade.php' );

/**
 * Add default channels.
 * 
 * @return boolean
 * 
 */
function wpas_reset_channel_terms() {
	return wpas_add_default_channel_terms(true);
}

/**
 * Add default ticket types.
 * 
 * @return boolean
 * 
 */
function wpas_reset_ticket_types() {
	return wpas_add_default_ticket_types(true);
}

/**
 * Reset replies count for all tickets.
 *
 * Gets all the existing tickets from the system
 * and reset their replies count one by one.
 *
 * @return boolean
 * 
 */
function wpas_reset_replies_count() {
	$args = array(
		'post_type'              => 'ticket',
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	);

	$query   = new WP_Query( $args );
	$reset = false;
	
	if ( 0 == $query->post_count ) {
		return false;
	}

	foreach( $query->posts as $post ) {
		if ( wpas_count_replies( $post->ID ) && false === $reset ) {
			$reset = true;
		}
	}

	return $reset;
}

/**
 * Clear the activity meta for a given ticket.
 *
 * Deletes the activity meta transient from the database
 * for one given ticket.
 *
 * @since  3.0.0
 * @param  integer $ticket_id ID of the ticket to clear the meta from
 * @return boolean            True if meta was cleared, false otherwise
 * 
 */
function wpas_clear_ticket_activity_meta( $ticket_id ) {
	return delete_transient( "wpas_activity_meta_post_$ticket_id" );
}

/**
 * Clear all tickets metas.
 *
 * Gets all the existing tickets from the system
 * and clear their metas one by one.
 *
 * @since 3.0.0
 * @return  True if some metas were cleared, false otherwise
 * 
 */
function wpas_clear_tickets_metas() {

	$args = array(
		'post_type'              => 'ticket',
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	);

	$query   = new WP_Query( $args );
	$cleared = false;
	
	if ( 0 == $query->post_count ) {
		return false;
	}

	foreach( $query->posts as $post ) {
		if ( wpas_clear_ticket_activity_meta( $post->ID ) && false === $cleared ) {
			$cleared = true;
		}
	}

	return $cleared;

}

/**
 * Clear all terms for a given taxonomy.
 *
 * @since  3.0.0
 * @param  string $taxonomy Taxonomy name
 * @return boolean          True if terms were deleted, false otherwise
 */
function wpas_clear_taxonomy( $taxonomy ) {

	$terms  = get_terms( $taxonomy, array( 'hide_empty' => false ) );
	$delete = false;

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return false;
	}

	foreach ( $terms as $term ) {
		if ( wp_delete_term( $term->term_id, $taxonomy ) && false === $delete ) {
			$delete = true;
		}
	}

	return $delete;

}

/**
 * Clear all custom taxonomies terms.
 *
 * @since  3.0.0
 * @return boolean True if terms were deleted, false otherwise
 */
function wpas_clear_taxonomies() {

	$taxonomies = (array) WPAS()->custom_fields->get_custom_fields();
	$deleted    = false;

	if ( empty( $taxonomies ) ) {
		return false;
	}

	foreach ( $taxonomies as $taxonomy ) {

		if ( 'taxonomy' !== $taxonomy['args']['callback'] ) {
			continue;
		}

		if ( wpas_clear_taxonomy( $taxonomy['name'] ) && false === $deleted ) {
			$deleted = true;
		}

	}

	return $deleted;

}

/**
 * Delete the synchronized e-commerce products.
 *
 * The function goes through all the available products
 * and deletes the associated synchronized terms along with
 * the term taxonomy and term relationship. It also deleted
 * the post metas where the taxonomy ID is stored.
 *
 * @param $resync boolean Whether or not to re-synchronize the products after deleting them
 *
 * @return        boolean True if the operation completed, false otherwise
 * @since 3.1.7
 */
function wpas_delete_synced_products( $resync = false ) {
	
	$post_type = filter_input( INPUT_GET, 'pt', FILTER_SANITIZE_STRING );
	
	if ( empty( $post_type ) ) {
		return false;
	}
	
	$sync  = new WPAS_Product_Sync( '', 'product' );
	$posts = new WP_Query( array( 'post_type' => $post_type, 'posts_per_page' => -1, 'post_status' => 'any' ) );
	$sync->set_post_type( $post_type );
	
	$product_terms = get_terms([
		'taxonomy' => 'product',
		'hide_empty' => false,
	]);
	
	if ( ! empty( $posts->posts ) ) {
		
		foreach((array)$product_terms as $product_term){
			
			$unsync_term = false;
			
			/* Does the product taxonmy exist as a product post? */
			/* Note even sure this section is needed - it seems  */
			/* to be unnecessary given what comes in the next    */
			/* section. */
			foreach ( $posts->posts as $post ) {
				if($product_term->name == $post->ID){
					$unsync_term = true;
				}
			}
			
			/* Product taxonomy item exists in the product posts so process this section */
			if($unsync_term == true){
				
				/* Is the product term on a ticket?  Only if its not used on a ticket should we remove it from the product taxonomy */
				if( wpas_product_has_tickets($product_term->term_id) === false ){
					
					wp_delete_term( (int) $product_term->term_id, 'product' );
					
				}
			}
			
		}
		
	}
	
	/* Now let's make sure we don't have some orphan post metas left */
	global $wpdb;
	$metas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = '%s'", '_wpas_product_term' ) );
	if ( ! empty( $metas ) ) {
		foreach ( $metas as $meta ) {
			$value = unserialize( $meta->meta_value );
			$term = get_term_by( 'id', $value['term_id'], 'product' );
			if ( empty( $term ) ) {
				delete_post_meta( $meta->post_id, '_wpas_product_term' );
			}
		}
	}
	if ( true === $resync ) {
		/* Delete the initial synchronization marker so that it's done again */
		// delete_option( "wpas_sync_$post_type" );
		update_option( "wpas_sync_$post_type", 0 );
		/* Synchronize */
		$sync->run_initial_sync();
	}
	return true;
}

/**
 * Check product term has any ticket
 *
 * @since 4.0.0
 * @return boolean */
function wpas_product_has_tickets($term_id) {
	$args = array(
		'post_type' => 'ticket',
		'status' => 'publish',
		'tax_query' => array(
			array(
				'taxonomy' => 'product',
				'field' => 'id',
				'terms' => $term_id
			)
		)
	);
	$term_query =  new WP_Query( $args );
	$term_posts_count = $term_query->found_posts;
	
	if( $term_posts_count > 0 ){
		return true;
	}else{
		return false;
	}
}

/**
 * @return array
 */
function wpas_delete_unused_terms() {

	$statistics = array(
		'count'     => 0,
		'deleted'   => 0,
		'used'      => 0,
	);

	$taxonomy   = get_taxonomy('product');
	$terms      = get_terms( 'product', array( 'hide_empty' => false ) );

	$statistics['count'] = count($terms);

	foreach( $terms as $term ) {

		$items = new WP_Query( array(
                            'post_type'   => 'ticket',
                            'numberposts' => -1,
                            'tax_query'   => array(
                            	array(
                            		'taxonomy'  => 'product',
                                    'terms'     => array($term->term_id),
                                    'field'     => 'term_id',
	                                'operator'  => 'IN'
	                            )
                            )
        ) );

		if( 0 === count( $items->posts ) ) {
			wp_delete_term($term->term_id, $taxonomy->name);
			$statistics['deleted'] += 1;
		}

		wp_update_term_count_now( array($term->term_id), $taxonomy->name );

	}

	$statistics['used'] = count(get_terms( 'product', array( 'hide_empty' => false ) ));

	return $statistics;
}

/**
 * Clear the agents metas that can be
 *
 * @since 3.2
 * @return void
 */
function wpas_clear_agents_metas() {

	$agents = wpas_get_users( array( 'cap' => 'edit_ticket' ) );

	foreach ( $agents as $user ) {
		delete_user_option( $user->ID, 'wpas_open_tickets' ); 		// Delete the open tickets count
		delete_user_option( $user->ID, 'wpas_open_tickets',true ); // Delete it as well at the global level just in case it exists there (which it shouldn't!)
	}

}

/**
 * Checks for templates overrides.
 *
 * Check if any of the plugin templates is being
 * overwritten by the child theme or the theme.
 *
 * @since  3.0.0
 * @param  string $dir Directory to check
 * @return array       Array of overridden templates
 */
function wpas_check_templates_override( $dir ) {

	$templates = array(
			'details.php',
			'list.php',
			'registration.php',
			'submission.php'
	);

	$overrides = array();

	if ( is_dir( $dir ) ) {

		$files = scandir( $dir );

		if ( empty( $files ) ) {
			return array();
		}

		foreach ( $files as $key => $file ) {
			if ( !in_array( $file, $templates ) ) {
				continue;
			}

			array_push( $overrides, $file );
		}

	}

	return $overrides;

}

/**
 * Update the last reply date postmeta for all tickets.
 *
 * @return boolean
 *
 */
function wpas_update_last_reply() {

	global $wpdb;

	$sql = <<<SQL
SELECT 
	wpas_ticket.ID AS ticket_id,
	wpas_reply.ID AS reply_id,
	wpas_replies.latest_reply,
	wpas_replies.latest_reply_gmt,
	wpas_replies.post_author,
	wpas_ticket.post_author=wpas_reply.post_author AS client_replied_last
FROM 
	{$wpdb->posts} AS wpas_ticket 
	LEFT OUTER JOIN {$wpdb->posts} AS wpas_reply ON wpas_ticket.ID=wpas_reply.post_parent
	LEFT OUTER JOIN (
		SELECT
			post_parent AS ticket_id,
			post_author as post_author,
  			post_date_gmt AS latest_reply_gmt,
			MAX(post_date) AS latest_reply
		FROM
			{$wpdb->posts}
		WHERE 1=1
			AND 'ticket_reply' = post_type
		GROUP BY
			post_parent
	) wpas_replies ON wpas_replies.ticket_id=wpas_reply.post_parent AND wpas_replies.latest_reply=wpas_reply.post_date 
WHERE 1=1
	AND wpas_replies.latest_reply IS NOT NULL
	AND 'ticket_reply'=wpas_reply.post_type
ORDER BY
	wpas_replies.latest_reply ASC
SQL;

	$test = wpas_get_tickets('any');

	/* Set some defaults or all tickets */
	foreach ( $test as $ticket) {
		update_post_meta( $ticket->ID, '_wpas_last_reply_date', $ticket->post_date );
		update_post_meta( $ticket->ID, '_wpas_last_reply_date_gmt', $ticket->post_date_gmt );
		update_post_meta( $ticket->ID, '_wpas_is_waiting_client_reply', 0 );
	}

	$replies = $wpdb->get_results( $sql );

	foreach ( $replies as $reply_post ) {

		if( null !== get_post( $reply_post->ticket_id) ) {

			/* . */
			update_post_meta( $reply_post->ticket_id, '_wpas_last_reply_date', $reply_post->latest_reply );
			update_post_meta( $reply_post->ticket_id, '_wpas_last_reply_date_gmt', $reply_post->latest_reply_gmt );
			update_post_meta( $reply_post->ticket_id, '_wpas_is_waiting_client_reply', (int)$reply_post->client_replied_last );

		}
	}

}

/**
 * Delete unclaimed attachments
 *
 * @since 3.3.4
 * @return void
 */
function wpas_delete_unclaimed_attachments() {

	$upload           = wp_get_upload_dir();
	$attachments_root = trailingslashit( $upload['basedir'] ) . 'awesome-support/';
	$ticket_folders   = glob( $attachments_root . 'ticket_*' );

	foreach ( $ticket_folders as $folder ) {

		$basename  = basename( $folder );

		if ( ( $x_pos = strpos( $basename, '_' ) ) !== false ) {
			$ticket_id = substr( $basename, $x_pos + 1 );
			$post      = get_post( absint( $ticket_id ) );

			if ( empty( $post ) ) {

				$it    = new RecursiveDirectoryIterator( $attachments_root . $basename, RecursiveDirectoryIterator::SKIP_DOTS );
				$files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );

				foreach ( $files as $file ) {
					if ( $file->isDir() ) {
						rmdir( $file->getRealPath() );
					} else {
						unlink( $file->getRealPath() );
					}
				}
				rmdir( $attachments_root . $basename );
			}
		}
	}

	return;

}

/**
 * Reset all time tracking fields to zero
 *
 * @since 3.6.0
 * @return void
 */

function wpas_reset_time_fields_to_zero() {

	$args = array(
		'post_type'              => 'ticket',
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	);

	$query   = new WP_Query( $args );
	$reset = true;

	if ( 0 == $query->post_count ) {
		return false;
	}

	foreach( $query->posts as $post ) {
		update_post_meta( $post->ID, '_wpas_ttl_calculated_time_spent_on_ticket', 0 );
		update_post_meta( $post->ID, '_wpas_ttl_adjustments_to_time_spent_on_ticket', 0 );
		update_post_meta( $post->ID, '_wpas_final_time_spent_on_ticket', 0 );
	}
	
	return $reset;	
}

function wpas_install_email_template( $template, $overwrite = true ) {
	
	$template_root_path = '';  // the file path to the folder containing the template set
	
	// Set the variable to the template set path based on which template we need
	switch ( $template ) {

		case 'blue_blocks' : 
			$template_root_path = WPAS_PATH . 'assets/admin/email-templates/blue-block/';		
			break;
	
		case 'blue_blocks-ss' : 
			$template_root_path = WPAS_PATH . 'assets/admin/email-templates/blue-block-with-satisfaction-surveys/';		
			break;
			
		case 'elegant' : 
			$template_root_path = WPAS_PATH . 'assets/admin/email-templates/elegant/';		
			break;
			
		case 'elegant-ss' : 
			$template_root_path = WPAS_PATH . 'assets/admin/email-templates/elegant-with-with-satisfaction-surveys/';		
			break;			

		case 'simple' : 
			$template_root_path = WPAS_PATH . 'assets/admin/email-templates/simple/';		
			break;						
			
		case 'default' : 
			$template_root_path = WPAS_PATH . 'assets/admin/email-templates/default/';		
			break;
			
		case 'debug' : 
			$template_root_path = WPAS_PATH . 'assets/admin/email-templates/debug/';		
			break;					
	}
	
	// Allow other add-ons to set this path
	$template_root_path = apply_filters( 'wpas_email_template_root_path', $template_root_path ) ;
	
	// Create array with option names and corresponding file names
	$template_files['content_confirmation'] 	= $template_root_path . 'New-Ticket-Confirmation-Going-To-End-User.html';
	$template_files['content_assignment'] 		= $template_root_path . 'New-Ticket-Assigned-To-Agent.html';
	$template_files['content_reply_agent'] 		= $template_root_path . 'Agent-Reply-Going-To-End-User.html';
	
	$template_files['content_reply_client'] 	= $template_root_path . 'New-Reply-From-Client-Going-To-Agent.html';
	$template_files['content_closed'] 			= $template_root_path . 'Ticket-Closed-By-Agent.html';
	$template_files['content_closed_client'] 	= $template_root_path . 'Ticket-Closed-By-Client.html';
	
	// Allow other add-ons to update this array 
	$template_files = apply_filters( 'wpas_email_template_map_to_files', $template_files );
	
	// Read the template files into the appropriate option based on the key-fiile mapping array above.
	foreach ( $template_files as $key => $template_file ) {

	if ( file_exists( $template_file ) ) {
		
		$template_contents = file_get_contents( $template_file );

			if ( ! empty( $template_contents ) ) {
				
				// Is there any existing value in the option?
				$existing_contents = wpas_get_option( $key );
				
				if ( ! empty( $existing_contents ) && ! $overwrite ) {
					// do not overwrite existing contents
					continue ;
				}
				
				wpas_update_option( $key, $template_contents, true ) ;
			}
			
		}
	}
	
	
}