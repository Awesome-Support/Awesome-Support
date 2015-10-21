<?php
/**
 * Admin Tickets List.
 *
 * @package   Admin/Tickets List
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */

class WPAS_Tickets_List {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	public function __construct() {
		add_action( 'manage_ticket_posts_columns',       array( $this, 'add_core_custom_columns' ),     16, 1 );
		add_action( 'manage_ticket_posts_custom_column', array( $this, 'core_custom_columns_content' ), 10, 2 );
		add_action( 'admin_menu',                        array( $this, 'hide_closed_tickets' ),         10, 0 );
		add_filter( 'the_excerpt',                       array( $this, 'remove_excerpt' ),              10, 1 );
		add_filter( 'post_row_actions',                  array( $this, 'remove_quick_edit' ),           10, 2 );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     3.0.0
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Remove Quick Edit action
	 *
	 * @since   3.1.6
	 * @global  object $post
	 *
	 * @param   array  $actions An array of row action links.
	 *
	 * @return  array               Updated array of row action links
	 */
	public function remove_quick_edit( $actions ) {
		global $post;

		if ( $post->post_type === 'ticket' ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}
        
	/**
	 * Add age custom column.
	 *
	 * Add this column after the date.
	 *
	 * @since  3.0.0
	 * @param  array $columns List of default columns
	 * @return array          Updated list of columns
	 */
	public function add_core_custom_columns( $columns ) {

		$new = array();

		/**
		 * Parse the old columns and add the new ones.
		 */
		foreach ( $columns as $col_id => $col_label ) {

			if ( 'title' === $col_id ) {
				$new['ticket_id'] = '#';
			}

			/* Remove the date column that's replaced by the activity column */
			if ( 'date' !== $col_id ) {
				$new[$col_id] = $col_label;
			} else {
				/* If agents can see all tickets do nothing */
				if (
					current_user_can( 'administrator' )
					&& true === boolval( wpas_get_option( 'admin_see_all' ) )
					|| current_user_can( 'edit_ticket' )
					&& !current_user_can( 'administrator' )
					&& true === boolval( wpas_get_option( 'agent_see_all' ) ) ) {
						$new = array_merge( $new, array( 'wpas-assignee' => __( 'Support Staff', 'awesome-support' ) ) );
				}

				/* Add the activity column */
				$new = array_merge( $new, array( 'wpas-activity' => __( 'Activity', 'awesome-support' ) ) );
			}

		}

		return $new;
	}

	/**
	 * Manage core column content.
	 *
	 * @since  3.0.0
	 * @param  array   $column  Column currently processed
	 * @param  integer $post_id ID of the post being processed
	 */
	public function core_custom_columns_content( $column, $post_id ) {

		switch ( $column ) {

			case 'ticket_id':

				$link = add_query_arg( array( 'post' => $post_id, 'action' => 'edit' ), admin_url( 'post.php' ) );
				echo "<a href='$link'>#$post_id</a>";

				break;

			case 'wpas-assignee':

				$assignee = get_post_meta( $post_id, '_wpas_assignee', true );
				$agent    = get_user_by( 'id', $assignee );

				if ( is_object( $agent ) && is_a( $agent, 'WP_User' ) ) {
					echo $agent->data->display_name;
				}

				break;

			case 'wpas-activity':

				$latest        = null;
				$tags          = array();
				$activity_meta = get_transient( "wpas_activity_meta_post_$post_id" );

				if ( false === $activity_meta ) {

					$post                         = get_post( $post_id );
					$activity_meta                = array();
					$activity_meta['ticket_date'] = $post->post_date;

					/* Get the last reply if any */
					$latest = new WP_Query(  array(
						'posts_per_page'         =>	1,
						'orderby'                =>	'post_date',
						'order'                  =>	'DESC',
						'post_type'              =>	'ticket_reply',
						'post_parent'            =>	$post_id,
						'post_status'            =>	array( 'unread', 'read' ),
						'no_found_rows'          => true,
						'cache_results'          => false,
						'update_post_term_cache' => false,
						'update_post_meta_cache' => false,
						)
					);

					if ( !empty( $latest->posts ) ) {
						$user_data                      = get_user_by( 'id', $latest->post->post_author );
						$activity_meta['user_link']     = add_query_arg( array( 'user_id' => $latest->post->post_author ), admin_url( 'user-edit.php' ) );
						$activity_meta['user_id']       = $latest->post->post_author;
						$activity_meta['user_nicename'] = $user_data->user_nicename;
						$activity_meta['reply_date']    = $latest->post->post_date;
					}

					set_transient( "wpas_activity_meta_post_$post_id", $activity_meta, apply_filters( 'wpas_activity_meta_transient_lifetime', 60*60*1 ) ); // Set to 1 hour by default

				}

				echo '<ul>';

				// if ( isset( $mode ) && 'details' == $mode ):
				if ( 1 === 1 ):

					?><li><?php printf( _x( 'Created %s ago.', 'Ticket created on', 'awesome-support' ), human_time_diff( get_the_time( 'U', $post_id ), current_time( 'timestamp' ) ) ); ?></li><?php

					/**
					 * We check when was the last reply (if there was a reply).
					 * Then, we compute the ticket age and if it is considered as
					 * old, we display an informational tag.
					 */
					if ( !isset( $activity_meta['reply_date'] ) ) {
						echo '<li>';
						echo _x( 'No reply yet.', 'No last reply', 'awesome-support' );
						echo '</li>';
					} else {

						$args = array(
							'post_parent'            => $post_id,
							'post_type'              => 'ticket_reply',
							'post_status'            => array( 'unread', 'read' ),
							'posts_per_page'         => - 1,
							'orderby'                => 'date',
							'order'                  => 'DESC',
							'no_found_rows'          => true,
							'cache_results'          => false,
							'update_post_term_cache' => false,
							'update_post_meta_cache' => false,
						);

						$query = new WP_Query( $args );
						$role  = true === user_can( $activity_meta['user_id'], 'edit_ticket' ) ? _x( 'agent', 'User role', 'awesome-support' ) : _x( 'client', 'User role', 'awesome-support' );

						?><li><?php echo _x( sprintf( _n( '%s reply.', '%s replies.', $query->post_count, 'awesome-support' ), $query->post_count ), 'Number of replies to a ticket', 'awesome-support' ); ?></li><?php
						?><li><?php printf( _x( '<a href="%s">Last replied</a> %s ago by %s (%s).', 'Last reply ago', 'awesome-support' ), add_query_arg( array( 'post' => $post_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) . '#wpas-post-' . $query->posts[0]->ID, human_time_diff( strtotime( $activity_meta['reply_date'] ), current_time( 'timestamp' ) ), '<a href="' . $activity_meta['user_link'] . '">' . $activity_meta['user_nicename'] . '</a>', $role ); ?></li><?php
					}

				endif;

				/**
				 * Add tags
				 */
				if ( true === wpas_is_reply_needed( $post_id, $latest ) ) {
					$color = ( false !== ( $c = wpas_get_option( 'color_awaiting_reply', false ) ) ) ? $c : '#0074a2';
					array_push( $tags, "<span class='wpas-label' style='background-color:$color;'>" . __( 'Awaiting Support Reply', 'awesome-support' ) . "</span>" );
				}


				if ( true === wpas_is_ticket_old( $post_id, $latest ) ) {
					$old_color = wpas_get_option( 'color_old' );
					array_push( $tags, "<span class='wpas-label' style='background-color:$old_color;'>" . __( 'Old', 'awesome-support' ) . "</span>" );
				}

				if ( !empty( $tags ) ) {
					echo '<li>' . implode( ' ', $tags ) . '</li>';
				}

				echo '</ul>';

				break;

		}

	}

	/**
	 * Hide closed tickets.
	 *
	 * If the plugin is set to hide closed tickets,
	 * we modify the "All Tickets" link in the post type menu
	 * and append the status filter with the "open" value.
	 *
	 * @since  3.0.0
	 * @return bool True if the closed tickets were hiddne, false otherwise
	 */
	public function hide_closed_tickets() {

		$hide = (bool) wpas_get_option( 'hide_closed' );

		if ( true !== $hide ) {
			return false;
		}

		global $submenu;

		if ( is_array( $submenu ) && array_key_exists( 'edit.php?post_type=ticket', $submenu ) && isset( $submenu[5] ) ) {
			$submenu["edit.php?post_type=ticket"][5][2] = $submenu["edit.php?post_type=ticket"][5][2] . '&amp;wpas_status=open';
		}

		return true;

	}

	/**
	 * Remove the ticket excerpt.
	 *
	 * We don't want ot display the ticket excerpt in the tickets list
	 * when the excerpt mode is selected.
	 * 
	 * @param  string $content Ticket excerpt
	 * @return string          Excerpt if applicable or empty string otherwise
	 */
	public function remove_excerpt( $content ) {

		global $mode;

		if ( !is_admin() ||! isset( $_GET['post_type'] ) || 'ticket' !== $_GET['post_type'] ) {
			return $content;
		}

		global $mode;

		if ( 'excerpt' === $mode ) {
			return '';
		}

		return $content;
	}

}