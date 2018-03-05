<?php
/**
 * System Tools.
 *
 * The system tools are a set of functions that helps accomplish some of the more technical
 * operations on the plugin data.
 *
 * The functions are triggered by a URL parameter and the trigger is pulled from the system_tools method
 * within the Awesome_Support_Admin class. Those functions must be triggered early so that we can safely
 * redirect to "read only" pages after the function was executed.
 */

/**
 * Build the link that triggers a specific tool.
 *
 * @since  3.0.0
 * @param  string $tool Tool to trigger
 * @param  array  $args Arbitrary arguments
 * @return string       URL that triggers the tool function
 */
function wpas_tool_link( $tool, $args = array() ) {

	$args['tool']   = $tool;
	$args['_nonce'] = wp_create_nonce( 'system_tool' );

	return esc_url( add_query_arg( $args, admin_url( 'edit.php' ) ) );
}

if ( isset( $_GET['done'] ) ) {

	switch( $_GET['done'] ) {

		case 'tickets_metas':
			$message = __( 'Tickets metas were cleared', 'awesome-support' );
			break;

		case 'agents_metas':
			$message = __( 'Agents metas were cleared', 'awesome-support' );
			break;

		case 'clear_taxonomies':
			$message = __( 'All custom taxonomies terms were cleared', 'awesome-support' );
			break;

		case 'resync_products':
			$message = __( 'All products have been re-synchronized', 'awesome-support' );
			break;

		case 'delete_products':
			$message = __( 'All products have been deleted', 'awesome-support' );
			break;

		case 'update_last_reply':
			$message = __( 'Last reply date has been updated on all tickets.', 'awesome-support' );
			break;
		
		case 'ticket_attachments':
			$message = __( 'All unclaimed ticket attachment folders have been deleted', 'awesome-support' );
			break;

		case 'reset_channels':
			$message = __( 'All channels have been reset', 'awesome-support' );
			break;
	}
	
	do_action('wpas_show_done_tool_message',sanitize_text_field( $_GET['done'] ));

}

if ( isset( $message ) ) {
	echo "<div class='updated below-h2'><p>$message</p></div>";
}
?>
<p><?php _e( 'These tool are intended for advanced users or for use on the support staff request. Be aware that some of these tools can definitively erase data.', 'awesome-support' ); ?></p>
<table class="widefat wpas-system-tools-table" id="wpas-system-tools">
	<thead>
		<tr>
			<th data-override="key" class="row-title"><?php _e( 'Tools', 'awesome-support' ); ?></th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Tickets Metas', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'tickets_metas' ); ?>" class="button-secondary"><?php _e( 'Clear', 'awesome-support' ); ?></a>
				<span class="wpas-system-tools-desc"><?php _e( 'Clear all transients for all tickets.', 'awesome-support' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Agents Metas', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'agents_metas' ); ?>" class="button-secondary"><?php _e( 'Clear', 'awesome-support' ); ?></a>
				<span class="wpas-system-tools-desc"><?php _e( 'Clear all agents metas.', 'awesome-support' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Custom Taxonomies', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'clear_taxonomies' ); ?>" class="button-secondary"><?php _e( 'Clear', 'awesome-support' ); ?></a>
				<span class="wpas-system-tools-desc"><?php _e( 'Clear all terms from all custom taxonomies.', 'awesome-support' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Ticket Attachments', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'ticket_attachments' ); ?>" class="button-secondary"><?php _e( 'Clear', 'awesome-support' ); ?></a>
				<span class="wpas-system-tools-desc"><?php _e( 'Clear unclaimed ticket attachment folders.', 'awesome-support' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Update last reply date on tickets', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'update_last_reply' ); ?>" class="button-secondary"><?php _e( 'Update', 'awesome-support' ); ?></a>
				<span class="wpas-system-tools-desc"><?php _e( 'Update last reply date on tickets.', 'awesome-support' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Reset replies count', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'reset_replies_count' ); ?>" class="button-secondary"><?php _e( 'Count', 'awesome-support' ); ?></a>
				<span class="wpas-system-tools-desc"><?php _e( 'Count all ticket replies.', 'awesome-support' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Reset channels', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'reset_channels' ); ?>" class="button-secondary"><?php _e( 'Reset', 'awesome-support' ); ?></a>
				<span class="wpas-system-tools-desc"><?php _e( 'Reset channels.', 'awesome-support' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Zero Out All Time Fields', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'reset_time_fields' ); ?>" class="button-secondary"><?php _e( 'Reset', 'awesome-support' ); ?></a>
				<span class="wpas-system-tools-desc"><?php _e( 'Reset time fields by setting them all to zero on ALL tickets!', 'awesome-support' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Re-run conversion from 3.3.x to 4.0.0', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'rerun_334_to_400_conversion' ); ?>" class="button-secondary"><?php _e( 'Rerun Conversion', 'awesome-support' ); ?></a>
				<span class="wpas-system-tools-desc"><?php _e( 'If your CAPABILITIES are not installed, re-run the 3.3.x to 4.0.0 conversion process. Make sure you have a BACKUP!', 'awesome-support' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Re-run conversion from 4.0.x to 4.4.0', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'rerun_400_to_440_conversion' ); ?>" class="button-secondary"><?php _e( 'Rerun Conversion', 'awesome-support' ); ?></a>
				<span class="wpas-system-tools-desc"><?php _e( 'If your CAPABILITIES are not installed, re-run the 4.x.x to 4.4.0 conversion process. Make sure you have a BACKUP!', 'awesome-support' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Re-run conversion from 4.x.x to 5.0.0', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'rerun_400_to_500_conversion' ); ?>" class="button-secondary"><?php _e( 'Rerun Conversion', 'awesome-support' ); ?></a>
				<span class="wpas-system-tools-desc"><?php _e( 'If your CAPABILITIES are not installed, re-run the 4.x.x to 5.0.0 conversion process. Make sure you have a BACKUP!', 'awesome-support' ); ?></span>
			</td>
		</tr>				
		<?php do_action( 'wpas_system_tools_table_after' ); ?>
	</tbody>
</table>