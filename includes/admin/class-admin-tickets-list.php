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
		add_filter( 'the_title',                         array( $this, 'add_ticket_id_title' ) );
		add_action( 'pre_get_posts',                     array( $this, 'filter_staff' ) );
		add_filter( 'post_class',                        array( $this, 'ticket_row_class' ), 10, 3 );
		add_action( 'pre_get_posts', 					 array( $this, 'set_ordering_query_var' ) );
		add_filter( 'posts_results', 					 array( $this, 'apply_ordering_criteria' ), 10, 2 );

	}

	/**
	 *  Called by the 'pre_get_posts' filter hook this method sets
	 *  the following to true when for the admin ticket list page:
	 *
	 *        $wp_query->query_var['wpas_order_by_urgency']
	 *
	 *  Setting this to true will trigger modifications to the query that
	 *  will be made in the apply_ordering_criteria() function called by
	 *  the 'posts_clauses' filter hook.
	 *
	 * @since    3.3
	 *
	 * @param WP_Query $query
	 *
	 * @return void
	 */
	public function set_ordering_query_var( $query ) {

		global $pagenow;

		if ( $query->is_main_query() && 'edit.php' === $pagenow && 'ticket' === $query->get( 'post_type' ) && wpas_has_smart_tickets_order() ) {

			/**
			 * Inspect the current context and if appropriate specify a query_var to allow
			 * WP_Query to modify itself based on arguments passed to WP_Query.
			 */
			$query->set( 'wpas_order_by_urgency', true );

		}

	}

	/**
	 *  Called by the 'posts_clauses' filter hook this method
	 *  modifies WP_Query SQL for ticket post types when:
	 *
	 *        $wp_query->get('wpas_order_by_urgency') === true
	 *
	 *  The query var 'wpas_order_by_urgency' will be set in the
	 *  set_ordering_query_var() function called by the 'pre_get_posts'
	 *  action hook.
	 *
	 * @since    3.3
	 *
	 * @param WP_Post[] $posts
	 * @param WP_Query  $query
	 *
	 * @return WP_Post[]
	 */
	public function apply_ordering_criteria( $posts, $query ) {

		if ( $query->get( 'wpas_order_by_urgency' )  ) {

			/**
			 * Hooks in WP_Query should never modify SQL based on context.
			 * Instead they should modify based on a query_var so they can
			 * be tested and side-effects are minimized.
			 */

			/**
			 * @var wpdb $wpdb
			 *
			 */
			global $wpdb;

			$sql =<<<SQL
SELECT 
	wpas_ticket.ID AS ticket_id,
	wpas_ticket.post_title AS ticket_title,
	wpas_reply.ID AS reply_id,
	wpas_reply.post_title AS reply_title,
	wpas_replies.reply_count AS reply_count,
	wpas_replies.latest_reply,
	wpas_ticket.post_author=wpas_reply.post_author AS client_replied_last
FROM 
	{$wpdb->posts} AS wpas_ticket 
	INNER JOIN {$wpdb->postmeta} AS wpas_postmeta ON wpas_ticket.ID=wpas_postmeta.post_id AND '_wpas_status'=wpas_postmeta.meta_key AND 'open'=CAST(wpas_postmeta.meta_value AS CHAR)
	LEFT OUTER JOIN {$wpdb->posts} AS wpas_reply ON wpas_ticket.ID=wpas_reply.post_parent
	LEFT OUTER JOIN (
		SELECT
			post_parent AS ticket_id,
			COUNT(*) AS reply_count,
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

            $no_replies = $client_replies = $agent_replies = array();

            foreach( $posts as $post ) {

                $no_replies[ $post->ID ] = $post;

            }

			/**
			 * The post order will be modifiedusing the following logic:
			 *
			 * 		Order 	- 	Ticket State
			 *		-----   	-------------------------------------------
			 * 		 1st   	- 	No reply - older since request made
			 * 	 	 2nd 	- 	No reply - newer since request made
			 * 	 	 3rd 	- 	Reply - older response since client replied
			 * 	 	 4th 	- 	Reply - newer response since client replied
			 * 	 	 5th 	- 	Reply - newer response since agent replied
			 * 	 	 6th 	- 	Reply - older response since agent replied
			 */

			foreach( $wpdb->get_results( $sql ) as $reply_post ) {

				if ( isset( $no_replies[ $reply_post->ticket_id ] ) ) {

					if ( $reply_post->client_replied_last ) {
						$client_replies[ $reply_post->ticket_id ] = $no_replies[ $reply_post->ticket_id ];
					} else {
						$agent_replies[ $reply_post->ticket_id ] = $no_replies[ $reply_post->ticket_id ];
					}

					unset( $no_replies[ $reply_post->ticket_id ] );

				}

			}

			$posts = array_values( $no_replies + $client_replies + array_reverse( $agent_replies, true ) );

		}

		return $posts;

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

			// We add all our columns where the date was and move the date column to the end
			if ( 'date' === $col_id ) {

				// Add the client column
				$new['wpas-client'] = esc_html__( 'Created By', 'awesome-support' );

				// If agents can see all tickets do nothing
				if (
					current_user_can( 'administrator' )
					&& true === boolval( wpas_get_option( 'admin_see_all' ) )
					|| current_user_can( 'edit_ticket' )
					   && ! current_user_can( 'administrator' )
					   && true === boolval( wpas_get_option( 'agent_see_all' ) )
				) {
					$new['wpas-assignee'] = esc_html__( 'Assigned To', 'awesome-support' );
				}

			} else {
				$new[ $col_id ] = $col_label;
			}

		}

		// Finally we re-add the date
		$new['date'] = $columns['date'];

		// Add the activity column
		$new['wpas-activity'] = esc_html__( 'Activity', 'awesome-support' );

		return $new;

	}

	/**
	 * Add ticket ID to the ticket title in admin list screen
	 *
	 * @since 3.3
	 *
	 * @param string $title Original title
	 *
	 * @return string
	 */
	public function add_ticket_id_title( $title ) {

		global $pagenow;

		if ( 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'ticket' !== $_GET['post_type'] ) {
			return $title;
		}

		$id = get_the_ID();

		$title = "$title (#$id)";

		return $title;

	}

	/**
	 * Get all ticket replies
	 *
	 * Try to get the replies from cache and if not possible, run the query and cache the result.
	 *
	 * @since 3.3
	 *
	 * @param int $ticket_id ID of the ticket we want to get the replies for
	 *
	 * @return WP_Query
	 */
	public function get_replies_query( $ticket_id ) {

		$q = wp_cache_get( 'replies_query_' . $ticket_id, 'wpas' );

		if ( false === $q ) {

			$args = array(
				'post_parent'            => $ticket_id,
				'post_type'              => 'ticket_reply',
				'post_status'            => array( 'unread', 'read' ),
				'posts_per_page'         => - 1,
				'orderby'                => 'date',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'cache_results'          => false,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			);

			$q = new WP_Query( $args );

			// Cache the result
			wp_cache_add( 'replies_query_' . $ticket_id, $q, 'wpas', 600 );

		}

		return $q;

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

			case 'wpas-assignee':

				$assignee = (int) get_post_meta( $post_id, '_wpas_assignee', true );
				$agent    = get_user_by( 'id', $assignee );
				$link     = add_query_arg( array( 'post_type' => 'ticket', 'staff' => $assignee ), admin_url( 'edit.php' ) );

				if ( is_object( $agent ) && is_a( $agent, 'WP_User' ) ) {
					echo "<a href='$link'>{$agent->data->display_name}</a>";
				}

				break;

			case 'wpas-client':

				$client = get_user_by( 'id', get_the_author_meta( 'ID' ) );
				$link   = add_query_arg( array( 'post_type' => 'ticket', 'author' => $client->ID ), admin_url( 'edit.php' ) );

				echo "<a href='$link'>$client->display_name</a><br>$client->user_email";

				break;

			case 'wpas-activity':

				$tags    = array();
				$replies = $this->get_replies_query( $post_id );

				/**
				 * We check when was the last reply (if there was a reply).
				 * Then, we compute the ticket age and if it is considered as
				 * old, we display an informational tag.
				 */
				if ( 0 === $replies->post_count ) {
					echo _x( 'No reply yet.', 'No last reply', 'awesome-support' );
				} else {

					$last_reply     = $replies->posts[ $replies->post_count - 1 ];
					$last_user_link = add_query_arg( array( 'user_id' => $last_reply->post_author ), admin_url( 'user-edit.php' ) );
					$last_user      = get_user_by( 'id', $last_reply->post_author );
					$role           = true === user_can( $last_reply->post_author, 'edit_ticket' ) ? _x( 'agent', 'User role', 'awesome-support' ) : _x( 'client', 'User role', 'awesome-support' );

					echo _x( sprintf( _n( '%s reply.', '%s replies.', $replies->post_count, 'awesome-support' ), $replies->post_count ), 'Number of replies to a ticket', 'awesome-support' );
					echo '<br>';
					printf( _x( '<a href="%s">Last replied</a> %s ago by %s (%s).', 'Last reply ago', 'awesome-support' ), add_query_arg( array(
							'post'   => $post_id,
							'action' => 'edit'
						), admin_url( 'post.php' ) ) . '#wpas-post-' . $last_reply->ID, human_time_diff( strtotime( $last_reply->post_date ), current_time( 'timestamp' ) ), '<a href="' . $last_user_link . '">' . $last_user->user_nicename . '</a>', $role );
				}

				// Maybe add the "Awaiting Support Response" tag
				if ( true === wpas_is_reply_needed( $post_id, $replies ) ) {
					$color = ( false !== ( $c = wpas_get_option( 'color_awaiting_reply', false ) ) ) ? $c : '#0074a2';
					array_push( $tags, "<span class='wpas-label' style='background-color:$color;'>" . __( 'Awaiting Support Reply', 'awesome-support' ) . "</span>" );
				}

				// Maybe add the "Old" tag
				if ( true === wpas_is_ticket_old( $post_id, $replies ) ) {
					$old_color = wpas_get_option( 'color_old' );
					array_push( $tags, "<span class='wpas-label' style='background-color:$old_color;'>" . __( 'Old', 'awesome-support' ) . "</span>" );
				}

				if ( ! empty( $tags ) ) {
					echo '<br>' . implode( ' ', $tags );
				}

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

	/**
	 * Filter tickets by assigned staff
	 *
	 * @since 3.3
	 *
	 * @param WP_Query $wp_query
	 *
	 * @return void
	 */
	public function filter_staff( $wp_query ) {

		global $pagenow;

		if ( 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'ticket' !== $_GET['post_type'] ) {
			return;
		}

		if ( ! $wp_query->is_main_query() ) {
			return;
		}

		if ( ! isset( $_GET['staff'] ) ) {
			return;
		}

		$staff_id = (int) $_GET['staff'];
		$agent    = new WPAS_Member_Agent( $staff_id );

		if ( ! $agent->is_agent() ) {
			return;
		}

		$meta_query = $wp_query->get( 'meta_query' );

		if ( ! is_array( $meta_query ) ) {
			$meta_query = (array) $meta_query;
		}

		$meta_query[] = array(
			'key'     => '_wpas_assignee',
			'value'   => $staff_id,
			'compare' => '='
		);

		if ( ! isset( $meta_query['relation'] ) ) {
			$meta_query['relation'] = 'AND';
		}

		$wp_query->set( 'meta_query', $meta_query );

	}

	/**
	 * Filter the list of CSS classes for the current post.
	 *
	 * @since 3.3
	 *
	 * @param array $classes An array of post classes.
	 * @param array $class   An array of additional classes added to the post.
	 * @param int   $post_id The post ID.
	 *
	 * @return array
	 */
	public function ticket_row_class( $classes, $class, $post_id ) {

		global $pagenow;

		if ( 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || isset( $_GET['post_type'] ) && 'ticket' !== $_GET['post_type'] ) {
			return $classes;
		}

		if ( ! is_admin() ) {
			return $classes;
		}

		if ( 'ticket' !== get_post_type( $post_id ) ) {
			return $classes;
		}

		$replies = $this->get_replies_query( $post_id );

		if ( true === wpas_is_reply_needed( $post_id, $replies ) ) {
			$classes[] = 'wpas-awaiting-support-reply';
		}

		return $classes;

	}

}