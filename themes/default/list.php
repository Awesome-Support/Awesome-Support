<?php
/* Get the tickets object */
global $wpas_tickets;

if ( $wpas_tickets->have_posts() ):

	/* Get list of columns to display */
	$columns 		  = wpas_get_tickets_list_columns();
	
	/* Get number of tickets per page */
	$tickets_per_page = wpas_get_option( 'tickets_per_page_front_end' );
	If ( empty($tickets_per_page) ) {
		$tickets_per_page = 5 ; // default number of tickets per page to 5 if no value specified.
	}
	
	?>
	<div class="wpas wpas-ticket-list">

		<?php wpas_get_template( 'partials/ticket-navigation' ); ?>

		<!-- Filters & Search tickets -->
		<div class="wpas-row" id="wpas_ticketlist_filters">
			<div class="wpas-one-third">
				<select class="wpas-form-control wpas-filter-status">
					<option value=""><?php esc_html_e('Any status', 'awesome-support'); ?></option>
				</select>
			</div>
			<div class="wpas-one-third"></div>
			<div class="wpas-one-third" id="wpas_filter_wrap">
				<input class="wpas-form-control" id="wpas_filter" type="text" placeholder="<?php esc_html_e('Search tickets...', 'awesome-support'); ?>">
				<span class="wpas-clear-filter" title="<?php esc_html_e('Clear Filter', 'awesome-support'); ?>"></span>
			</div>
		</div>

		<!-- List of tickets -->
		<table id="wpas_ticketlist" class="wpas-table wpas-table-hover" data-filter="#wpas_filter" data-filter-text-only="true" data-page-navigation=".wpas_table_pagination" data-page-size=" <?php echo $tickets_per_page ?> ">
			<thead>
				<tr>
					<?php foreach ( $columns as $column_id => $column ) {

						$data_attributes = '';

						// Add the data attributes if any
						if ( isset( $column['column_attributes']['head'] ) && is_array( $column['column_attributes']['head'] ) ) {
							$data_attributes = wpas_array_to_data_attributes( $column['column_attributes']['head'] );
						}

						printf( '<th id="wpas-ticket-%1$s" %3$s>%2$s</th>', $column_id, $column['title'], $data_attributes );

					} ?>
				</tr>
			</thead>
			<tbody>
				<?php
				while( $wpas_tickets->have_posts() ):

					$wpas_tickets->the_post();

					echo '<tr class="wpas-status-' . wpas_get_ticket_status( $wpas_tickets->post->ID ) . '" id="wpas_ticket_' . $wpas_tickets->post->ID . '">';

					foreach ( $columns as $column_id => $column ) {

						$data_attributes = '';

						// Add the data attributes if any
						if ( isset( $column['column_attributes']['body'] ) && is_array( $column['column_attributes']['body'] ) ) {
							$data_attributes = wpas_array_to_data_attributes( $column['column_attributes']['body'], true );
						}

						printf( '<td %s>', $data_attributes );

						/* Display the content for this column */
						wpas_get_tickets_list_column_content( $column_id, $column );

						echo '</td>';

					}

					echo '</tr>';
				
				endwhile;

				wp_reset_query(); ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="<?php echo count($columns); ?>">
						<ul class="wpas_table_pagination"></ul>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
<?php else:
	echo wpas_get_notification_markup( 'info', sprintf( __( 'You haven\'t submitted a ticket yet. <a href="%s">Click here to submit your first ticket</a>.', 'awesome-support' ), wpas_get_submission_page_url() ) );
endif; ?>