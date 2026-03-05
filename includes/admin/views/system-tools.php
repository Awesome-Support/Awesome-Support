<?php
/**
 * System Tools.
 *
 * The system tools are a set of functions that helps accomplish some of the more technical
 * operations on the plugin data.
 *
 * The functions are triggered by a URL parameter and the trigger is pulled from the system_tools method
 * within the Mumei_Ayuda_Support_Admin class. Those functions must be triggered early so that we can safely
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
function mumei_ayuda_tool_link( $tool, $args = array() ) {
	$args['tool']   = $tool;
	$args['_nonce'] = wp_create_nonce( 'system_tool' );

	return esc_url( add_query_arg( $args, admin_url( 'edit.php' ) ) );
}

if ( isset( $_GET['done'] ) ) {

	$message = '' ;

	switch( $_GET['done'] ) {

		case 'tickets_metas':
			$message = __( 'Tickets metas were cleared', 'ayuda-help-desk' );
			break;

		case 'agents_metas':
			$message = __( 'Agents metas were cleared', 'ayuda-help-desk' );
			break;

		case 'clear_taxonomies':
			$message = __( 'All custom taxonomies terms were cleared', 'ayuda-help-desk' );
			break;

		case 'resync_products':
			$message = __( 'All products have been re-synchronized', 'ayuda-help-desk' );
			break;

		case 'delete_products':
			$message = __( 'All products have been deleted', 'ayuda-help-desk' );
			break;

		case 'update_last_reply':
			$message = __( 'Last reply date has been updated on all tickets.', 'ayuda-help-desk' );
			break;

		case 'ticket_attachments':
			$message = __( 'All unclaimed ticket attachment folders have been deleted', 'ayuda-help-desk' );
			break;

		case 'reset_channels':
			$message = __( 'All channels have been reset', 'ayuda-help-desk' );
			break;

		case 'reset_ticket_types':
			$message = __( 'All ticket types have been reset', 'ayuda-help-desk' );
			break;

		case 'install_blue_blocks_email_template':
			$message = __( 'The Blue Blocks Email Template Set Has Been Installed', 'ayuda-help-desk' );
			break;

		case 'install_blue_blocks_ss_email_template':
			$message = __( 'The Blue Blocks With Satisfaction Survey Elements Email Template Set Has Been Installed', 'ayuda-help-desk' );
			break;

		case 'install_elegant_email_template':
			$message = __( 'The Elegant Email Template Set Has Been Installed', 'ayuda-help-desk' );
			break;

		case 'install_elegant_ss_email_template':
			$message = __( 'The Elegant With Satisfaction Survey Elements Email Template Set Has Been Installed', 'ayuda-help-desk' );
			break;

		case 'install_simple_email_template':
			$message = __( 'The Simple Email Template Set Has Been Installed', 'ayuda-help-desk' );
			break;

		case 'install_default_email_template':
			$message = __( 'The Default Email Template Set Has Been Installed', 'ayuda-help-desk' );
			break;

		case 'install_debug_email_template':
			$message = __( 'The Debug Email Template Set Has Been Installed', 'ayuda-help-desk' );
			break;

		case 'mark_all_auto_del_attchmnts':
		case 'remove_mark_all_auto_del_attchmnts':
		case 'mark_open_auto_del_attchmnts':
		case 'remove_mark_open_auto_del_attchmnts':
		case 'mark_closed_auto_del_attchmnts':
		case 'remove_mark_closed_auto_del_attchmnts':

			$done_parts = explode( '_', sanitize_text_field( wp_unslash( $_GET['done'] ) ) );
			$flag_added_removed = 'remove' === substr( sanitize_text_field( wp_unslash( $_GET['done'] ) ), 0, 6 ) ? 'removed' : 'added';
			$flag_ticket_types = 'removed' === $flag_added_removed ? $done_parts[2] : $done_parts[1];


			// translators: %1$s is the flag or status, %2$s is the number of tickets.
			$x_content = __( 'Auto delete attachments flag %1$s on %2$s tickets', 'ayuda-help-desk' );

			$message = sprintf( $x_content, $flag_added_removed, $flag_ticket_types );
			break;
	}

	$message = apply_filters('mumei_ayuda_show_done_tool_message', $message, sanitize_text_field( wp_unslash( $_GET['done'] ) ));

}

if ( isset( $message ) && !empty( $message ) ) {
	echo '<div class="updated below-h2"><p>' . esc_html( $message ) . '</p></div>';
}
?>
<p><?php esc_html_e( 'These tool are intended for advanced users or for use on the support staff request. Be aware that some of these tools can definitively erase data.', 'ayuda-help-desk' ); ?></p>
<table class="widefat wpas-system-tools-table" id="wpas-system-tools">
	<thead>
		<tr>
			<th data-override="key" class="row-title"><?php esc_html_e( 'General Tools', 'ayuda-help-desk' ); ?></th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Tickets Metas', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'tickets_metas' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Clear', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Clear all transients for all tickets.', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Agents Metas', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'agents_metas' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Clear', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Clear all agents metas.', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Custom Taxonomies', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'clear_taxonomies' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Clear', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Clear all terms from all custom taxonomies.', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Ticket Attachments', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'ticket_attachments' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Clear', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Clear unclaimed ticket attachment folders.', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Update last reply date on tickets', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'update_last_reply' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Update', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Update last reply date on tickets.', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Reset replies count', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'reset_replies_count' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Count', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Count all ticket replies.', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Reset channels', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'reset_channels' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Reset', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Reset channels.', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Reset ticket types', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'reset_ticket_types' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Reset', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Reset ticket_types.', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Zero Out All Time Fields', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'reset_time_fields' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Reset', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Reset time fields by setting them all to zero on ALL tickets!', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<?php do_action( 'mumei_ayuda_system_tools_table_after' ); ?>
	</tbody>
</table>

<p><h3><?php esc_html_e( 'Install an email template set', 'ayuda-help-desk' ); ?></h3></p>
<p><?php esc_html_e( 'Warning: Using these options will overwrite your email templates in TICKETS->SETTINGS->EMAILS!', 'ayuda-help-desk' ); ?></p>
<table class="widefat wpas-system-tools-table" id="wpas-system-tools">
	<thead>
		<tr>
			<th data-override="key" class="row-title"><?php esc_html_e( 'Email Template Sets', 'ayuda-help-desk' ); ?></th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Blue Blocks', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'install_blue_blocks_email_template' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Install', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Install the Blue Blocks email template set into the TICKETS->SETTINGS->EMAIL template fields', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Blue Blocks With Satisfaction Survey Elements', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'install_blue_blocks_ss_email_template' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Install', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Install the Blue Blocks with Satisfaction Survey Elements email template set into the TICKETS->SETTINGS->EMAIL template fields', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>

		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Elegant', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'install_elegant_email_template' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Install', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Install the Elegant email template set into the TICKETS->SETTINGS->EMAIL template fields', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Elegant With Satisfaction Survey Elements', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'install_elegant_ss_email_template' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Install', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Install the Elegant email template set with Satisfaction Survey Elements into the TICKETS->SETTINGS->EMAIL template fields', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>

		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Simple', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'install_simple_email_template' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Install', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Install the Simple email template set into the TICKETS->SETTINGS->EMAIL template fields', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>

		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Default', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'install_default_email_template' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Install', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Install the Default email template set into the TICKETS->SETTINGS->EMAIL template fields', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>

		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Debug', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'install_debug_email_template' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Install', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Install a debugging email template set into the TICKETS->SETTINGS->EMAIL template fields', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>

		<?php do_action( 'mumei_ayuda_system_email_template_tools_table_after' ); ?>
	</tbody>
</table>

<p><h3><?php esc_html_e( 'Tools to re-run conversion of data after upgrading from an earlier version', 'ayuda-help-desk' ); ?></h3></p>
<table class="widefat wpas-system-tools-table" id="wpas-system-tools">
	<thead>
		<tr>
			<th data-override="key" class="row-title"><?php esc_html_e( 'Data Conversion Tools', 'ayuda-help-desk' ); ?></th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Re-run conversion from 3.3.x to 4.0.0', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'rerun_334_to_400_conversion' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Rerun Conversion', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'If your CAPABILITIES are not installed, re-run the 3.3.x to 4.0.0 conversion process. Make sure you have a BACKUP!', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Re-run conversion from 4.0.x to 4.4.0', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'rerun_400_to_440_conversion' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Rerun Conversion', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'If your CAPABILITIES are not installed, re-run the 4.x.x to 4.4.0 conversion process. Make sure you have a BACKUP!', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Re-run conversion from 4.x.x to 5.0.0', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'rerun_400_to_500_conversion' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Rerun Conversion', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'If your CAPABILITIES are not installed, re-run the 4.x.x to 5.0.0 conversion process. Make sure you have a BACKUP!', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Re-run conversion from 5.8.0 to 5.9.0', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'rerun_580_to_590_conversion' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Rerun Conversion', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'If your CAPABILITIES are not installed, re-run the 5.8.0 to 5.9.0 conversion process. Make sure you have a BACKUP!', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>


		<?php do_action( 'mumei_ayuda_system_data_conversion_tools_table_after' ); ?>
	</tbody>
</table>

<p><h3><?php esc_html_e( 'Tools to handle ticket and reply attachments', 'ayuda-help-desk' ); ?></h3></p>
<table class="widefat wpas-system-tools-table" id="wpas-system-tools-attachments">
	<thead>
		<tr>
			<th data-override="key" class="row-title"><?php esc_html_e( 'Auto Delete Attachments Flag', 'ayuda-help-desk' ); ?></th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'All Tickets', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'mark_all_auto_del_attchmnts' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Add', 'ayuda-help-desk' ); ?></a>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'remove_mark_all_auto_del_attchmnts' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Remove', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Add or Remove auto delete attachments flag on all tickets.', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Open Tickets', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'mark_open_auto_del_attchmnts' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Add', 'ayuda-help-desk' ); ?></a>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'remove_mark_open_auto_del_attchmnts' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Remove', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Add or Remove auto delete attachments flag on open tickets.', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>
		<tr>
			<td class="row-title"><label for="tablecell"><?php esc_html_e( 'Closed Tickets', 'ayuda-help-desk' ); ?></label></td>
			<td>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'mark_closed_auto_del_attchmnts' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Add', 'ayuda-help-desk' ); ?></a>
				<a href="<?php echo esc_url( mumei_ayuda_tool_link( 'remove_mark_closed_auto_del_attchmnts' ) ); ?>" class="button-secondary"><?php esc_html_e( 'Remove', 'ayuda-help-desk' ); ?></a>
				<span class="wpas-system-tools-desc"><?php esc_html_e( 'Add or Remove auto delete attachments flag on closed tickets.', 'ayuda-help-desk' ); ?></span>
			</td>
		</tr>


	</tbody>
</table>

<?php do_action( 'mumei_ayuda_system_tools_after' ); ?>
