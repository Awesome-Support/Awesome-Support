<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/simple-hint/2.1.1/simple-hint.min.css">
<?php
/**
 * User Profile.
 *
 * This metabox is used to display the user profile. It gives quick access to basic information about the client.
 *
 * @since 3.3
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $post;

// Get the user object
$user = get_userdata( $post->post_author );

// Get tickets
$open   = wpas_get_tickets( 'open' );
$closed = wpas_get_tickets( 'closed' );

// Sort open tickets
$by_status  = array();
$all_status = wpas_get_post_status();

foreach ( $open as $t ) {

	if ( ! is_a( $t, 'WP_Post' ) ) {
		continue;
	}

	if ( ! array_key_exists( $t->post_status, $all_status ) ) {
		continue;
	}

	if ( ! array_key_exists( $t->post_status, $by_status ) ) {
		$by_status[ $t->post_status ] = array();
	}

	$by_status[ $t->post_status ][] = $t;

}

// Add the closed tickets in the list
$by_status['closed'] = $closed;
?>
<div id="wpas-up">

	<div class="wpas-up-contact-details wpas-cf">
		<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $user->ID ) ); ?>">
			<?php echo get_avatar( $user->ID, '80', 'mm', $user->data->user_nicename, array( 'class' => 'wpas-up-contact-img' ) ); ?>
		</a>
		<div class="wpas-up-contact-name"><?php echo $user->data->user_nicename; ?></div>
		<div class="wpas-up-contact-role"><?php echo wp_kses_post( sprintf( __( 'Support User since %s', 'awesome-support' ), '<strong>' . date( get_option( 'date_format' ), strtotime( $user->data->user_registered ) ) . '</strong>' ) ); ?></div>
		<div class="wpas-up-contact-email"><a href="mailto:<?php echo $user->data->user_email; ?>"><?php echo $user->data->user_email; ?></a></div>
		<!-- <em class="wpas-up-contact-replytime">Usually replies within 4 hours</em> -->
	</div>
	
	<div class="wpas-row wpas-up-stats">
		<div class="wpas-col wpas-up-stats-all">
			<strong><?php echo count( $open ) + count( $closed ); ?></strong>
			<?php echo esc_html__( 'Total', 'awesome-support' ); ?>
		</div>
		<div class="wpas-col wpas-up-stats-open">
			<strong><?php echo count( $open ); ?></strong>
			<?php echo esc_html__( 'Open', 'awesome-support' ); ?>
		</div>
		<div class="wpas-col wpas-up-stats-closed">
			<strong><?php echo count( $closed ); ?></strong>
			<?php echo esc_html__( 'Closed', 'awesome-support' ); ?>
		</div>
	</div>

	<div class="wpas-up-tickets">
		<?php
		foreach ( $by_status as $status => $tickets ) {

			$status_label = 'closed' === $status ? esc_html__( 'Closed', 'awesome-support' ) : $all_status[ $status ];
			$lis = sprintf( '<li><span class="wpas-label" style="background-color:%1$s;">%2$s ▾</span></li>', wpas_get_option( "color_$status", '#dd3333' ), $status_label );

			foreach ( $tickets as $t ) {
				$created = sprintf( esc_html_x( 'Created on %s', 'Ticket date creation', 'awesome-support' ), date( get_option( 'date_format' ), strtotime( $t->post_date ) ) );
				$title   = apply_filters( 'the_title', $t->post_title );
				$link    = esc_url( admin_url( "post.php?post=$t->ID&action=edit" ) );
				$lis .= sprintf( '<li data-hint="%1$s" class="hint-left hint-anim"><a href="%3$s">%2$s</a></li>', $created, $title, $link );
			}

			printf( '<ul>%s</ul>', $lis );

		}
		?>

		<!-- @todo <a href="/wp-admin/edit.php?post_type=ticket" class="button">View all tickets</a> -->
	</div>

</div>