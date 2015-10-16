<?php
/* Get the tickets object */
global $wpas_tickets;

if ( $wpas_tickets->have_posts() ):

	$columns = wpas_get_tickets_list_columns();
	?>
	<div class="wpas wpas-ticket-list">
		<table id="wpas_ticketlist" class="wpas-table wpas-table-hover">
			<thead>
				<tr>
					<?php foreach ( $columns as $column_id => $column ) {
						echo "<th id='wpas-ticket-$column_id'>" . $column['title'] . "</th>";
					} ?>
				</tr>
			</thead>
			<tbody>
				<?php
				while( $wpas_tickets->have_posts() ):

					$wpas_tickets->the_post();

					echo '<tr>';

					foreach ( $columns as $column_id => $column ) {

						echo '<td';

						/* If current column is the date we add the date attribute for sorting purpose */
						if ( 'date' === $column_id ) {
							echo ' data-order="' . strtotime( get_the_time() ) . '"';
						}

						/* We don't forget to close the <td> tag */
						echo '>';

						/* Display the content for this column */
						wpas_get_tickets_list_column_content( $column_id, $column );

						echo '</td>';

					}

					echo '</tr>';
				
				endwhile;

				wp_reset_query(); ?>
			</tbody>
		</table>
		<?php if ( current_user_can( 'create_ticket' ) ) wpas_make_button( __( 'Open a ticket', 'wpas' ), array( 'type' => 'link', 'link' => wpas_get_submission_page_url(), 'class' => 'wpas-btn wpas-btn-default' ) ); ?>
	</div>
<?php else:
	echo wpas_get_notification_markup( 'info', sprintf( __( 'You haven\'t submitted a ticket yet. <a href="%s">Click here to submit your first ticket</a>.', 'wpas' ), wpas_get_submission_page_url() ) );
endif; ?>
