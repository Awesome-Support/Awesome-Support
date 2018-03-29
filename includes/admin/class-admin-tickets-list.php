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

		if ( is_admin() ) {

			/**
			 * Add custom columns
			 */
			add_action( 'manage_ticket_posts_columns', array( $this, 'add_custom_columns' ), 10, 1 );
			add_action( 'manage_ticket_posts_columns', array( $this, 'move_status_first' ), 15, 1 );
			add_action( 'manage_ticket_posts_custom_column', array( $this, 'custom_columns_content' ), 10, 2 );
			add_filter( 'manage_edit-ticket_sortable_columns', array( $this, 'custom_columns_sortable' ), 10, 1 );

			/**
			 * Add tabs in ticket listing page
			 */
			add_action( 'restrict_manage_posts', array( $this, 'tablenav_tabs' ), 8, 2 );
			add_filter( 'parse_query', array( $this, 'custom_taxonomy_filter_convert_id_term' ), 10, 1 );
			add_filter( 'parse_query', array( $this, 'custom_meta_query' ), 11, 1 );
			add_filter( 'posts_clauses', array( $this, 'post_clauses_orderby' ), 5, 2 );
			add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
			add_action( 'parse_request', array( $this, 'parse_request' ), 10, 1 );
			add_action( 'pre_get_posts', array( $this, 'set_filtering_query_var' ), 1, 1 );
			add_action( 'pre_get_posts', array( $this, 'set_ordering_query_var' ), 100, 1 );
			add_filter( 'posts_results', array( $this, 'apply_ordering_criteria' ), 10, 2 );
			add_filter( 'posts_results', array( $this, 'filter_the_posts' ), 10, 2 );

			add_filter( 'wpas_add_custom_fields', array( $this, 'add_custom_fields' ) );

			add_action( 'admin_menu', array( $this, 'hide_closed_tickets' ), 10, 0 );
			add_filter( 'the_excerpt', array( $this, 'remove_excerpt' ), 10, 1 );
			add_filter( 'post_row_actions', array( $this, 'remove_quick_edit' ), 10, 2 );
			add_filter( 'post_class', array( $this, 'ticket_row_class' ), 10, 3 );
			add_filter( 'manage_posts_extra_tablenav', array( $this, 'manage_posts_extra_tablenav' ), 10, 1 );

		}
	}

	/**
	 * Clear all filters if filtering by Ticket ID
	 *
	 * @param $query
	 */
	public function set_filtering_query_var( $query ) {

		global $post_type;

	    if ( 'ticket' !== $post_type
	        || ! $query->is_main_query()
	        || empty ($_GET[ 'id' ])
	    ) {
	        return;
	    }

    	$fields = $this->get_custom_fields();

    	foreach( $fields as $key => $value ) {
			if ( 'id' !== $key && $value[ 'args' ][ 'filterable' ] ) {
				$query->query[ $key ] = '';
				$query->set( $key, '');
			}
	    }

		$query->query[ 'post_status' ] = '';
		$query->set( 'post_status', '');

		$query->query[ 'filter-by-date' ] = '';
		$query->set( 'filter-by-date', '');

	}

	public function filter_the_posts( $posts, $query ) {

		global $typenow;

		if ( ! $query->get( 'wpas_activity' ) ) {
			return $posts;
		}

		$p = array_reverse($posts, true);
		foreach ( array_reverse($posts, true) as $key => $post ) {

			$replies = $this->get_replies_query( $post->ID );

			if( empty($replies->posts) ) {
				unset( $p[ $key ] );
			}

			// Maybe add the "Awaiting Support Response" tag
			if ( isset( $_GET[ 'activity' ] ) && 'awaiting_support_reply' === $_GET[ 'activity' ]
				&& user_can( (int) $post->post_author, 'edit_ticket' )
			) {
				unset( $p[ $key ] );
			}

			// Maybe add the "Old" tag
			if ( isset( $_GET[ 'activity' ] ) &&  'old' === $_GET[ 'activity' ]
			     && false === wpas_is_ticket_old( $post->ID, wpas_get_replies($post->ID) ) ) {
				unset( $p[ $key ] );
			}

		}
		$posts = array_reverse($p);

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
	 * Add custom fields
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function add_custom_fields( $fields ) {

		global $pagenow, $typenow;

		$add_custom_fields = ( 'edit.php' !== $pagenow && 'ticket' !== $typenow ) ? false : true;
		if ( ! apply_filters( 'add_ticket_column_custom_fields', $add_custom_fields ) ) {
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

		$new    = array();
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
				$id            = $field[ 'name' ];
				$title         = apply_filters( 'wpas_custom_column_title', wpas_get_field_title( $field ), $field );
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
				$new[ 'wpas-client' ] = $this->get_cf_title( 'wpas-client', 'Created by' );

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

		if ( ! empty( $field ) ) {
			$field_title = apply_filters( 'wpas_custom_column_title', wpas_get_field_title( $field ), $field );
		}

		return esc_html__( $field_title, 'awesome-support' );

	}


	/**
	 * Get screen option for current user else return default.
	 *
	 * @param $option
	 *
	 * @return mixed|string
	 */
	public function get_user_meta_current_val( $option, $default = null ) {

		$user_id        = get_current_user_id();
		$current_val = esc_attr( get_user_option( $option, $user_id ) );

		if ( empty( $current_val ) ) {
			return $default;
		}

		return $current_val;
	}

	/**
	 * @return
	 */
	public function edit_link_target() {

		$current_val = $this->get_user_meta_current_val( 'edit_ticket_in_new_window' );

		return ( 'yes' !== $current_val ? '_self' : '_blank' );

	}

	/**
	 * Manage core column content.
	 *
	 * @since  3.0.0
	 *
	 * @param  array   $column  Column currently processed
	 *
	 * @param  integer $post_id ID of the post being processed
	 */
	public function custom_columns_content( $column, $post_id ) {

		$fields = $this->get_custom_fields();

		if ( isset( $fields[ $column ] ) ) {

			if ( true === $fields[ $column ][ 'args' ][ 'show_column' ] ) {

				switch ( $column ) {

					case 'id':

						$link = add_query_arg( array(
							                       'post'   => $post_id,
							                       'action' => 'edit',
						                       ), admin_url( 'post.php' ) );
						echo "<strong><a href='$link' target='" . $this->edit_link_target() . "'>{$post_id}</a></strong>";

						break;

					case 'wpas-client':
					
						$the_post = get_post( $post_id ) ;
						$author_id = 0 ;
						if ( ! is_wp_error( $the_post ) && ! empty( $the_post ) ) {
							$author_id = $the_post->post_author ;
						}

						$client = get_user_by( 'id', $author_id );

						if ( ! empty( $client ) ) {
							$link = add_query_arg( array(
								                       'post_type' => 'ticket',
								                       'author'    => $client->ID,
							                       ), admin_url( 'edit.php' ) );

							echo "<a href='$link'>$client->display_name</a><br />$client->user_email";
						} else {
							// This shouldn't ever execute?
							echo '';
						}

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
							printf( _x( '<a href="%s" target="' . $this->edit_link_target() . '">Last replied</a> %s ago by %s (%s).', 'Last reply ago', 'awesome-support' ), add_query_arg( array(
								                                                                                                                                                                 'post'   => $post_id,
								                                                                                                                                                                 'action' => 'edit',
							                                                                                                                                                                 ), admin_url( 'post.php' ) ) . '#wpas-post-' . $last_reply->ID, human_time_diff( strtotime( $last_reply->post_date ), current_time( 'timestamp' ) ), '<a href="' . $last_user_link . '">' . $last_user->user_nicename . '</a>', $role );
						}

						// Add open date
						if ( true === boolval( wpas_get_option( 'show_open_date_in_activity_column', false ) ) ) {
							$open_date = wpas_get_open_date( $post_id );
							if ( ! empty( $open_date ) ) {

								$open_date_string        = (string) date_i18n( $open_date );  // Convert date to string
								$open_date_string_tokens = explode( ' ', $open_date_string );    // Separate date/time

								if ( ! empty( $open_date_string_tokens ) ) {
									echo '<br>';
									echo __( 'Opened on: ', 'awesome-support' ) . $open_date_string_tokens[ 0 ] . ' at: ' . $open_date_string_tokens[ 1 ];
								}
							}
						}

						// Add open date gmt
						if ( true === boolval( wpas_get_option( 'show_open_date_gmt_in_activity_column', false ) ) ) {
							$open_date_gmt = wpas_get_open_date_gmt( $post_id );
							if ( ! empty( $open_date_gmt ) ) {

								$open_date_string_gmt        = (string) date_i18n( $open_date_gmt );  // Convert date to string
								$open_date_string_tokens_gmt = explode( ' ', $open_date_string_gmt );    // Separate date/time

								if ( ! empty( $open_date_string_tokens_gmt ) ) {
									echo '<br>';
									echo __( 'Opened on GMT: ', 'awesome-support' ) . $open_date_string_tokens_gmt[ 0 ] . ' at: ' . $open_date_string_tokens_gmt[ 1 ];
								}
							}
						}

						// Maybe add close date
						$close_date = wpas_get_close_date( $post_id );
						if ( ! empty( $close_date ) ) {

							$close_date_string        = (string) date_i18n( $close_date );  // Convert date to string
							$close_date_string_tokens = explode( ' ', $close_date_string );    // Separate date/time

							if ( ! empty( $close_date_string_tokens ) ) {
								echo '<br>';
								echo __( 'Closed on: ', 'awesome-support' ) . $close_date_string_tokens[ 0 ] . ' at: ' . $close_date_string_tokens[ 1 ];
							}
						}

						// Maybe add gmt close date
						if ( true === boolval( wpas_get_option( 'show_clse_date_gmt_in_activity_column', false ) ) ) {

							$close_date_gmt = wpas_get_close_date_gmt( $post_id );
							if ( ! empty( $close_date_gmt ) ) {

								$close_date_string_gmt        = (string) date_i18n( $close_date_gmt );  // Convert date to string
								$close_date_string_tokens_gmt = explode( ' ', $close_date_string_gmt );    // Separate date/time

								if ( ! empty( $close_date_string_tokens_gmt ) ) {
									echo '<br>';
									echo __( 'Closed on GMT: ', 'awesome-support' ) . $close_date_string_tokens_gmt[ 0 ] . ' at: ' . $close_date_string_tokens_gmt[ 1 ];
								}
							}
						}

						// Show the length of time a ticket was opened (applies to closed tickets only)...
						if ( true === boolval( wpas_get_option( 'show_length_of_time_ticket_was_opened', false ) ) ) {

							$open_date_gmt  = wpas_get_open_date_gmt( $post_id );
							$close_date_gmt = wpas_get_close_date_gmt( $post_id );
							if ( ! empty( $close_date_gmt ) && ! empty( $open_date_gmt ) ) {

								// Calculate difference object...
								$date1      = new DateTime( $open_date_gmt );
								$date2      = new DateTime( $close_date_gmt );
								$diff_dates = $date2->diff( $date1 );

								//echo '<br>';
								//echo __('Ticket was opened for: ', 'awesome-support') . human_time_diff( strtotime( $open_date_gmt ), strtotime( $close_date_gmt ) )   ;
								echo '<br>';
								echo __( 'Ticket was opened for: ', 'awesome-support' );
								echo ' ' . $diff_dates->format( '%d' ) . __( ' day(s)', 'awesome-support' );
								echo ' ' . $diff_dates->format( '%h' ) . __( ' hour(s)', 'awesome-support' );
								echo ' ' . $diff_dates->format( '%i' ) . __( ' minute(s)', 'awesome-support' );


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
						
						$tags = apply_filters( 'wpas_ticket_listing_activity_tags', $tags, $post_id );

						if ( ! empty( $tags ) ) {
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
				$id         = $field[ 'name' ];
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

		if ( ! isset( $_GET[ 'post_type' ] ) || 'ticket' !== $_GET[ 'post_type' ]
		     || 'edit.php' !== $pagenow
		     || $query->query[ 'post_type' ] !== 'ticket'
		     || ! $query->is_main_query()
		) {
			return;
		}

		$fields  = $this->get_custom_fields();
		$orderby = isset( $query->query[ 'orderby' ] ) ? $query->query[ 'orderby' ] : '';

		if ( ! empty( $orderby ) && array_key_exists( $orderby, $fields ) ) {
			if ( 'taxonomy' != $fields[ $orderby ][ 'args' ][ 'field_type' ] ) {

				switch ( $orderby ) {

					case 'date':
					case 'status':
					case 'id':
					case 'wpas-client':

						break;

					case 'wpas-activity':

						$orderby = 'last_reply_date';
						$query->set( 'wpas_activity', true );

					default:

						/* Order by Custom Field (_wpas_* in postmeta */
						$query->set( 'meta_key', '_wpas_' . $orderby );
						$query->set( 'orderby', 'meta_value' );

						break;
				}

				$order = isset( $_GET[ 'order' ] ) && ! empty( $_GET[ 'order' ] ) && strtoupper( $_GET[ 'order' ] ) === 'DESC' ? 'DESC' : 'ASC';

				$query->set( 'order', $order );
			}

		} else {

			/* Skip urgency ordering on trash page */

			if ( ! isset( $_GET[ 'post_status' ] )
			     || isset( $_GET[ 'post_status' ] ) && 'trash' !== $_GET[ 'post_status' ]
			) {

				if ( wpas_has_smart_tickets_order() ) {
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

		if ( $query->get( 'wpas_order_by_urgency' ) ) {

			global $wpdb;

			$sql = <<<SQL
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
			$replies = $wpdb->get_results( $sql );

			foreach ( $posts as $post ) {

				$no_replies[ $post->ID ] = $post;

			}

			/**
			 * The post order will be modified using the following logic:
			 *
			 *        Order    -    Ticket State
			 *        -----    -------------------------------------------
			 *         1st    -    No reply - older since request made
			 *         2nd    -    No reply - newer since request made
			 *         3rd    -    Reply - older response since client replied
			 *         4th    -    Reply - newer response since client replied
			 *         5th    -    Reply - newer response since agent replied
			 *         6th    -    Reply - older response since agent replied
			 */

			foreach ( $replies as $reply_post ) {

				if ( isset( $no_replies[ $reply_post->ticket_id ] ) ) {

					if ( (bool) $reply_post->client_replied_last ) {
						$client_replies[ $reply_post->ticket_id ] = $no_replies[ $reply_post->ticket_id ];
					} else {
						$agent_replies[ $reply_post->ticket_id ] = $no_replies[ $reply_post->ticket_id ];
					}

					unset( $no_replies[ $reply_post->ticket_id ] );

				}

			}

			// Smart sort
			$posts = array_values( $client_replies + $no_replies + array_reverse( $agent_replies, true ) );

		}

		return $posts;

	}


	/**
	 * Turn tablenav area into tabs for ticket listing page
	 *
	 * @param string $post_type
	 * @param string $which
	 *
	 */
	public function tablenav_tabs( $post_type, $which ) {

		if ( 'ticket' !== $post_type || 'top' !== $which ) {
			return;
		}

		// Register tabs
		add_filter( 'wpas_admin_tabs_tickets_tablenav', array( $this, 'register_tabs' ) );
		echo wpas_admin_tabs( 'tickets_tablenav' );
	}

	/**
	 * Register tabs for tickets tablenav
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function register_tabs( $tabs ) {
		
		// Check options to see which tabs to show...
		$show_doc_tab = boolval( wpas_get_option( 'ticket_list_show_doc_tab', true) );
		$show_bulk_actions_tab = boolval( wpas_get_option( 'ticket_list_show_bulk_actions_tab', true) );
		$show_preferences_tab = boolval( wpas_get_option( 'ticket_list_show_preferences_tab', true) ) ;

		// Add tabs to tab array based on options set
		$tabs[ 'filter' ]        = __( 'Filter', 'awesome-support' );
		$tabs[ 'search' ]        = __( 'Search', 'awesome-support' );
		
		if ( true === $show_bulk_actions_tab ) {
			$tabs[ 'bulk_actions' ]  = __( 'Bulk Actions', 'awesome-support' );
		}
		
		if ( true === $show_preferences_tab ) {
			$tabs[ 'preferences' ]   = __( 'Preferences', 'awesome-support' );
		}
		
		if ( true === $show_doc_tab ) {		
			$tabs[ 'documentation' ] = __( 'Documentation', 'awesome-support' );
		}

		// Set content fo tabs based on which tabs are set to be active...
		add_filter( 'wpas_admin_tabs_tickets_tablenav_filter_content', array( $this, 'filter_tab_content' ) );
		add_filter( 'wpas_admin_tabs_tickets_tablenav_search_content', array( $this, 'search_tab_content' ) );
		
		if ( true === $show_bulk_actions_tab ) {		
			add_filter( 'wpas_admin_tabs_tickets_tablenav_bulk_actions_content', array(
				$this,
				'bulk_actions_tab_content',
			) );
		}
		
		if ( true === $show_preferences_tab ) {		
			add_filter( 'wpas_admin_tabs_tickets_tablenav_preferences_content', array( $this, 'preferences_tab_content' ) );
		}
		
		if ( true === $show_doc_tab ) {				
			add_filter( 'wpas_admin_tabs_tickets_tablenav_documentation_content', array(
				$this,
				'filter_documentation_content',
			) );
		}

			
		return $tabs;
	}

	/**
	 * Add content to filter tab
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function filter_tab_content( $content ) {

		ob_start();

		echo '<div class="filter_by_date_container"></div>';

		// Add custom field filters
		$this->custom_filters();

		// Add texonomy filters
		$this->custom_taxonomy_filter();

		// Emply container to place filter button via jQuery
		echo '<div class="filter_btn_container"></div>';

		/* RESET FILTERS */

		echo '<span style="line-height: 28px; margin: 0 25px;">';
		echo $this->reset_link();
		echo '</span>';

		echo '<div class="clear clearfix"></div>';

		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Add content to search tab
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function search_tab_content( $content ) {

		return '<div id="search_tab_content_placeholder"></div>';

	}

	/**
	 * Add content to documentation tab
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function preferences_tab_content( $content ) {

		ob_start();

		// Save preference to user meta if Save button clicked
		if ( isset( $_GET[ 'save_preferences' ] ) ) {
			$user = get_current_user_id();
			if ( 'yes' === $_GET[ 'edit_ticket_in_new_window' ] ) {
				update_user_option( $user, 'edit_ticket_in_new_window', 'yes' );
			} else {
				update_user_option( $user, 'edit_ticket_in_new_window', 'no' );
			}
		}

		$current_val = $this->get_user_meta_current_val('edit_ticket_in_new_window', 'no');
		$selected    = isset( $current_val ) && $current_val === 'yes' ? 'checked' : '';

		echo "<table style='max-width: 640px; min-width: 300px;'>";
		echo "<tr><td colspan='2'><h2>Preferences</h2><br/></td></tr>";

		echo "<tr><td width='100' align='right'>";
		echo "<input type='checkbox' name='edit_ticket_in_new_window' id='edit_ticket_in_new_window' value='yes' " . $selected . " />";
		echo "</td><td><label for='edit_ticket_in_new_window'>" . __('Edit ticket in new Window when the ticket ID is clicked', 'awesome-support') . "</label></td></tr>";

		echo "<tr><td></td><td><br/><input type='submit' name='save_preferences' class='button' value='Save Preferences' /></td></tr>";
		echo "</table>";

		$content = ob_get_clean();

		return $content;

	}

	/**
	 * Add content to documentation tab
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function filter_documentation_content( $content ) {

		ob_start();

		echo '<h2>' . __( 'Awesome Support Core Documentation', 'awesome-support' ) . '</h2>' . '<br />';
		echo '<a href = "https://getawesomesupport.com/documentation/awesome-support/post-installation-need-know-quick-start/">' . __( '1. User Guide', 'awesome-support' ) . '</a>' . '<br />';
		echo __( 'The end user guide covers topics such as instructions for installation, entering tickets, adding agents, navigation, replying to and closing tickets and more.', 'awesome-support' ) . '<br /><br />';

		echo '<a href = "https://getawesomesupport.com/documentation/awesome-support/admin-overview/">' . __( '2. Administration Guide', 'awesome-support' ) . '</a>' . '<br />';
		echo __( 'The admin guide covers topics such as configuring products, departments, priorities and channels. It also includes guides for security using roles and capabilities along with time tracking, email alerts and known incompatibilities.', 'awesome-support' ) . '<br /><br />';

		echo '<a href = "https://getawesomesupport.com/documentation/awesome-support/how-to-fix-you-do-not-have-the-capacity-to-open-a-new-ticket/">' . __( '3. Troubleshooting', 'awesome-support' ) . '</a>' . '<br />';
		echo __( 'Having an issue? Your answer might be in here.', 'awesome-support' ) . '<br /><br />';

		echo '<a href = "https://getawesomesupport.com/faq/">' . __( '4. FAQ and More Troubleshooting Tips', 'awesome-support' ) . '</a>' . '<br />';
		echo __( 'Even more trouble-shooting tips and other frequently asked questions. 404 pages, missing tabs, PHP errors and conflicts are just some of the topics covered here!', 'awesome-support' ) . '<br /><br />';

		echo '<a href = "https://getawesomesupport.com/documentation/awesome-support/custom-fields/">' . __( '5. Customization', 'awesome-support' ) . '</a>' . '<br />';
		echo __( 'Need to change the look of your ticket pages?  Maybe add some custom fields? Then this is the guide you need!', 'awesome-support' ) . '<br /><br />';
		
		echo '<h2>' . __( 'Awesome Support Add-ons and Extensions Documentation', 'awesome-support' ) . '</h2>' . '<br />';
		echo '<a href = "https://getawesomesupport.com/documentation-new/">' . __( '1. All Extensions', 'awesome-support' ) . '</a>' . '<br />';
		echo __( 'Links to documentation for all extensions and add-ons.', 'awesome-support' ) . '<br /><br />';
		
		echo '<a href = "http://restapidocs.getawesomesupport.com/">' . __( '2. REST API', 'awesome-support' ) . '</a>' . '<br />';
		echo __( 'Documentation for the REST API.', 'awesome-support' ) . '<br /><br />';
		
		echo '<h2>' . __( 'Import Tickets (Zendesk, Ticksy, Helpscout)', 'awesome-support' ) . '</h2>' . '<br />';		
		echo '<a href = "https://getawesomesupport.com/addons/awesome-support-importer/">' . __( '1. Install The FREE Importer', 'awesome-support' ) . '</a>' . '<br />';
		echo __( 'The link above will direct you to the page with the importer add-on', 'awesome-support' ) . '<br /><br />';		

		echo '<a href = "https://getawesomesupport.com/documentation/importer/installation/">' . __( '2. Importer Documentation', 'awesome-support' ) . '</a>' . '<br />';		
		echo __( 'Read the documentation to learn how to import tickets from Zendesk, Ticksy and Helpscout', 'awesome-support' ) . '<br /><br />';		

		$content = ob_get_clean();

		return $content;


	}

	/**
	 * * Add content to bulk actions tab
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function bulk_actions_tab_content( $content ) {
		return '<div id="bulk_action_tab_content_placeholder" class="actions"></div>';
	}


	/***
	 * Display filters
	 */
	public function custom_filters() {

		/* STATE */

		$this_sort       = isset( $_GET[ 'status' ] ) ? filter_input( INPUT_GET, 'status', FILTER_SANITIZE_STRING ) : 'open';
		$all_selected    = ( 'any' === $this_sort ) ? 'selected="selected"' : '';
		$open_selected   = ( ! isset( $_GET[ 'status' ] ) && true === (bool) wpas_get_option( 'hide_closed' ) || 'open' === $this_sort ) ? 'selected="selected"' : '';
		$closed_selected = ( 'closed' === $this_sort ) ? 'selected="selected"' : '';

		$dropdown = '<select id="status" name="status">';
		$dropdown .= "<option value='any' $all_selected>" . __( 'All States', 'awesome-support' ) . "</option>";
		$dropdown .= "<option value='open' $open_selected>" . __( 'Open', 'awesome-support' ) . "</option>";
		$dropdown .= "<option value='closed' $closed_selected>" . __( 'Closed', 'awesome-support' ) . "</option>";
		$dropdown .= '</select>';

		echo $dropdown;


		/* STATUS */

		if ( ! isset( $_GET[ 'post_status' ] )
		     || isset( $_GET[ 'post_status' ] ) && 'trash' !== $_GET[ 'post_status' ]
		) {
			$this_sort    = isset( $_GET[ 'post_status' ] ) ? filter_input( INPUT_GET, 'post_status', FILTER_SANITIZE_STRING ) : 'any';
			$all_selected = ( 'any' === $this_sort ) ? 'selected="selected"' : '';

			$dropdown = '<select id="post_status" name="post_status" >';
			$dropdown .= "<option value='any' $all_selected>" . __( 'All Status', 'awesome-support' ) . "</option>";

			/**
			 * Get available statuses.
			 */
			$custom_statuses = wpas_get_post_status();

			foreach ( $custom_statuses as $_status_id => $_status_value ) {
				$custom_status_selected = ( isset( $_GET[ 'post_status' ] ) && $_status_id === $this_sort ) ? 'selected="selected"' : '';
				$dropdown               .= "<option value='" . $_status_id . "' " . $custom_status_selected . " >" . __( $_status_value, 'awesome-support' ) . "</option>";
			}

			$dropdown .= '</select>';

			echo $dropdown;
		}


		/* ACTIVITY */
		
		
		$selected_activity        = isset( $_GET[ 'activity' ] ) ? filter_input( INPUT_GET, 'activity', FILTER_SANITIZE_STRING ) : '';
		
		$activity_options = apply_filters( 'wpas_ticket_list_activity_options', array(
			'all' =>					__( 'All Activity', 'awesome-support' ),
			'awaiting_support_reply' => __( 'Awaiting Support Reply', 'awesome-support' ),
			'old' =>					__( 'Old', 'awesome-support' ) . " (Open > " . wpas_get_option( 'old_ticket' ) . " Days)"
			
		) );
		

		$dropdown = '<select id="activity" name="activity">';
		
		foreach ( $activity_options as $a_value => $a_name ) {
			$selected = $selected_activity === $a_value ? ' selected="selected"' : '';
			$dropdown .= "<option value=\"{$a_value}\"{$selected}>{$a_name}</option>";
		}
		
		$dropdown .= '</select>';

		echo $dropdown;


		$fields = $this->get_custom_fields();


		/* AGENT */

		if ( $fields[ 'assignee' ][ 'args' ][ 'filterable' ] ) {

			$selected       = __( 'All Agents', 'awesome-support' );
			$selected_value = '';

			if ( isset( $_GET[ 'assignee' ] ) && ! empty( $_GET[ 'assignee' ] ) ) {
				$staff_id = (int) $_GET[ 'assignee' ];
				$agent    = new WPAS_Member_Agent( $staff_id );

				if ( $agent->is_agent() ) {
					$user           = get_user_by( 'ID', $staff_id );
					$selected       = $user->display_name;
					$selected_value = $staff_id;
				}
			}

			$staff_atts = array(
				'name'      => 'assignee',
				'id'        => 'assignee',
				'disabled'  => ! current_user_can( 'assign_ticket' ) ? true : false,
				'select2'   => true,
				'data_attr' => array(
					'capability'  => 'edit_ticket',
					'allowClear'  => true,
					'placeholder' => $selected,
				),
			);

			if ( isset( $staff_id ) ) {
				$staff_atts[ 'selected' ] = $staff_id;
			}

			echo wpas_dropdown( $staff_atts, "<option value='" . $selected_value . "'>" . $selected . "</option>" );

		}


		/* CLIENT */

		$selected       = __( 'All Clients', 'awesome-support' );
		$selected_value = '';

		if ( isset( $_GET[ 'author' ] ) && ! empty( $_GET[ 'author' ] ) ) {
			$client_id      = (int) $_GET[ 'author' ];
			$user           = get_user_by( 'ID', $client_id );
			$selected       = $user->display_name;
			$selected_value = $client_id;
		}

		$client_atts = array(
			'name'      => 'author',
			'id'        => 'author',
			'disabled'  => ! current_user_can( 'assign_ticket' ) ? true : false,
			'select2'   => true,
			'data_attr' => array(
				'capability'  => 'view_ticket',
				'allowClear'  => true,
				'placeholder' => $selected,
			),
		);

		if ( isset( $client_id ) ) {
			$client_atts[ 'selected' ] = $client_id;
		}

		echo wpas_dropdown( $client_atts, "<option value='" . $selected_value . "'>" . $selected . "</option>" );


		/* TICKET ID */
		$selected_value = '';
		if ( isset( $_GET[ 'id' ] ) && ! empty( $_GET[ 'id' ] ) ) {
			$selected_value = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
		}

		echo '<input type="text" placeholder="Ticket ID" name="id" id="id" value="' . $selected_value . '" />';

		echo '<div style="clear:both;"></div>';

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

				if ( ! array_key_exists( $tax_slug, $fields ) ) {
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

					if ( ! empty( $term ) ) {
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

		if ( ! is_array( $meta_query ) ) {
			$meta_query = empty( $meta_query ) ? [] : (array) $meta_query;
		}

		if ( isset( $_GET[ 'assignee' ] ) && ! empty( $_GET[ 'assignee' ] ) ) {

			$staff_id = (int) $_GET[ 'assignee' ];
			$agent    = new WPAS_Member_Agent( $staff_id );

			if ( $agent->is_agent() ) {

				$meta_query[] = array(
					'key'     => '_wpas_assignee',
					'value'   => $staff_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				);
				$wp_query->set( 'meta_key', '_wpas_assignee' );
			}

			if ( ! isset( $meta_query[ 'relation' ] ) ) {
				$meta_query[ 'relation' ] = 'AND';
			}

		}


		$wpas_activity = isset( $_GET[ 'activity' ] ) && ! empty( $_GET[ 'activity' ] ) ? $_GET[ 'activity' ] : 'any';

			if( 'awaiting_support_reply' === $wpas_activity ) {
				$meta_query[] = array(
					'key'     => '_wpas_is_waiting_client_reply',
					'value'   => 1,
					'compare' => '=',
					'type'    => 'numeric',
				);
			}

			elseif( 'old' === $wpas_activity ) {

				$old_after           = (int) wpas_get_option( 'old_ticket' );
				$old_after           = strtotime( 'now' ) + ( $old_after * 86400 );

				$meta_query[] = array(
					'key'     => '_wpas_last_reply_date',
					'value'   => $old_after,
					'compare' => '<=',
					'type'    => 'numeric',
				);
			}

		$wpas_status = isset( $_GET[ 'status' ] ) && ! empty( $_GET[ 'status' ] ) ? $_GET[ 'status' ] : 'open';

		if ( 'any' === $wpas_status ) {

			$meta_query[] = array(
				'relation' => 'OR',
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

		if ( 'open' === $wpas_status ) {

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

		if ( isset( $meta_query ) ) {
			if ( ! isset( $meta_query[ 'relation' ] ) ) {
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
		
		$screen = get_current_screen(); 
		
		if ( $screen->id == 'edit-ticket' ){ 		

			// Map query vars to their keys, or get them if endpoints are not supported
			foreach ( $fields as $key => $var ) {

				if ( isset( $_GET[ $var[ 'name' ] ] ) ) {
					$wp->query_vars[ $key ] = $_GET[ $var[ 'name' ] ];
				} elseif ( isset( $wp->query_vars[ $var[ 'name' ] ] ) ) {
					$wp->query_vars[ $key ] = $wp->query_vars[ $var ];
				}
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

		if ( is_admin() && $wp_query->is_main_query()
		     && ! is_null( filter_input( INPUT_GET, 'id' ) )
		     && 'ticket' === $wp_query->query[ 'post_type' ]
		) {

			global $wpdb;

			$ticket_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );

			/* Filter by Ticket ID */
			if ( ! empty( $ticket_id ) && intval( $ticket_id ) != 0 && 'ticket' === get_post_type( $ticket_id ) && wpas_can_view_ticket( intval( $ticket_id ) ) ) {
				$where = " AND {$wpdb->posts}.ID = " . intval( $ticket_id );
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

		if ( ! isset( $wp_query->query[ 'post_type' ] )
		     || $wp_query->query[ 'post_type' ] !== 'ticket'
		     || ! $wp_query->query_vars_changed
		) {
			return $clauses;
		}

		$fields = $this->get_custom_fields();

		$orderby = isset( $_GET[ 'orderby' ] ) ? $_GET[ 'orderby' ] : '';

		if ( ! empty( $orderby ) && array_key_exists( $orderby, $fields ) ) {

			global $wpdb;

			$order = ( 'ASC' == strtoupper( $wp_query->get( 'order' ) ) ) ? 'ASC' : 'DESC';

			if ( 'taxonomy' == $fields[ $orderby ][ 'args' ][ 'field_type' ] && ! $fields[ $orderby ][ 'args' ][ 'taxo_std' ] ) {

				/*
				 *  Alias taxonomy tables used by sorting in
				 *  case there is an active taxonomy filter. (is_tax())
				 */
				$clauses[ 'join' ] .= <<<SQL
LEFT OUTER JOIN {$wpdb->term_relationships} AS t_rel ON {$wpdb->posts}.ID=t_rel.object_id
LEFT OUTER JOIN {$wpdb->term_taxonomy} AS t_t ON t_t.term_taxonomy_id=t_rel.term_taxonomy_id
LEFT OUTER JOIN {$wpdb->terms} AS tms ON tms.term_id=t_t.term_id
SQL;

				$clauses[ 'where' ]   .= " AND (t_t.taxonomy = '" . $orderby . "' AND t_t.taxonomy IS NOT NULL)";
				$clauses[ 'groupby' ] = "t_rel.object_id";
				$clauses[ 'orderby' ] = "GROUP_CONCAT(tms.name ORDER BY tms.name ASC) " . $order;

			} elseif ( 'id' === $orderby ) {

			} elseif ( 'status' === $orderby ) {

				$clauses[ 'orderby' ] = "{$wpdb->posts}.post_status " . $order;

			} elseif ( 'assignee' === $orderby ) {

				// Join user table onto the postmeta table
				$clauses[ 'join' ]    .= " LEFT JOIN {$wpdb->users} ag ON ( {$wpdb->prefix}postmeta.meta_key='_wpas_assignee' AND CAST({$wpdb->prefix}postmeta.meta_value AS UNSIGNED)=ag.ID)";
				$clauses[ 'orderby' ] = "ag.display_name " . $order;

			} elseif ( 'wpas-client' === $orderby ) {

				// Join user table onto the postmeta table
				$clauses[ 'join' ]    .= " LEFT JOIN {$wpdb->users} ON {$wpdb->prefix}posts.post_author={$wpdb->users}.ID";
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
			unset( $actions[ 'inline hide-if-no-js' ] );
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

		if ( is_array( $submenu ) && array_key_exists( 'edit.php?post_type=ticket', $submenu ) && isset( $submenu[ 5 ] ) ) {
			$submenu[ "edit.php?post_type=ticket" ][ 5 ][ 2 ] = $submenu[ "edit.php?post_type=ticket" ][ 5 ][ 2 ] . '&amp;wpas_status=open';
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

		if ( ! is_admin() || ! isset( $_GET[ 'post_type' ] ) || 'ticket' !== $_GET[ 'post_type' ] ) {
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
		     || ! isset( $_GET[ 'post_type' ] )
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

		if ( 'edit.php' !== $pagenow || ! isset( $_GET[ 'post_type' ] ) || isset( $_GET[ 'post_type' ] ) && 'ticket' !== $_GET[ 'post_type' ] ) {
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