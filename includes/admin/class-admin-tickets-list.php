<?php
/**
 * Admin Tickets List.
 *
 * @package   Admin/Tickets List
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
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

		if( is_admin() ) {

			/**
			 * Add custom columns
			 */
			add_action( 'manage_ticket_posts_columns',          array( $this, 'add_custom_columns' ), 10, 1 );
			add_action( 'manage_ticket_posts_columns',          array( $this, 'move_status_first' ), 15, 1 );
			add_action( 'manage_ticket_posts_custom_column',    array( $this, 'custom_columns_content' ), 10, 2 );
			add_filter( 'manage_edit-ticket_sortable_columns',  array( $this, 'custom_columns_sortable' ), 10, 1 );

			/**
			 * Add the taxonomies filters
			 */
			add_action( 'restrict_manage_posts',                array( $this, 'custom_filters' ), 8, 2 );
			add_action( 'restrict_manage_posts',                array( $this, 'custom_taxonomy_filter' ), 10, 2 );
			add_filter( 'parse_query',                          array( $this, 'custom_taxonomy_filter_convert_id_term' ), 10, 1 );
			add_filter( 'parse_query',                          array( $this, 'custom_meta_query' ), 11, 1 );
			add_filter( 'posts_clauses',                        array( $this, 'post_clauses_orderby' ), 5, 2 );
			add_filter( 'posts_where',                          array( $this, 'posts_where' ), 10, 2 );
			add_action( 'parse_request',                        array( $this, 'parse_request' ), 10, 1 );
			add_action( 'pre_get_posts',                        array( $this, 'set_ordering_query_var' ), 100, 1 );
			add_filter( 'posts_results', 					    array( $this, 'apply_ordering_criteria' ), 10, 2 );

			add_filter( 'wpas_add_custom_fields',               array( $this, 'add_custom_fields' ) );

			add_action( 'admin_menu',                           array( $this, 'hide_closed_tickets' ),         10, 0 );
			add_filter( 'the_excerpt',                          array( $this, 'remove_excerpt' ),              10, 1 );
			add_filter( 'post_row_actions',                     array( $this, 'remove_quick_edit' ),           10, 2 );
			add_filter( 'post_class',                           array( $this, 'ticket_row_class' ), 10, 3 );
			add_filter( 'manage_posts_extra_tablenav',          array( $this, 'manage_posts_extra_tablenav' ), 10, 1 );

		}
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
	 * Add custom fields
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function add_custom_fields( $fields ) {

		global $pagenow, $typenow;

		$add_custom_fields = ( 'edit.php' !== $pagenow && 'ticket' !== $typenow ) ? false : true;
		if( !apply_filters( 'add_ticket_column_custom_fields', $add_custom_fields ) ) {
			return $fields;
		}

		wpas_add_custom_field( 'id', array(
			'show_column'     => true,
			'sortable_column' => true,
			'filterable'      => true,
			'title'           => __( 'ID', 'awesome-support' ),
		) );

		wpas_add_custom_field( 'wpas-client', array(
			'show_column'     => true,
			'sortable_column' => true,
			'filterable'      => true,
			'title'           => __( 'Created by', 'awesome-support' ),
		) );

		wpas_add_custom_field( 'wpas-activity', array(
			'show_column'     => true,
			'sortable_column' => true,
			'filterable'      => true,
			'title'           => __( 'Activity', 'awesome-support' ),
		) );

		return $this->get_custom_fields();

	}

	/**
     * Get custom fields
     *
	 * @return mixed
	 */
	public function get_custom_fields() {
		return WPAS()->custom_fields->get_custom_fields(); 

	}

	/**
	 * Add custom column.
	 *
	 * Add this column after the date.
	 *
	 * @since  3.0.0
	 * @param  array $columns List of default columns
	 * @return array          Updated list of columns
	 */
	public function add_custom_columns( $columns ) {

		$new = array();
		$custom = array();
		$fields = $this->get_custom_fields();

		/**
		 * Prepare all custom fields that are supposed to show up
		 * in the admin columns.
		 */
		foreach ( $fields as $field ) {

			/* If CF is a regular taxonomy we don't handle it, WordPress does */
			if ( 'taxonomy' == $field[ 'args' ][ 'field_type' ] && true === $field[ 'args' ][ 'taxo_std' ] ) {
				continue;
			}

			if ( true === $field[ 'args' ][ 'show_column' ] ) {
				$id = $field[ 'name' ];
				$title = apply_filters( 'wpas_custom_column_title', wpas_get_field_title( $field ), $field );
				$custom[ $id ] = $title;
			}

		}

		/**
		 * Parse the old columns and add the new ones.
		 */
		foreach ( $columns as $col_id => $col_label ) {

			// We add all our columns where the date was and move the date column to the end
			if ( 'date' === $col_id ) {

				if ( array_key_exists( 'status', $custom ) ) {
					$new[ 'status' ] = esc_html__( 'Status', 'awesome-support' );
				}

				$new[ 'title' ] = esc_html__( 'Title', 'awesome-support' );

				if ( array_key_exists( 'ticket_priority', $custom ) ) {
					$new[ 'ticket_priority' ] = $this->get_cf_title( 'ticket_priority', 'Priority' );
				}

				$new[ 'id' ] = esc_html__( 'ID', 'awesome-support' );

				if ( array_key_exists( 'product', $custom ) ) {
					$new[ 'product' ] = $this->get_cf_title( 'product', 'Product' );
				}

				if ( array_key_exists( 'department', $custom ) ) {
					$new[ 'department' ] = $this->get_cf_title( 'department', 'Department' );
				}

				if ( array_key_exists( 'ticket_channel', $custom ) ) {
					$new[ 'ticket_channel' ] = $this->get_cf_title( 'ticket_channel', 'Channel' );
				}

				if ( array_key_exists( 'ticket-tag', $custom ) ) {
					$new[ 'ticket-tag' ] = $this->get_cf_title( 'ticket-tag', 'Tag' );
				}

				// Add the client column
				$new[ 'wpas-client' ] = esc_html__( 'Created By', 'awesome-support' );

				// assignee/agent...
				$new[ 'assignee' ] = $this->get_cf_title( 'assignee', 'Agent' );				

				// Add the date
				$new[ 'date' ] = $columns[ 'date' ];

				$new[ 'wpas-activity' ] = $this->get_cf_title( 'wpas-activity', 'Activity' );

			} else {
				$new[ $col_id ] = $col_label;
			}

		}

		return array_merge( $new, $custom );

	}


	/**
	 * Return CF Title after applying filters
	 *
	 * @since 3.3.5
	 *
	 * @param $field_id
	 *
	 * @param $field_title
	 *
	 * @return string
	 */
	public function get_cf_title( $field_id, $field_title ) {

		$fields = $this->get_custom_fields();

		$field = $fields[ $field_id ];

		if( ! empty( $field ) ) {
			$field_title = apply_filters( 'wpas_custom_column_title', wpas_get_field_title( $field ), $field );
		}

		return esc_html__( $field_title, 'awesome-support' );

	}


	/**
	 * Manage core column content.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $column Column currently processed
	 *
	 * @param  integer $post_id ID of the post being processed
	 */
	public function custom_columns_content( $column, $post_id ) {

		$fields = $this->get_custom_fields();

		if ( isset( $fields[ $column ] ) ) {

			if ( true === $fields[ $column ][ 'args' ][ 'show_column' ] ) {

				switch ( $column ) {

					case 'id':

						$link = add_query_arg( array( 'post' => $post_id, 'action' => 'edit' ), admin_url( 'post.php' ) );
						echo "<strong><a href='$link'>{$post_id}</a></strong>";

						break;

					case 'wpas-client':

						$client = get_user_by( 'id', get_the_author_meta( 'ID' ) );

						if( !empty( $client) ) {
							$link = add_query_arg( array( 'post_type' => 'ticket', 'author' => $client->ID ), admin_url( 'edit.php' ) );

							echo "<a href='$link'>$client->display_name</a><br />$client->user_email";
						}
						else {
							// This shouldn't ever execute?
							echo '';
						}

						break;

					case 'wpas-activity':

						$tags = array();
						$replies = $this->get_replies_query( $post_id );

						/**
						 * We check when was the last reply (if there was a reply).
						 * Then, we compute the ticket age and if it is considered as
						 * old, we display an informational tag.
						 */
						if ( 0 === $replies->post_count ) {
							echo _x( 'No reply yet.', 'No last reply', 'awesome-support' );
						} else {

							$last_reply = $replies->posts[ $replies->post_count - 1 ];
							$last_user_link = add_query_arg( array( 'user_id' => $last_reply->post_author ), admin_url( 'user-edit.php' ) );
							$last_user = get_user_by( 'id', $last_reply->post_author );
							$role = true === user_can( $last_reply->post_author, 'edit_ticket' ) ? _x( 'agent', 'User role', 'awesome-support' ) : _x( 'client', 'User role', 'awesome-support' );

							echo _x( sprintf( _n( '%s reply.', '%s replies.', $replies->post_count, 'awesome-support' ), $replies->post_count ), 'Number of replies to a ticket', 'awesome-support' );
							echo '<br>';
							printf( _x( '<a href="%s">Last replied</a> %s ago by %s (%s).', 'Last reply ago', 'awesome-support' ), add_query_arg( array(
									'post'   => $post_id,
									'action' => 'edit',
								), admin_url( 'post.php' ) ) . '#wpas-post-' . $last_reply->ID, human_time_diff( strtotime( $last_reply->post_date ), current_time( 'timestamp' ) ), '<a href="' . $last_user_link . '">' . $last_user->user_nicename . '</a>', $role );
						}
						
						// Add open date
						if ( true === boolval( wpas_get_option( 'show_open_date_in_activity_column', false) ) ) {
							$open_date = wpas_get_open_date( $post_id ) ;
							if (! empty( $open_date ) ) {
								
								$open_date_string = (string) date_i18n( $open_date ) ;  // Convert date to string
								$open_date_string_tokens = explode(' ', $open_date_string ) ;	// Separate date/time
								
								if ( ! empty( $open_date_string_tokens ) ) {
									echo '<br>';
									echo __('Opened on: ', 'awesome-support') . $open_date_string_tokens[0] . ' at: ' . $open_date_string_tokens[1] ;
								}
							}
						}
						
						// Add open date gmt
						if ( true === boolval( wpas_get_option( 'show_open_date_gmt_in_activity_column', false) ) ) {						
							$open_date_gmt = wpas_get_open_date_gmt( $post_id ) ;
							if (! empty( $open_date_gmt ) ) {
								
								$open_date_string_gmt = (string) date_i18n( $open_date_gmt ) ;  // Convert date to string
								$open_date_string_tokens_gmt = explode(' ', $open_date_string_gmt ) ;	// Separate date/time
								
								if ( ! empty( $open_date_string_tokens_gmt ) ) {
									echo '<br>';
									echo __('Opened on GMT: ', 'awesome-support') . $open_date_string_tokens_gmt[0] . ' at: ' . $open_date_string_tokens_gmt[1] ;
								}
							}
						}
						
						// Maybe add close date
						$close_date = wpas_get_close_date( $post_id );
						if (! empty( $close_date ) ) {
							
							$close_date_string = (string) date_i18n( $close_date ) ;  // Convert date to string
							$close_date_string_tokens = explode(' ', $close_date_string ) ;	// Separate date/time
							
							if ( ! empty( $close_date_string_tokens ) ) {
								echo '<br>';
								echo __('Closed on: ', 'awesome-support') . $close_date_string_tokens[0] . ' at: ' . $close_date_string_tokens[1] ;
							}
						}
						
						// Maybe add gmt close date
						if ( true === boolval( wpas_get_option( 'show_clse_date_gmt_in_activity_column', false) ) ) {
							
							$close_date_gmt = wpas_get_close_date_gmt( $post_id );
							if (! empty( $close_date_gmt ) ) {
								
								$close_date_string_gmt = (string) date_i18n( $close_date_gmt ) ;  // Convert date to string
								$close_date_string_tokens_gmt = explode(' ', $close_date_string_gmt ) ;	// Separate date/time
								
								if ( ! empty( $close_date_string_tokens_gmt ) ) {
									echo '<br>';
									echo __('Closed on GMT: ', 'awesome-support') . $close_date_string_tokens_gmt[0] . ' at: ' . $close_date_string_tokens_gmt[1] ;
								}
							}
						}
						
						// Show the length of time a ticket was opened (applies to closed tickets only)...
						if ( true === boolval( wpas_get_option( 'show_length_of_time_ticket_was_opened', false) ) ) {
							
							$open_date_gmt = wpas_get_open_date_gmt( $post_id );
							$close_date_gmt = wpas_get_close_date_gmt( $post_id );
							if (! empty( $close_date_gmt ) && ! empty( $open_date_gmt ) ) {
								
								// Calculate difference object...
								$date1 = new DateTime( $open_date_gmt );
								$date2 = new DateTime( $close_date_gmt );
								$diff_dates = $date2->diff($date1) ;
								
								//echo '<br>';
								//echo __('Ticket was opened for: ', 'awesome-support') . human_time_diff( strtotime( $open_date_gmt ), strtotime( $close_date_gmt ) )   ;
								echo '<br>';
								echo __('Ticket was opened for: ', 'awesome-support');
								echo ' ' . $diff_dates->format('%d') .  __(' day(s)', 'awesome-support') ;
								echo ' ' . $diff_dates->format('%h') .  __(' hour(s)', 'awesome-support') ;								
								echo ' ' . $diff_dates->format('%i') .  __(' minute(s)', 'awesome-support') ;

								
							}
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

						if ( !empty( $tags ) ) {
							echo '<br>' . implode( ' ', $tags );
						}

						break;

					default:

						/* In case a custom callback is specified we use it */
						if ( function_exists( $fields[ $column ][ 'args' ][ 'column_callback' ] ) ) {
							call_user_func( $fields[ $column ][ 'args' ][ 'column_callback' ], $fields[ $column ][ 'name' ], $post_id );
						} /* Otherwise we use the default rendering options */
						else {
							wpas_cf_value( $fields[ $column ][ 'name' ], $post_id );
						}

				}
			}
		}
	}

	/**
	 * Make custom columns sortable
	 *
	 * @param  array $columns Already sortable columns
	 *
	 * @return array          New sortable columns
	 * @since  3.0.0
	 */
	public function custom_columns_sortable( $columns ) {

		$new = array();

		$fields = $this->get_custom_fields();

		foreach ( $fields as $field ) {

			/* If CF is a regular taxonomy we don't handle it, WordPress does */
			if ( 'taxonomy' == $field[ 'args' ][ 'field_type' ] && true === $field[ 'args' ][ 'taxo_std' ] ) {
				continue;
			}

			if ( true === $field[ 'args' ][ 'show_column' ] && true === $field[ 'args' ][ 'sortable_column' ] ) {
				$id = $field[ 'name' ];
				$new[ $id ] = $id;
			}

		}

		return apply_filters( 'wpas_custom_columns_sortable', array_merge( $columns, $new ) );

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

		if ( !isset( $_GET[ 'post_type' ] )	|| 'ticket' !== $_GET[ 'post_type' ]
			|| 'edit.php' !== $pagenow
			|| $query->query[ 'post_type' ] !== 'ticket'
			|| !$query->is_main_query()
		) {
			return;
		}

		$fields     = $this->get_custom_fields();
		$orderby    = isset($query->query[ 'orderby' ]) ? $query->query[ 'orderby' ] : '';

		if ( ! empty( $orderby ) && 'wpas-activity' !== $orderby && array_key_exists( $orderby, $fields ) ) {
			if ( 'taxonomy' != $fields[ $orderby ][ 'args' ][ 'field_type' ] ) {

				switch ($orderby) {

					case 'date':
					case 'status':
					//case 'assignee':
					case 'id':
					case 'wpas-client':
					case 'wpas-activity':

						break;

					default:

						/* Order by Custom Field (_wpas_* in postmeta */
						$query->set( 'meta_key', '_wpas_' . $orderby );
						$query->set( 'orderby', 'meta_value' );

						break;
				}

				$order      = isset( $_GET[ 'order' ] ) && ! empty( $_GET[ 'order' ] ) && strtoupper($_GET[ 'order' ]) === 'DESC' ? 'DESC' : 'ASC';

				$query->set( 'order', $order );
			}

		} else {

				/* Skip urgency ordering on trash page */

				if ( ! isset( $_GET[ 'post_status' ] )
					|| isset( $_GET[ 'post_status' ] ) && 'trash' !== $_GET[ 'post_status' ]
				) {

					if ( ( ! empty( $orderby ) && 'wpas-activity' === $orderby ) || wpas_has_smart_tickets_order() ) {
						/**
						 * Inspect the current context and if appropriate specify a query_var to allow
						 * WP_Query to modify itself based on arguments passed to WP_Query.
						 */
						$query->set( 'wpas_order_by_urgency', true );
					}
				}

			}

			return;

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
				//AND '_wpas_status'=wpas_postmeta.meta_key AND 'open'=CAST(wpas_postmeta.meta_value AS CHAR)
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
	INNER JOIN {$wpdb->postmeta} AS wpas_postmeta ON wpas_ticket.ID=wpas_postmeta.post_id
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

					if ( (bool) $reply_post->client_replied_last ) {
						$client_replies[ $reply_post->ticket_id ] = $no_replies[ $reply_post->ticket_id ];
					} else {
						$agent_replies[ $reply_post->ticket_id ] = $no_replies[ $reply_post->ticket_id ];
					}

					unset( $no_replies[ $reply_post->ticket_id ] );

				}

			}

			if( 'asc' !== filter_input(INPUT_GET, 'order') ) {
				$posts = array_values( $client_replies + $no_replies + array_reverse( $agent_replies, true ) );
			} else {
				$posts = array_values( $no_replies + $client_replies + array_reverse( $agent_replies, true ) );
			}

		}

		return $posts;

	}

	/***
     * Display filters
     *
	 * @param $post_type
     *
	 * @param $which
	 */
	public function custom_filters( $post_type, $which ) {

		if ( 'ticket' !== $post_type || 'top' !== $which ) {
			return;
		}

		/* STATE */

		$this_sort = isset( $_GET[ 'status' ] ) ? filter_input( INPUT_GET, 'status', FILTER_SANITIZE_STRING ) : 'open';
		$all_selected = ( 'any' === $this_sort ) ? 'selected="selected"' : '';
		$open_selected = ( !isset( $_GET[ 'status' ] ) && true === (bool)wpas_get_option( 'hide_closed' ) || 'open' === $this_sort ) ? 'selected="selected"' : '';
		$closed_selected = ( 'closed' === $this_sort ) ? 'selected="selected"' : '';

		$dropdown = '<select id="status" name="status">';
		$dropdown .= "<option value='any' $all_selected>" . __( 'All States', 'awesome-support' ) . "</option>";
		$dropdown .= "<option value='open' $open_selected>" . __( 'Open', 'awesome-support' ) . "</option>";
		$dropdown .= "<option value='closed' $closed_selected>" . __( 'Closed', 'awesome-support' ) . "</option>";
		$dropdown .= '</select>';

		echo $dropdown;


		/* STATUS */

		if ( !isset( $_GET[ 'post_status' ] )
			|| isset( $_GET[ 'post_status' ] ) && 'trash' !== $_GET[ 'post_status' ]
		) {
			$this_sort = isset( $_GET[ 'post_status' ] ) ? filter_input( INPUT_GET, 'post_status', FILTER_SANITIZE_STRING ) : 'any';
			$all_selected = ( 'any' === $this_sort ) ? 'selected="selected"' : '';

			$dropdown = '<select id="post_status" name="post_status" >'; 
			$dropdown .= "<option value='any' $all_selected>" . __( 'All Status', 'awesome-support' ) . "</option>";

			/**
			 * Get available statuses.
			 */
			$custom_statuses = wpas_get_post_status();

			foreach ( $custom_statuses as $_status_id => $_status_value ) {
				$custom_status_selected = ( isset( $_GET[ 'post_status' ] ) && $_status_id === $this_sort ) ? 'selected="selected"' : '';
				$dropdown .= "<option value='" . $_status_id . "' " . $custom_status_selected . " >" . __( $_status_value, 'awesome-support' ) . "</option>";
			}

			$dropdown .= '</select>';

			echo $dropdown;
		}


		$fields = $this->get_custom_fields();


		/* AGENT */

		if ( $fields[ 'assignee' ][ 'args' ][ 'filterable' ] ) {

			$selected = __( 'All Agents', 'awesome-support' );
			$selected_value = '';

			if ( isset( $_GET[ 'assignee' ] ) && !empty( $_GET[ 'assignee' ] ) ) {
				$staff_id = (int)$_GET[ 'assignee' ];
				$agent = new WPAS_Member_Agent( $staff_id );

				if ( $agent->is_agent() ) {
					$user = get_user_by( 'ID', $staff_id );
					$selected = $user->display_name;
					$selected_value = $staff_id;
				}
			}

			$staff_atts = array(
				'name'      => 'assignee',
				'id'        => 'assignee',
				'disabled'  => !current_user_can( 'assign_ticket' ) ? true : false,
				'select2'   => true,
				'data_attr' => array( 'capability' => 'edit_ticket',
				                      'allowClear' => true,
				                      'placeholder' => $selected,
					),
			);

			if ( isset( $staff_id ) ) {
				$staff_atts['selected'] = $staff_id;
			}

			echo wpas_dropdown( $staff_atts, "<option value='" . $selected_value . "'>" . $selected . "</option>" );

		}


		/* CLIENT */

		$selected = __( 'All Clients', 'awesome-support' );
		$selected_value = '';

		if ( isset( $_GET[ 'author' ] ) && !empty( $_GET[ 'author' ] ) ) {
			$client_id = (int)$_GET[ 'author' ];
			$user = get_user_by( 'ID', $client_id );
			$selected = $user->display_name;
			$selected_value = $client_id;
		}

		$client_atts = array(
			'name'      => 'author',
			'id'        => 'author',
			'disabled'  => !current_user_can( 'assign_ticket' ) ? true : false,
			'select2'   => true,
			'data_attr' => array( 'capability' => 'view_ticket',
			                      'allowClear' => true,
				                  'placeholder' => $selected,
				),
		);

		if ( isset( $client_id ) ) {
			$client_atts['selected'] = $client_id;
		}

		echo wpas_dropdown( $client_atts, "<option value='" . $selected_value . "'>" . $selected . "</option>" );


		/* TICKET ID */
		$selected_value = '';
		if ( isset( $_GET[ 'id' ] ) && !empty( $_GET[ 'id' ] ) ) {
			$selected_value = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
		}

		echo '<input type="text" placeholder="Ticket ID" name="id" id="id" value="' . $selected_value . '" />';

		echo '<div style="clear:both;"></div>';

		/* RESET FILTERS */

		echo '<span class="alignright" style="line-height: 28px; margin: 0 25px;">';
		echo $this->reset_link();
		echo '</span>';

	}

	/**
	 * Add filters for custom taxonomies
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public function custom_taxonomy_filter() {

		global $typenow;

		if ( 'ticket' != $typenow ) {
			echo '';
		}

		$post_types = get_post_types( array( '_builtin' => false ) );

		if ( in_array( $typenow, $post_types ) ) {

			$filters = get_object_taxonomies( $typenow );

			/* Get all custom fields */
			$fields = $this->get_custom_fields();

			foreach ( $filters as $tax_slug ) {

				if ( !array_key_exists( $tax_slug, $fields ) ) {
					continue;
				}

				if ( true !== $fields[ $tax_slug ][ 'args' ][ 'filterable' ] ) {
					continue;
				}

				$tax_obj = get_taxonomy( $tax_slug );

				$args = array(
					'show_option_all' => __( 'All ' . $tax_obj->label ),
					'taxonomy'        => $tax_slug,
					'name'            => $tax_obj->name,
					'orderby'         => 'name',
					'hierarchical'    => $tax_obj->hierarchical,
					'show_count'      => true,
					'hide_empty'      => true,
					'hide_if_empty'   => true,
				);

				if ( isset( $_GET[ $tax_slug ] ) ) {
					$args[ 'selected' ] = filter_input( INPUT_GET, $tax_slug, FILTER_SANITIZE_STRING );
				}

				wp_dropdown_categories( $args );

			}
		}

	}

	/**
	 * Convert taxonomy term ID into term slug.
	 *
	 * When filtering, WordPress uses the term ID by default in the query but
	 * that doesn't work. We need to convert it to the taxonomy term slug.
	 *
	 * @param  object $query WordPress current main query
	 *
	 * @return void
	 *
	 * @since  2.0.0
	 * @link   http://wordpress.stackexchange.com/questions/578/adding-a-taxonomy-filter-to-admin-list-for-a-custom-post-type
	 */
	public function custom_taxonomy_filter_convert_id_term( $query ) {

		global $pagenow;

		/* Check if we are in the correct post type */
		if ( is_admin()
			&& 'edit.php' == $pagenow
			&& isset( $_GET[ 'post_type' ] )
			&& 'ticket' === $_GET[ 'post_type' ]
			&& $query->is_main_query()
		) {

			/* Get all custom fields */
			$fields = $this->get_custom_fields();

			/* Filter custom fields that are taxonomies */
			foreach ( $query->query_vars as $arg => $value ) {

				if ( array_key_exists( $arg, $fields ) && 'taxonomy' === $fields[ $arg ][ 'args' ][ 'field_type' ] && true === $fields[ $arg ][ 'args' ][ 'filterable' ] ) {

					$term = get_term_by( 'id', $value, $arg );

					// Depending on where the filter was triggered (dropdown or click on a term) it uses either the term ID or slug. Let's see if this term slug exists
					if ( is_null( $term ) ) {
						$term = get_term_by( 'slug', $value, $arg );
					}

					if ( !empty( $term ) ) {
						$query->query_vars[ $arg ] = $term->slug;
					}

				}

			}

		}
	}

	/**
	 * Set meta_query cor custom columns
	 *
	 * @param $wp_query
	 *
	 * @since  3.3.4
	 */
	public function custom_meta_query( $wp_query ) {

		global $pagenow;

		/* Check if we are in the correct post type */
		if ( ! is_admin()
			|| 'edit.php' !== $pagenow
			|| ! isset( $_GET[ 'post_type' ] )
			|| 'ticket' !== $_GET[ 'post_type' ]
			|| ! $wp_query->is_main_query()
		) {
			return;
		}

		$meta_query = $wp_query->get( 'meta_query' );

		if ( !is_array( $meta_query ) ) {
			$meta_query = (array)$meta_query;
		}

		if ( isset( $_GET[ 'assignee' ] ) && !empty( $_GET[ 'assignee' ] ) ) {

			$staff_id = (int)$_GET[ 'assignee' ];
			$agent = new WPAS_Member_Agent( $staff_id );

			if ( $agent->is_agent() ) {

				$meta_query[] = array(
					'key'     => '_wpas_assignee',
					'value'   => $staff_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				);
				$wp_query->set('meta_key', '_wpas_assignee');
			}

			if ( !isset( $meta_query[ 'relation' ] ) ) {
				$meta_query[ 'relation' ] = 'AND';
			}

		}


		$wpas_status = isset( $_GET[ 'status' ] ) && !empty( $_GET[ 'status' ] ) ? $_GET[ 'status' ] : 'open';

		if ( 'any' === $wpas_status ) {

			$meta_query[] = array(
				'relation'      => 'OR',
				array(
					'key'     => '_wpas_status',
					'value'   => 'open',
					'compare' => '=',
					'type'    => 'CHAR',
				),
				array(
					'key'     => '_wpas_status',
					'value'   => 'closed',
					'compare' => '=',
					'type'    => 'CHAR',
				),
			);
		}

		if( 'open' === $wpas_status ) {

			$meta_query[] = array(
				'key'     => '_wpas_status',
				'value'   => 'open',
				'compare' => '=',
				'type'    => 'CHAR',
			);

		}

		if ( 'closed' === $wpas_status ) {

			$meta_query[] = array(
				'key'     => '_wpas_status',
				'value'   => 'closed',
				'compare' => '=',
				'type'    => 'CHAR',
			);
		}

		if( isset($meta_query)) {
			if ( !isset( $meta_query[ 'relation' ] ) ) {
				$meta_query[ 'relation' ] = 'AND';
			}
			$wp_query->set( 'meta_query', $meta_query );
		}

	}

	/**
	 * Save query vars
	 */
	public function parse_request() {

		global $wp;

		$fields = $this->get_custom_fields();

		// Map query vars to their keys, or get them if endpoints are not supported
		foreach ( $fields as $key => $var ) {
			if ( isset( $_GET[ $var[ 'name' ] ]) ) {
				$wp->query_vars[ $key ] = $_GET[ $var[ 'name' ] ];
			} elseif ( isset( $wp->query_vars[ $var[ 'name' ] ] ) ) {
				$wp->query_vars[ $key ] = $wp->query_vars[ $var ];
			}
		}

	}

	/**
	 * Single ticket where
	 *
	 * @param $where
	 * @param $wp_query
	 *
	 * @return string
	 *
	 * @since  3.3.4
	 */
	public function posts_where( $where, $wp_query ) {

		if ( is_admin() && is_main_query()
		    && ! is_null( filter_input( INPUT_GET, 'id' ) )
			&& 'ticket' === $wp_query->query[ 'post_type' ]
		) {

			global $wpdb;

			$ticket_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );

			/* Filter by Ticket ID */
			if ( ! empty( $ticket_id ) && intval( $ticket_id ) != 0 ) {
				$where .= " AND {$wpdb->posts}.ID = " . intval( $ticket_id );
			}
		}

		return $where;

	}

	/**
	 * Set query requirements for column sorting
	 *
	 * @param $clauses
	 * @param $wp_query
	 *
	 * @return mixed
	 *
	 * @since  3.3.4
	 */
	public function post_clauses_orderby( $clauses, $wp_query ) {

		if ( !isset( $wp_query->query[ 'post_type' ] )
			|| $wp_query->query[ 'post_type' ] !== 'ticket'
			|| ! $wp_query->query_vars_changed
		) {
			return $clauses;
		}

		$fields = $this->get_custom_fields();

		$orderby = isset($_GET['orderby']) ? $_GET['orderby'] : '';

		if ( !empty( $orderby ) && 'wpas-activity' !== $orderby && array_key_exists( $orderby, $fields ) ) {

			global $wpdb;

			$order = ( 'ASC' == strtoupper( $wp_query->get( 'order' ) ) ) ? 'ASC' : 'DESC';

			if ( 'taxonomy' == $fields[ $orderby ][ 'args' ][ 'field_type' ] && !$fields[ $orderby ][ 'args' ][ 'taxo_std' ] ) {

				/*
				 *  Alias taxonomy tables used by sorting in
				 *  case there is an active taxonomy filter. (is_tax())
				 */
				$clauses[ 'join' ] .= <<<SQL
LEFT OUTER JOIN {$wpdb->term_relationships} AS t_rel ON {$wpdb->posts}.ID=t_rel.object_id
LEFT OUTER JOIN {$wpdb->term_taxonomy} AS t_t ON t_t.term_taxonomy_id=t_rel.term_taxonomy_id
LEFT OUTER JOIN {$wpdb->terms} AS tms ON tms.term_id=t_t.term_id
SQL;

				$clauses[ 'where' ] .= " AND (t_t.taxonomy = '" . $orderby . "' AND t_t.taxonomy IS NOT NULL)";
				$clauses[ 'groupby' ] = "t_rel.object_id";
				$clauses[ 'orderby' ] = "GROUP_CONCAT(tms.name ORDER BY tms.name ASC) " . $order;

			} elseif ( 'id' === $orderby ) {

			} elseif ( 'status' === $orderby ) {

				$clauses[ 'orderby' ] = "{$wpdb->posts}.post_status " . $order;

			} elseif ( 'assignee' === $orderby ) {

				// Join user table onto the postmeta table
				$clauses[ 'join' ] .= " LEFT JOIN {$wpdb->users} ag ON ( {$wpdb->prefix}postmeta.meta_key='_wpas_assignee' AND CAST({$wpdb->prefix}postmeta.meta_value AS UNSIGNED)=ag.ID)";
				$clauses[ 'orderby' ] = "ag.display_name " . $order;

			} elseif ( 'wpas-client' === $orderby ) {

				// Join user table onto the postmeta table
				$clauses[ 'join' ] .= " LEFT JOIN {$wpdb->users} ON {$wpdb->prefix}posts.post_author={$wpdb->users}.ID";
				$clauses[ 'orderby' ] = " {$wpdb->users}.display_name " . $order;

			} else {

				// Exclude empty values in custom fields
				$clauses[ 'where' ] .= " AND TRIM(IFNULL({$wpdb->postmeta}.meta_value,''))<>'' ";

			}

		}

		return $clauses;
	}


	/**
	 * Reorder the admin columns.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $columns List of admin columns
	 *
	 * @return array          Re-ordered list
	 */
	public function move_status_first( $columns ) {

		// Don't change columns order on mobiles as it breaks the layout. WordPress expects the title column to be the second one.
		// @link https://github.com/Awesome-Support/Awesome-Support/issues/306
		if ( wp_is_mobile() ) {
			return $columns;
		}

		if ( isset( $columns[ 'status' ] ) ) {
			$status_content = $columns[ 'status' ];
			unset( $columns[ 'status' ] );
		} else {
			return $columns;
		}

		$new = array();

		foreach ( $columns as $column => $content ) {

			if ( 'title' === $column ) {
				$new[ 'status' ] = $status_content;
			}

			$new[ $column ] = $content;

		}

		return $new;

	}


	/**
     * Display Reset Filters
     *
     * @since   3.3.4
     *
	 * @return string               Return link
	 */
	public function reset_link() {

		$link = add_query_arg( array( 'post_type' => 'ticket' ), admin_url( 'edit.php' ) );

		return "<a href='$link'>Reset Filters</a>";

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

		if ( !is_admin() || ! isset( $_GET['post_type'] ) || 'ticket' !== $_GET['post_type'] ) {
			return $content;
		}

		global $mode;

		if ( 'excerpt' === $mode ) {
			return '';
		}

		return $content;
	}

	/**
	 * Display notice
	 *
	 * @param $which
	 *
	 */
	public function manage_posts_extra_tablenav( $which ) {

		if ( wp_is_mobile()
			|| !isset( $_GET[ 'post_type' ] )
			|| 'ticket' !== $_GET[ 'post_type' ]
		) {
			return;
		}

		if ( 'bottom' === $which ) {

			echo '<div class="alignright" style="clear: both; overflow: hidden; margin: 20px 10px;"><p>'
				. __( 'NOTE: Please be aware that when you sort on a column, tickets that have never had a value entered into that column will not appear on your sorted list (null fields). This can reduce the number of tickets in your sorted list.  This reduced number of tickets is NOT a bug - it is a deliberate design decision. You should also be aware that deliberately entering a blank into a ticket field is considered data so those tickets will show up in the sorted list.', 'awesome-support' )
				. ' - '
				. $this->reset_link()
				. '</p></div>';
		}

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

			if ( 'closed' === wpas_get_ticket_status( $post_id ) ) {
				$classes[] = 'wpas-ticket-list-row-closed';
			}

		return $classes;

	}

}