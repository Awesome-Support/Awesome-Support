<?php
add_shortcode( 'tickets', 'mumei_ayuda_sc_client_account' );
/**
 * Registration page shortcode.
 */
function mumei_ayuda_sc_client_account() {

	global $mumei_ayuda_tickets, $post;

	$mumei_ayuda_tickets = mumei_ayuda_get_tickets_for_shortcode() ;

	/* Get the ticket content */
	ob_start();

	/**
	 * mumei_ayuda_frontend_plugin_page_top is executed at the top
	 * of every plugin page on the front end.
	 */
	do_action( 'mumei_ayuda_frontend_plugin_page_top', $post->ID, $post );

	/**
	 * mumei_ayuda_before_tickets_list hook
	 */
	do_action( 'mumei_ayuda_before_tickets_list' );

	/* If user is not logged in we display the register form */
	if ( !is_user_logged_in() ):

		$registration = mumei_ayuda_get_option( 'login_page', false );

		if ( false !== $registration && !empty( $registration ) && !is_null( get_post( intval( $registration ) ) ) ) {
			/* As the headers are already sent we can't use wp_redirect. */
			echo '<meta http-equiv="refresh" content="0; url=' . esc_url( get_permalink( $registration ) ) . '" />';
			mumei_ayuda_get_notification_markup( 'info', __( 'You are being redirected...', 'ayuda-help-desk' ) );
			exit;
		}

		mumei_ayuda_get_template( 'registration' );

	else:

		/**
		 * Get the custom template.
		 */
		mumei_ayuda_get_template( 'list' );

	endif;

	/**
	 * mumei_ayuda_after_tickets_list hook
	 */
	do_action( 'mumei_ayuda_after_tickets_list' );

	/**
	 * Finally get the buffer content and return.
	 *
	 * @var string
	 */
	$content = ob_get_clean();

	return $content;

}
/**
 * Get the list of tickets that should be shown in the [tickets] shortcode.
 *
 * @since 4.4.0
 *
 * @param none
 *
 * @return array post array of tickets found
 */
function mumei_ayuda_get_tickets_for_shortcode() {

	global $current_user, $post;

	/**
	 * For some reason when the user ID is set to 0
	 * the query returns posts whose author has ID 1.
	 * In order to avoid that (for non logged users)
	 * we set the user ID to -1 if it is 0.
	 *
	 * @var integer
	 */
	$author = ( 0 !== $current_user->ID ) ? $current_user->ID : -1;

	$args = array(
		'author'                 => $author,
		'post_type'              => 'ticket',
		'post_status'            => 'any',
		'order'                  => 'DESC',
		'orderby'                => 'date',
		'posts_per_page'         => - 1,
		'no_found_rows'          => false,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	) ;

	/* Maybe only show open tickets */
	if ( true === boolval( mumei_ayuda_get_option( 'hide_closed_fe', false) ) ) {
		$args_meta = array(
			'meta_query' => array(
				array(
					'key'     => '_mumei_ayuda_status',
					'value'   => 'closed',
					'compare' => '!=',
				),
			),
		) ;

		$args = array_merge($args, $args_meta);
	}

	$args = apply_filters( 'mumei_ayuda_tickets_shortcode_query_args', $args );

	$mumei_ayuda_tickets_found = new WP_Query( $args );

	return $mumei_ayuda_tickets_found ;

}
