<?php
/* Get the tickets object */
global $mumei_ayuda_tickets;

if ( $mumei_ayuda_tickets->have_posts() ):

	/* Get list of columns to display */
	$columns 		  = mumei_ayuda_get_tickets_list_columns();

	/* Get number of tickets per page */
	$tickets_per_page = mumei_ayuda_get_option( 'tickets_per_page_front_end' );
	If ( empty($tickets_per_page) ) {
		$tickets_per_page = 5 ; // default number of tickets per page to 5 if no value specified.
	}

	?>
	<style type="text/css">
	.wrap .content-area main article .entry-content
	{
		min-width: 100%;
	}
	</style>
	<div class="wpas wpas-ticket-list alignwide">

		<?php mumei_ayuda_get_template( 'partials/ticket-navigation' ); ?>

		<!-- Filters & Search tickets -->
		<div class="wpas-row" id="mumei_ayuda_ticketlist_filters">
			<div class="wpas-one-third">
				<select class="wpas-form-control wpas-filter-status">
					<option value=""><?php esc_html_e('Any status', 'ayuda-help-desk'); ?></option>
				</select>
			</div>
			<div class="wpas-one-third"></div>
			<div class="wpas-one-third" id="mumei_ayuda_filter_wrap">
				<input class="wpas-form-control" id="mumei_ayuda_filter" type="text" placeholder="<?php esc_html_e('Search tickets...', 'ayuda-help-desk'); ?>">
				<span class="wpas-clear-filter" title="<?php esc_html_e('Clear Filter', 'ayuda-help-desk'); ?>"></span>
			</div>
		</div>

		<!-- List of tickets -->
		<table id="mumei_ayuda_ticketlist" class="wpas-table wpas-table-hover" data-filter="#mumei_ayuda_filter" data-filter-text-only="true" data-page-navigation=".mumei_ayuda_table_pagination" data-page-size=" <?php echo esc_attr( $tickets_per_page ); ?> ">
			<thead>
				<tr>
					<?php foreach ( $columns as $column_id => $column ) {

						$data_attributes = '';

						// Add the data attributes if any
						if ( isset( $column['column_attributes']['head'] ) && is_array( $column['column_attributes']['head'] ) ) {
							$data_attributes = mumei_ayuda_array_to_data_attributes( $column['column_attributes']['head'] );
						}

						printf( '<th id="wpas-ticket-%1$s" %3$s>%2$s</th>', esc_attr($column_id), wp_kses_post($column['title']), wp_kses($data_attributes, get_allowed_html_wp_notifications()) );

					} ?>
				</tr>
			</thead>
			<tbody>
				<?php
				while( $mumei_ayuda_tickets->have_posts() ):

					$mumei_ayuda_tickets->the_post();

					echo '<tr class="wpas-status-' . esc_attr( mumei_ayuda_get_ticket_status( $mumei_ayuda_tickets->post->ID ) ) . '" id="mumei_ayuda_ticket_' . esc_attr( $mumei_ayuda_tickets->post->ID ) . '">';

					foreach ( $columns as $column_id => $column ) {

						$data_attributes = '';

						// Add the data attributes if any
						if ( isset( $column['column_attributes']['body'] ) && is_array( $column['column_attributes']['body'] ) ) {
							$data_attributes = mumei_ayuda_array_to_data_attributes( $column['column_attributes']['body'], true );
						}

						printf( '<td %s>', wp_kses($data_attributes, get_allowed_html_wp_notifications()) );

						/* Display the content for this column */
						mumei_ayuda_get_tickets_list_column_content( $column_id, $column );

						echo '</td>';

					}

					echo '</tr>';

				endwhile;

				wp_reset_query(); ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="<?php echo count($columns); ?>">
						<ul class="mumei_ayuda_table_pagination"></ul>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
<?php else:
	// translators: %s is the submit ticket link.
	$x_content = __( 'You haven\'t submitted a ticket yet. <a href="%s">Click here to submit your first ticket</a>.', 'ayuda-help-desk' );
	echo wp_kses(mumei_ayuda_get_notification_markup( 'info', sprintf( $x_content, mumei_ayuda_get_submission_page_url() ) ), get_allowed_html_wp_notifications());
endif; ?>
