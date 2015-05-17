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
		add_action( 'restrict_manage_posts',             array( $this, 'unreplied_filter' ),             9, 0 );
		add_action( 'admin_menu',                        array( $this, 'hide_closed_tickets' ),         10, 0 );
		add_filter( 'the_excerpt',                       array( $this, 'remove_excerpt' ),              10, 1 );
		add_filter( 'post_row_actions',                  array( $this, 'remove_quick_edit' ),           10, 2 );
		// add_filter( 'views_edit-ticket',                                array( $this, 'test' ),                        10, 1 );
		// add_action( 'quick_edit_custom_box',                            array( $this, 'custom_quickedit_options' ), 10, 2 );
		// add_action( 'bulk_edit_custom_box',                             array( $this, 'custom_quickedit_options' ), 10, 2 );
		// add_action( 'wp_ajax_save_bulk_edit_book',                      array( $this, 'save_bulk_edit_ticket' ), 10, 0 );
		// add_filter( 'update_user_metadata',                             array( $this, 'set_list_mode' ), 10, 5 );
		// add_filter( 'parse_query',                                      array( $this, 'filter_by_replies' ), 10, 1 );
	}

	public function test( $views ) {

		global $wp_query;

		print_r( wp_count_posts( 'ticket' ) );

//		print_r( $wp_query );

		return $views;
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
         * @global  object  $post
	 * @param   array   $actions    An array of row action links.
         * @return  array               Updated array of row action links
         */
        public function remove_quick_edit( $actions ) {
            global $post;

            if( $post->post_type === 'ticket' ) {
                unset($actions['inline hide-if-no-js']);
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
						$new = array_merge( $new, array( 'wpas-assignee' => __( 'Support Staff', 'wpas' ) ) );
				}

				/* Add the activity column */
				$new = array_merge( $new, array( 'wpas-activity' => __( 'Activity', 'wpas' ) ) );
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

		$mode = get_user_setting( 'tickets_list_mode', 'details' );

		switch ( $column ) {

			case 'ticket_id':

				$link = add_query_arg( array( 'post' => $post_id, 'action' => 'edit' ), admin_url( 'post.php' ) );
				echo "<a href='$link'>#$post_id</a>";

				break;

			case 'wpas-assignee':

				$assignee = get_post_meta( $post_id, '_wpas_assignee', true );
				$agent    = get_user_by( 'id', $assignee );
				echo $agent->data->display_name;

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

					?><li><?php printf( _x( 'Created %s ago.', 'Ticket created on', 'wpas' ), human_time_diff( get_the_time( 'U', $post_id ), current_time( 'timestamp' ) ) ); ?></li><?php

					/**
					 * We check when was the last reply (if there was a reply).
					 * Then, we compute the ticket age and if it is considered as
					 * old, we display an informational tag.
					 */
					if ( !isset( $activity_meta['reply_date'] ) ) {
						echo '<li>';
						echo _x( 'No reply yet.', 'No last reply', 'wpas' );
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
						$role  = true === user_can( $activity_meta['user_id'], 'edit_ticket' ) ? _x( 'agent', 'User role', 'wpas' ) : _x( 'client', 'User role', 'wpas' );

						?><li><?php echo _x( sprintf( _n( '%s reply.', '%s replies.', $query->post_count, 'wpas' ), $query->post_count ), 'Number of replies to a ticket', 'wpas' ); ?></li><?php
						?><li><?php printf( _x( '<a href="%s">Last replied</a> %s ago by %s (%s).', 'Last reply ago', 'wpas' ), add_query_arg( array( 'post' => $post_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) . '#wpas-post-' . $query->posts[0]->ID, human_time_diff( strtotime( $activity_meta['reply_date'] ), current_time( 'timestamp' ) ), '<a href="' . $activity_meta['user_link'] . '">' . $activity_meta['user_nicename'] . '</a>', $role ); ?></li><?php
						?><li><?php //printf( _x( 'Last replied by %s.', 'Last reply author', 'wpas' ), '<a href="' . $activity_meta['user_link'] . '">' . $activity_meta['user_nicename'] . '</a>' ); ?></li><?php
					}

				endif;

				/**
				 * Add tags
				 */
				if ( true === wpas_is_reply_needed( $post_id, $latest ) ) {
					$color = ( false !== ( $c = wpas_get_option( 'color_awaiting_reply', false ) ) ) ? $c : '#0074a2';
					array_push( $tags, "<span class='wpas-label' style='background-color:$color;'>" . __( 'Awaiting Support Reply', 'wpas' ) . "</span>" );
				}


				if ( true === wpas_is_ticket_old( $post_id, $latest ) ) {
					$old_color = wpas_get_option( 'color_old' );
					array_push( $tags, "<span class='wpas-label' style='background-color:$old_color;'>" . __( 'Old', 'wpas' ) . "</span>" );
				}

				if ( !empty( $tags ) ) {
					echo '<li>' . implode( ' ', $tags ) . '</li>';
				}

				echo '</ul>';

				break;

		}

	}

	/**
	 * Add quick ticket actions.
	 *
	 * Add options to change ticket state and status in the quick edit box.
	 *
	 * @since  3.0.0
	 * @param  array $column_name ID of the current column
	 * @param  string $post_type  Post type
	 * @return void
	 */
	public function custom_quickedit_options( $column_name, $post_type ) {

		if ( 'ticket' !== $post_type ) {
			return false;
		}

		if ( 'status' === $column_name ):

			$custom_status = wpas_get_post_status(); ?>

			<fieldset class="inline-edit-col-right inline-edit-ticket">
				<div class="inline-edit-col column-<?php echo $column_name ?>">
					<div class="inline-edit-group">
						<label class="inline-edit-group">
							<span class="title"><?php _e( 'Ticket Status', 'wpas' ); ?></span>
							<select name="_wpas_status">
								<option value="open"><?php _e( 'Open', 'wpas' ); ?></option>
								<option value="closed"><?php _e( 'Closed', 'wpas' ); ?></option>
							</select>
						</label>
					</div>
					<div class="inline-edit-group">
						<label class="inline-edit-group">
							<span class="title"><?php _e( 'Ticket State', 'wpas' ); ?></span>
							<select name="_wpas_state">
								<?php
								foreach ( $custom_status as $status_id => $status_label ) {
									?><option value="<?php echo $status_id; ?>"><?php echo $status_label; ?></option><?php
								}
								?>
							</select>
						</label>
					</div>
				</div>
			</fieldset>

		<?php endif;
	}

	public function save_bulk_edit_ticket() {

		// TODO perform nonce checking
		// get our variables
		$post_ids = ( ! empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();
		$status   = ( ! empty( $_POST[ '_wpas_status' ] ) ) ? $_POST[ '_wpas_status' ] : null;
		$state    = ( ! empty( $_POST[ '_wpas_state' ] ) ) ? $_POST[ '_wpas_state' ] : null;

		wpas_debug_display( $post_ids );
		wpas_debug_display( $status );
		wpas_debug_display( $state );
		exit;

		// if everything is in order
		if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {
			foreach( $post_ids as $post_id ) {

				wpas_update_ticket_status( $post_id, $state );

				if ( in_array( $status, array( 'open', 'closed' ) ) ) {
					update_post_meta( $post_id, '_wpas_status', $status );
				}
			}
		}

		die();
	}

	/**
	 * Add status dropdown in the filters bar.
	 *
	 * @since  2.0.0
	 */
	public function unreplied_filter() {

		global $typenow;

		if ( 'ticket' != $typenow ) {
			return;
		}

		$this_sort       = isset( $_GET['wpas_replied'] ) ? $_GET['wpas_replied'] : '';
		$all_selected    = ( '' === $this_sort ) ? 'selected="selected"' : '';
		$replied_selected   = ( 'replied' === $this_sort ) ? 'selected="selected"' : '';
		$unreplied_selected = ( 'unreplied' === $this_sort ) ? 'selected="selected"' : '';
		$dropdown        = '<select id="wpas_status" name="wpas_replied">';
		$dropdown        .= "<option value='' $all_selected>" . __( 'Any Reply Status', 'wpas' ) . "</option>";
		$dropdown        .= "<option value='replied' $replied_selected>" . __( 'Replied', 'wpas' ) . "</option>";
		$dropdown        .= "<option value='unreplied' $unreplied_selected>" . __( 'Unreplied', 'wpas' ) . "</option>";
		$dropdown        .= '</select>';

		echo $dropdown;

	}

	/**
	 * Hide closed tickets.
	 *
	 * If the plugin is set to hide closed tickets,
	 * we modify the "All Tickets" link in the post type menu
	 * and append the status filter with the "open" value.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function hide_closed_tickets() {
            $hide = boolval( wpas_get_option( 'hide_closed' ) );

            if ( true !== $hide ) {
                    return false;
            }

            global $submenu;

            if ( is_array( $submenu ) && array_key_exists( 'edit.php?post_type=ticket', $submenu ) && isset($submenu[5])) {
                    $submenu["edit.php?post_type=ticket"][5][2] = $submenu["edit.php?post_type=ticket"][5][2] . '&amp;wpas_status=open';
            }

            return true;
	}

	/**
	 * Filter tickets by status.
	 *
	 * When filtering, WordPress uses the ID by default in the query but
	 * that doesn't work. We need to convert it to the taxonomy term.
	 *
	 * @since  3.0.0
	 * @param  object $query WordPress current main query
	 */
	public function filter_by_replies( $query ) {

		global $pagenow;

		/* Check if we are in the correct post type */
		if ( is_admin()
			&& 'edit.php' == $pagenow
			&& isset( $_GET['post_type'] )
			&& 'ticket' == $_GET['post_type']
			&& isset( $_GET['wpas_replied'] )
			&& !empty( $_GET['wpas_replied'] )
			&& $query->is_main_query() ) {

			print_r( $query );
			// $query->query_vars['meta_key']     = '_wpas_status';
			// $query->query_vars['meta_value']   = sanitize_text_field( $_GET['wpas_status'] );
			// $query->query_vars['meta_compare'] = '=';
		}

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
	 * Update tickets list view.
	 * 
	 * @param  [type] $check      [description]
	 * @param  [type] $object_id  [description]
	 * @param  [type] $meta_key   [description]
	 * @param  [type] $meta_value [description]
	 * @param  [type] $prev       [description]
	 * @return [type]             [description]
	 */
	public function set_list_mode( $check, $object_id, $meta_key, $meta_value, $prev_value ) {

		if ( isset( $_GET['post_type'] ) && 'ticket' === $_GET['post_type'] ) {

			if ( 'wp_user-settings' === $meta_key ) {
				
				parse_str( $meta_value, $values );

				/* Check if the option being updated is the list view mode */
				if ( array_key_exists( 'posts_list_mode', $values ) && isset( $_REQUEST['mode'] ) ) {

					$val = 'excerpt' === $_REQUEST['mode'] ? 'details' : 'list';
					remove_filter( 'update_user_metadata', 'wpas_set_list_mode', 10 );
					set_user_setting( 'tickets_list_mode', $val );

					return false;

				}

			}

		}

		return $check;

		/**
		 * Set the ticket list mode.
		 */
		// global $mode;

		// if ( ! empty( $_REQUEST['mode'] ) ) {

		// 	$mode = $_REQUEST['mode'];

		// 	if ( isset( $_GET['post_type'] ) && 'ticket' === $_GET['post_type'] ) {

		// 		if ( 'excerpt' === $mode ) {
		// 			$mode = 'details';
		// 			set_user_setting ( 'tickets_list_mode', $mode );
		// 			delete_user_setting( 'posts_list_mode' );
		// 		}

		// 		if ( 'list' === $mode ) {
		// 			set_user_setting ( 'tickets_list_mode', $mode );
		// 		}

		// 	}

		// 	$mode = $_REQUEST['mode'] == 'excerpt' ? 'excerpt' : 'list';
		// 	set_user_setting ( 'posts_list_mode', $mode );
		// } else {
		// 	$mode = get_user_setting ( 'posts_list_mode', 'list' );
		// }

	}

}