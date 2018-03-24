<?php
	/**
	 * Templating Functions.
	 *
	 * This file contains all the templating functions. It aims at making it easy
	 * for developers to gather ticket details and insert them in a custom template.
	 *
	 * @package   Awesome_Support
	 * @author    AwesomeSupport <contact@getawesomesupport.com>
	 * @license   GPL-2.0+
	 * @link      https://getawesomesupport.com
	 * @copyright 2014-2017 AwesomeSupport
	 */

	add_filter( 'the_content', 'wpas_single_ticket', 10, 1 );
	/**
	 * Alter page content for single ticket.
	 *
	 * In order to ensure maximum compatibility with all themes,
	 * we hook onto the_content instead of changing the entire template
	 * for ticket single.
	 *
	 * However, if the theme author has customized the single ticket template
	 * we do not apply those modifications as the custom template will do the job.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $content Post content
	 *
	 * @return string          Ticket single
	 */
	function wpas_single_ticket( $content = '' ) {

		global $post;

		$slug = 'ticket';

		/* Don't touch the admin */
		if ( is_admin() ) {
			return $content;
		}

		/* Only apply this on the ticket single. */
		if ( $post && $slug !== $post->post_type ) {
			return $content;
		}

		/* Only apply this on the main query. */
		if ( ! is_main_query() ) {
			return $content;
		}

		/* Only apply this if it's inside of a loop. */
		if ( ! in_the_loop() ) {
			return $content;
		}

		/* Remove the filter to avoid infinite loops. */
		remove_filter( 'the_content', 'wpas_single_ticket' );

		/* Check if the current user can view the ticket */
		if ( ! wpas_can_view_ticket( $post->ID ) ) {

			if ( is_user_logged_in() ) {
				return wpas_get_notification_markup( 'failure', __( 'You are not allowed to view this ticket.', 'awesome-support' ) );
			} else {

				$output = '';
				$output .= wpas_get_notification_markup( 'failure', __( 'You are not allowed to view this ticket.', 'awesome-support' ) );

				ob_start();
				wpas_get_template( 'registration' );
				$output .= ob_get_clean();

				return $output;

			}
		}

		/* Get template name */
		$template_path = get_page_template();
		$template      = explode( '/', $template_path );
		$count         = count( $template );
		$template      = $template[ $count - 1 ];

		/* Don't apply the modifications on a custom template */
		if ( "single-$slug.php" === $template ) {
			return $content;
		}

		/* Get the ticket content */
		ob_start();

		/**
		 * wpas_frontend_plugin_page_top is executed at the top
		 * of every plugin page on the front end.
		 */
		do_action( 'wpas_frontend_plugin_page_top', $post->ID, $post );

		/**
		 * Get the custom template.
		 */
		wpas_get_template( 'details' );

		/**
		 * Finally get the buffer content and return.
		 *
		 * @var string
		 */
		$content = ob_get_clean();

		return $content;

	}

	/**
	 * Get the current theme name.
	 *
	 * @since  3.0.0
	 * @return string The theme name
	 */
	function wpas_get_theme() {
		return ( '' != ( $t = wpas_get_option( 'theme', 'default' ) ) ) ? $t : 'default';
	}

	/**
	 * Get plugin template.
	 *
	 * The function takes a template file name and loads it
	 * from whatever location the template is found first.
	 * The template is being searched for (in order) in
	 * the child theme, the theme and the default templates
	 * folder within the plugin.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $name Name of the template to include
	 * @param  array $args Pass variables to the template
	 *
	 * @return boolean True if a template is loaded, false otherwise
	 */
	function wpas_get_template( $name, $args = array() ) {

		if ( $args && is_array( $args ) ) {
			extract( $args );
		}

		$template = wpas_locate_template( $name );

		if ( ! file_exists( $template ) ) {
			return false;
		}

		$template = apply_filters( 'wpas_get_template', $template, $name, $args );

		do_action( 'wpas_before_template', $name, $template, $args );

		include( $template );

		do_action( 'wpas_after_template', $name, $template, $args );

		return true;

	}

	/**
	 * Locate plugin template.
	 *
	 * The function will locate the template and return the path
	 * from the child theme, if no child theme from the theme,
	 * and if no template in the theme it will load the default
	 * template stored in the plugin's /templates directory.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $name Name of the template to locate
	 *
	 * @return string Template path
	 */
	function wpas_locate_template( $name ) {

		$theme    = wpas_get_theme();
		$filename = "$name.php";

		$template = locate_template(
			array(
				WPAS_TEMPLATE_PATH . $filename,
			)
		);

		if ( ! $template ) {
			$template = WPAS_PATH . "themes/$theme/" . $filename;
		}

		return apply_filters( 'wpas_locate_template', $template, $name );

	}

	/**
	 * Get the plugin's theme stylesheet path.
	 *
	 * @since  3.1.6
	 * @return string Stylesheet path
	 */
	function wpas_get_theme_stylesheet() {

		$theme = wpas_get_theme();

		$template = locate_template(
			array(
				WPAS_TEMPLATE_PATH . 'style.css',
				WPAS_TEMPLATE_PATH . 'css/style.css',
			)
		);

		if ( ! $template ) {
			$template = WPAS_PATH . "themes/$theme/css/style.css";
		}

		return apply_filters( 'wpas_get_theme_stylesheet', $template );

	}

	/**
	 * Get plugin's theme stylesheet URI.
	 *
	 * @since  3.1.6
	 * @return string Stylesheet URI
	 */
	function wpas_get_theme_stylesheet_uri() {

		$template = wpas_get_theme_stylesheet();

		/* Remove the root path and replace backslashes by slashes */
		$truncate = str_replace( '\\', '/', str_replace( untrailingslashit( ABSPATH ), '', $template ) );

		/* Make sure the truncated string doesn't start with a slash because we trailing slash the home URL) */
		if ( '/' === substr( $truncate, 0, 1 ) ) {
			$truncate = substr( $truncate, 1 );
		}

		/* Build the final URL to the resource */
		$uri = trailingslashit( site_url() ) . $truncate;

		return apply_filters( 'wpas_get_theme_stylesheet_uri', $uri );

	}

	/**
	 * Get the ticket header.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $args Additional parameters
	 *
	 * @return void
	 */
	function wpas_ticket_header( $args = array() ) {

		global $post;

		$default = array(
			'container'       => '',
			'container_id'    => '',
			'container_class' => '',
			'table_id'        => "header-ticket-$post->ID",
			'table_class'     => 'wpas-table wpas-ticket-details-header',
		);

		$args = wp_parse_args( $args, $default );

		$custom_fields = WPAS()->custom_fields->get_custom_fields();

		$columns = array(
			'id'     => __( 'ID', 'awesome-support' ),
			'status' => __( 'Status', 'awesome-support' ),
			'date'   => __( 'Date', 'awesome-support' ),
		);

		$columns_callbacks = array(
			'id'     => 'id',
			'status' => 'wpas_cf_display_status',
			'date'   => 'date',
		);

		foreach ( $custom_fields as $field ) {

			/* Don't display core fields */
			if ( true === $field[ 'args' ][ 'core' ] ) {
				continue;
			}

			/* Don't display fields that aren't specifically designed to be displayed on the front end*/
			if ( ( false === $field[ 'args' ][ 'hide_front_end' ] ) && ( false === $field[ 'args' ][ 'backend_only' ] ) && ( true === $field[ 'args' ][ 'show_frontend_detail' ] )  ) {
				$columns[ $field[ 'name' ] ]           = ! empty( $field[ 'args' ][ 'title' ] ) ? sanitize_text_field( $field[ 'args' ][ 'title' ] ) : wpas_get_title_from_id( $field[ 'name' ] );
				$columns_callbacks[ $field[ 'name' ] ] = ( 'taxonomy' === $field[ 'args' ][ 'field_type' ] && true === $field[ 'args' ][ 'taxo_std' ] ) ? 'taxonomy' : $field[ 'args' ][ 'column_callback' ];
			}

		}

		$columns           = apply_filters( 'wpas_tickets_details_columns', $columns );
		$columns_callbacks = apply_filters( 'wpas_tickets_details_columns_callbacks', $columns_callbacks );
		?>

		<?php if ( ! empty( $args[ 'container' ] ) ): ?><<?php echo $args[ 'container' ]; ?>><?php endif; ?>

        <table id="<?php echo $args[ 'table_id' ]; ?>" class="<?php echo $args[ 'table_class' ]; ?>">
            <thead>
            <tr>
				<?php foreach ( $columns as $column => $label ): ?>
                    <th><?php echo $label; ?></th>
				<?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <tr>
				<?php foreach ( $columns_callbacks as $column => $callback ): ?>
                    <td>
						<?php wpas_get_tickets_list_column_content( $column, array( 'callback' => $callback ) ); ?>
                    </td>
				<?php endforeach; ?>
            </tr>
            </tbody>
        </table>

		<?php if ( ! empty( $args[ 'container' ] ) ): ?></<?php echo $args[ 'container' ]; ?>><?php endif;

	}

	/**
	 * Display the reply form.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $args Additional arguments
	 *
	 * @return void
	 */
	function wpas_get_reply_form( $args = array() ) {

		global $wp_query;

		$post_id = $wp_query->post->ID;
		$status  = wpas_get_ticket_status( $post_id );

		$defaults = array(
			'form_id'         => 'wpas-new-reply',
			'form_class'      => 'wpas-form',
			'container'       => 'div',
			'container_id'    => 'wpas-reply-box',
			'container_class' => 'wpas-form-group wpas-wysiwyg-textarea',
			'textarea_before' => '',
			'textarea_after'  => '',
			'textarea_class'  => 'wpas-form-control wpas-wysiwyg',
		);

		$args = wp_parse_args( $args, $defaults );

		/**
		 * Filter the form class.
		 *
		 * This can be useful for addons doing something on the reply form,
		 * like adding an upload feature for instance.
		 *
		 * @since  3.0.0
		 * @var    string
		 */
		$form_class = apply_filters( 'wpas_frontend_reply_form_class', $args[ 'form_class' ] );

		/**
		 * wpas_ticket_details_reply_form_before hook
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_ticket_details_reply_form_before' );

		if ( 'closed' === $status ):

			echo wpas_get_notification_markup( 'info', sprintf( __( 'The ticket has been closed. If you feel that your issue has not been solved yet or something new came up in relation to this ticket, <a href="%s">you can re-open it by clicking this link</a>.', 'awesome-support' ), wpas_get_reopen_url() ) );

		/**
		 * Check if the ticket is currently open and if the current user
		 * is allowed to post a reply.
		 */
		elseif ( 'open' === $status && true === wpas_can_reply_ticket() ): ?>

        <form id="<?php echo $args[ 'form_id' ]; ?>" class="<?php echo $form_class; ?>" method="post"
              action="<?php echo get_permalink( $post_id ); ?>" enctype="multipart/form-data">

			<?php
			/**
			 * wpas_ticket_details_reply_textarea_before hook
			 *
			 * @since  3.0.0
			 */
			do_action( 'wpas_ticket_details_reply_textarea_before' ); ?>

            <<?php echo $args[ 'container' ]; ?> id="<?php echo $args[ 'container_id' ]; ?>"
            class="<?php echo $args[ 'container_class' ]; ?>">
			<?php echo $args[ 'textarea_before' ];

			/**
			 * Load the visual editor if enabled
			 */
			if ( true === boolval( wpas_get_option( 'frontend_wysiwyg_editor' ) ) ) {

				$editor_defaults = apply_filters( 'wpas_ticket_editor_args', array(
					'media_buttons' => false,
					'textarea_name' => 'wpas_user_reply',
					'textarea_rows' => 10,
					'tabindex'      => 2,
					'editor_class'  => $args[ 'textarea_class' ],
					'quicktags'     => false,
					'tinymce'       => array(
						'toolbar1' => 'bold,italic,underline,strikethrough,hr,|,bullist,numlist,|,link,unlink',
						'toolbar2' => '',
					),
				) );

				wp_editor( '', 'wpas-reply-wysiwyg', apply_filters( 'wpas_reply_wysiwyg_args', $editor_defaults ) );

			} /**
			 * Otherwise just load a textarea
			 */
			else {

				/**
				 * Define if the reply can be submitted empty or not.
				 *
				 * @since  3.0.0
				 * @var boolean
				 */
				$can_submit_empty = apply_filters( 'wpas_can_reply_be_empty', false );
				?>
                <textarea class="form-control" rows="10" name="wpas_user_reply" rows="6" id="wpas-reply-textarea"
                          placeholder="<?php _e( 'Type your reply here.', 'awesome-support' ); ?>"
				          <?php if ( false === $can_submit_empty ): ?>required="required"<?php endif; ?>></textarea>
			<?php }

			echo $args[ 'textarea_after' ]; ?>
            </<?php echo $args[ 'container' ]; ?>>

			<?php
			/**
			 * wpas_ticket_details_reply_textarea_after hook
			 *
			 * @since  3.0.0
			 */
			do_action( 'wpas_ticket_details_reply_textarea_after' );

			if ( current_user_can( 'close_ticket' ) && apply_filters( 'wpas_user_can_close_ticket', true, $post_id ) ): ?>

                <div class="checkbox">
                    <label for="close_ticket" data-toggle="tooltip" data-placement="right" title=""
                           data-original-title="<?php _e( 'No reply is required to close', 'awesome-support' ); ?>">
                        <input type="checkbox" name="wpas_close_ticket" id="close_ticket"
                               value="true"> <?php _e( 'Close this ticket', 'awesome-support' ); ?>
                    </label>
                </div>

			<?php endif;

			/**
			 * wpas_ticket_details_reply_close_checkbox_after hook
			 *
			 * @since  3.0.0
			 */
			do_action( 'wpas_ticket_details_reply_close_checkbox_after' ); ?>

            <input type="hidden" name="ticket_id" value="<?php echo $post_id; ?>"/>

			<?php
			wp_nonce_field( 'send_reply', 'client_reply', false, true );
			wpas_do_field( 'submit_new_reply' );
			wpas_make_button( __( 'Reply', 'awesome-support' ), array(
				'name'     => 'wpas-submit',
				'onsubmit' => __( 'Please Wait...', 'awesome-support' ),
			) );

			/**
			 * wpas_ticket_details_reply_close_checkbox_after hook
			 *
			 * @since  3.0.0
			 */
			do_action( 'wpas_ticket_details_reply_form_before_close' );
			?>

            </form>

			<?php
		/**
		 * This case is an agent viewing the ticket from the front-end. All actions are tracked in the back-end only, that's why we prevent agents from replying through the front-end.
		 */
		elseif ( 'open' === $status && false === wpas_can_reply_ticket() ):
			echo wpas_get_notification_markup( 'info', sprintf( __( 'To reply to this ticket, please <a href="%s">go to your admin panel</a>.', 'awesome-support' ), add_query_arg( array(
				'post'   => $post_id,
				'action' => 'edit',
			), admin_url( 'post.php' ) ) ) );
		else:
			echo wpas_get_notification_markup( 'info', __( 'You are not allowed to reply to this ticket.', 'awesome-support' ) );
		endif;

		/**
		 * wpas_ticket_details_reply_form_after hook
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_ticket_details_reply_form_after' );

	}

	/**
	 * Get the URL to re-open a ticket.
	 *
	 * @since  3.0.0
	 *
	 * @param  integer $ticket_id ID of the ticket to re-open
	 *
	 * @return string             The URL to trigger re-opening the ticket
	 */
	function wpas_get_reopen_url( $ticket_id = null ) {

		global $wp_query;

		if ( is_null( $ticket_id ) ) {
			$ticket_id = intval( $wp_query->post->ID );
		}

		$url = wpas_do_url( get_permalink( $ticket_id ), 'reopen_ticket', array( 'ticket_id' => $ticket_id ) );

		return apply_filters( 'wpas_reopen_url', esc_url( $url ), $ticket_id );

	}

	/**
	 * Get the login URL.
	 *
	 * This function returns the URL of the page used for logging in.
	 * As of now it just uses the current post ID,
	 * but it might be changed in the future.
	 *
	 * @since  3.0.0
	 * @return string URL of the login page
	 */
	function wpas_get_login_url() {

		global $post;

		return get_permalink( $post->ID );

	}

	/**
	 * Get tickets list columns.
	 *
	 * Retrieve the columns to display on the list of tickets
	 * in the client area. The columns include the 3 basic ones
	 * (status, title and date), and also the custom fields that are
	 * set to show on front-end (and that are not core CF).
	 *
	 * @since  3.0.0
	 * @return array The list of columns with their title and callback
	 */
	function wpas_get_tickets_list_columns() {

		$custom_fields = WPAS()->custom_fields->get_custom_fields();

		$columns = array(
			'status' => array(
				'title'             => __( 'Status', 'awesome-support' ),
				'callback'          => 'wpas_cf_display_status',
				'column_attributes' => array( 'head' => array( 'sort-ignore' => true ) ),
			),
			'title'  => array( 'title' => __( 'Title', 'awesome-support' ), 'callback' => 'title' ),
			'date'   => array(
				'title'             => __( 'Date', 'awesome-support' ),
				'callback'          => 'date',
				'column_attributes' => array(
					'head' => array( 'type' => 'numeric', 'sort-initial' => 'descending' ),
					'body' => array( 'value' => 'wpas_get_the_time_timestamp' ),
				),
			),
		);

		foreach ( $custom_fields as $field ) {

			/* Don't display core fields */
			if ( true === $field[ 'args' ][ 'core' ] ) {
				continue;
			}

			/* Don't display fields that aren't specifically designed to be shown */
			if ( ( true !== $field[ 'args' ][ 'backend_only' ] )
			     && ( true !== $field[ 'args' ][ 'hide_front_end' ] )
				 && ( true === $field[ 'args' ][ 'show_frontend_list' ] )
			) {

				$column_title                = apply_filters( 'wpas_custom_column_title', wpas_get_field_title( $field ), $field );
				$column_callback             = ( 'taxonomy' === $field[ 'args' ][ 'field_type' ] && true === $field[ 'args' ][ 'taxo_std' ] ) ? 'taxonomy' : $field[ 'args' ][ 'column_callback' ];
				$columns[ $field[ 'name' ] ] = array( 'title' => $column_title, 'callback' => $column_callback );

				if ( ! empty( $field[ 'args' ][ 'column_attributes' ] ) && is_array( $field[ 'args' ][ 'column_attributes' ] ) ) {
					$columns[ $field[ 'name' ] ] = $field[ 'args' ][ 'column_attributes' ];
				}

			}

		}

		return apply_filters( 'wpas_tickets_list_columns', $columns );

	}

	/**
	 * Get tickets lit columns content.
	 *
	 * Based on the columns displayed in the front-end tickets list,
	 * this function will display the column content by using its callback.
	 * The callback can be a "standard" case like the title, or a custom function
	 * as used by the custom fields mostly.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $column_id ID of the current column
	 * @param  array $column Columns data
	 *
	 * @return void
	 */
	function wpas_get_tickets_list_column_content( $column_id, $column ) {

		$callback = $column[ 'callback' ];

		switch ( $callback ) {

			case 'id':
				echo '#' . get_the_ID();
				break;

			case 'status':
				echo wpas_get_ticket_status( get_the_ID() );
				break;

			case 'title':

				// If the replies are displayed from the oldest to the newest we want to link directly to the latest reply in case there are multiple reply pages
				if ( 'ASC' === wpas_get_option( 'replies_order', 'ASC' ) ) {
					$last_reply = wpas_get_replies( get_the_ID(), array(
						'read',
						'unread',
					), array( 'posts_per_page' => 1, 'order' => 'DESC' ) );
					$link       = ! empty( $last_reply ) ? wpas_get_reply_link( $last_reply[ 0 ]->ID ) : get_permalink( get_the_ID() );
				} else {
					$link = get_permalink( get_the_ID() );
				}

				$id    = get_the_ID();
				$title = get_the_title();
				$title .= " (#$id)";

				?><a href="<?php echo $link; ?>"><?php echo $title; ?></a><?php
				break;

			case 'date':
				$offset = wpas_get_offset_html5();
				?>
                <time
                datetime="<?php echo get_the_date( 'Y-m-d\TH:i:s' ) . $offset ?>"><?php echo get_the_date( get_option( 'date_format' ) ) . ' ' . get_the_date( get_option( 'time_format' ) ); ?></time><?php
				break;

			case 'taxonomy':

				$terms = get_the_terms( get_the_ID(), $column_id );
				$list  = array();

				if ( empty( $terms ) ) {
					continue;
				}

				foreach ( $terms as $term ) {
					array_push( $list, $term->name );
				}

				echo implode( ', ', $list );

				break;

			default:

                if ( ( ! is_array( $callback ) && function_exists( $callback ) )
                     || ( is_array( $callback ) && method_exists( $callback[0], $callback[1] ) )
                ) {
					call_user_func( $callback, $column_id, get_the_ID() );
				}

				break;

		}

	}

	/**
	 * Get HTML5 offset.
	 *
	 * Get the time offset based on the WordPress settings
	 * and convert it into a standard HTML5 format.
	 *
	 * @since  3.0.0
	 * @return string HTML5 formatted time offset
	 */
	function wpas_get_offset_html5() {

		$offset = get_option( 'gmt_offset' );

		/* Transform the offset in a W3C compliant format for datetime */
		$offset  = explode( '.', $offset );
		$hours   = $offset[ 0 ];
		$minutes = isset( $offset[ 1 ] ) ? $offset[ 1 ] : '00';
		$sign    = ( '-' === substr( $hours, 0, 1 ) ) ? '-' : '+';

		/* Remove the sign from the hours */
		if ( '-' === substr( $hours, 0, 1 ) ) {
			$hours = substr( $hours, 1 );
		}

		if ( 5 == $minutes ) {
			$minutes = '30';
		}

		if ( 1 === strlen( $hours ) ) {
			$hours = "0$hours";
		}

		$offset = "$sign$hours:$minutes";

		return $offset;

	}

	/**
	 * Display taxonomy terms.
	 *
	 * This function is used to display a taxonomy's terms
	 * and is necessary for non standard taxonomies (such as product).
	 *
	 * @since  3.1.3
	 *
	 * @param  string $field ID of the field to display
	 * @param  integer $post_id ID of the current post
	 * @param string $separator Separator used to join the taxonomy values
	 *
	 * @return void
	 */
	function wpas_show_taxonomy_column( $field, $post_id, $separator = ', ' ) {

		$terms = get_the_terms( $post_id, $field );
		$list  = array();

		if ( ! is_array( $terms ) ) {
			echo '';
		} else {

			foreach ( $terms as $term ) {

				$term_title = apply_filters( 'wpas_taxonomy_name', $term->name, $post_id, $field );

				if ( is_admin() ) {
					$get           = (array) $_GET;
					$get[ $field ] = isset( $term->post_id ) ? $term->post_id : $term->term_id; // Check for $term->post_id which is set when products are synchronized
					$url           = add_query_arg( $get, admin_url( 'edit.php' ) );
					$item          = "<a href='$url'>{$term_title}</a>";
				} else {
					$item = $term_title;
				}

				array_push( $list, $item );

			}

			echo implode( $separator, $list );

		}

	}

	/**
	 * Display assignee.
	 *
	 * This function is used to display an assignee by display name.
	 *
	 * @since  3.1.3
	 *
	 * @param  string $field ID of the field to display
	 * @param  integer $post_id ID of the current post
	 *
	 * @return void
	 */
	function wpas_show_assignee_column( $field, $post_id ) {

		$assignee = (int) get_post_meta( $post_id, '_wpas_assignee', true );
		$agent    = get_user_by( 'id', $assignee );
		$link     = add_query_arg( array( 'post_type' => 'ticket', 'assignee' => $assignee ), admin_url( 'edit.php' ) );

		if ( is_object( $agent ) && is_a( $agent, 'WP_User' ) ) {
			echo "<a href='$link'>{$agent->data->display_name}</a>";
		}

	}

	/**
	 * Display secondary assignee.
	 *
	 * This function is used to display an assignee by display name.
	 *
	 * @since  3.6.0
	 *
	 * @param  string $field ID of the field to display
	 * @param  integer $post_id ID of the current post
	 *
	 * @return void
	 */
	function wpas_show_secondary_assignee_column( $field, $post_id ) {

		$assignee = (int) get_post_meta( $post_id, '_wpas_secondary_assignee', true );
		$agent    = get_user_by( 'id', $assignee );
		$link     = add_query_arg( array( 'post_type' => 'ticket', 'secondary_assignee' => $assignee ), admin_url( 'edit.php' ) );

		if ( is_object( $agent ) && is_a( $agent, 'WP_User' ) ) {
			echo "<a href='$link'>{$agent->data->display_name}</a>";
		}

	}
	
	/**
	 * Display tertiary assignee.
	 *
	 * This function is used to display an assignee by display name.
	 *
	 * @since  3.6.0
	 *
	 * @param  string $field ID of the field to display
	 * @param  integer $post_id ID of the current post
	 *
	 * @return void
	 */
	function wpas_show_tertiary_assignee_column( $field, $post_id ) {

		$assignee = (int) get_post_meta( $post_id, '_wpas_tertiary_assignee', true );
		$agent    = get_user_by( 'id', $assignee );
		$link     = add_query_arg( array( 'post_type' => 'ticket', 'tertiary_assignee' => $assignee ), admin_url( 'edit.php' ) );

		if ( is_object( $agent ) && is_a( $agent, 'WP_User' ) ) {
			echo "<a href='$link'>{$agent->data->display_name}</a>";
		}

	}
	
	/**
	 * Display 3rd party information in ticket list.
	 *
	 * This function is used to display both the name and email.
	 * uused for 3rd party #1
	 *
	 * @since  3.6.0
	 *
	 * @param  string $field ID of the field to display
	 * @param  integer $post_id ID of the current post
	 *
	 * @return void
	 */
	function wpas_show_3rd_party01_column( $field, $post_id ) {
		$third_party_name01  = get_post_meta( $post_id, '_wpas_first_addl_interested_party_name', true );
		$third_party_email01 = get_post_meta( $post_id, '_wpas_first_addl_interested_party_email', true );
		
		$fullouput = '';
		If ( ! empty($third_party_name01) ) {
			$fullouput .= $third_party_name01 ;
		}
		If ( ! empty($third_party_email01) ) {
			If ( empty($fullouput) ) {
				$fullouput .= $third_party_email01 ;
			}
			
			If ( ! empty($fullouput) ) {
				$fullouput .= '<br />' . $third_party_email01 ;
			}
		}

		if ( ! empty($fullouput) ){
			echo $fullouput;
		}
	}

		/**
	 * Display 3rd party information in ticket list.
	 *
	 * This function is used to display both the name and email.
	 * uused for 3rd party #2
	 *
	 * @since  3.6.0
	 *
	 * @param  string $field ID of the field to display
	 * @param  integer $post_id ID of the current post
	 *
	 * @return void
	 */
	function wpas_show_3rd_party02_column( $field, $post_id ) {
		$third_party_name02  = get_post_meta( $post_id, '_wpas_second_addl_interested_party_name', true );
		$third_party_email02 = get_post_meta( $post_id, '_wpas_second_addl_interested_party_email', true );
		
		$fullouput = '';
		If ( ! empty($third_party_name02) ) {
			$fullouput .= $third_party_name02 ;
		}
		If ( ! empty($third_party_email02) ) {
			If ( empty($fullouput) ) {
				$fullouput .= $third_party_email02 ;
			}
			
			If ( ! empty($fullouput) ) {
				$fullouput .= '<br />' . $third_party_email02 ;
			}
		}

		if ( ! empty($fullouput) ){
			echo $fullouput;
		}
	}
	
	/***
	 * Display integer as hh:mm:ss
	 *
	 * @since 3.3.5
	 *
	 * @param $field
	 *
	 * @param $post_id
	 */
	function wpas_cf_display_time_hhmm( $field, $post_id ) {

		$minutes = (int) get_post_meta( $post_id, '_wpas_' . $field, true );

		if ( ! empty( $minutes ) ) {
			echo sprintf( "%02d:%02d", floor( $minutes / 60 ), ( $minutes ) % 60 );
		}

	}

	/**
	 * Display time adjustment column
	 *
	 * @since 3.3.5
	 *
	 * @param $field
	 *
	 * @param $post_id
	 */
	function wpas_cf_display_time_adjustment_column( $field, $post_id ) {

		$minutes = (int) get_post_meta( $post_id, '_wpas_ttl_adjustments_to_time_spent_on_ticket', true );
		$minutes = sprintf( "%02d:%02d", floor( $minutes / 60 ), ( $minutes ) % 60 );

		$adjustment_operator = get_post_meta( $post_id, '_wpas_time_adjustments_pos_or_neg', true );

		if ( '+' === $adjustment_operator ) {

			echo "<span style='color: #6ddb32;'>$adjustment_operator</span> <span>$minutes</span>";

		} elseif ( '-' === $adjustment_operator ) {

			echo "<span style='color: #dd3333;'>$adjustment_operator</span> (<span style='color: #dd3333;'>$minutes</span>)";

		}

	}

	/**
	 * Display the post status.
	 *
	 * Gets the ticket status and formats it according to the plugin settings.
	 *
	 * @since  3.0.0
	 *
	 * @param string $name Field / column name. This parameter is important as it is automatically passed by some
	 *                          filters
	 * @param  integer $post_id ID of the post being processed
	 *
	 * @return string           Formatted ticket status
	 */
	function wpas_cf_display_status( $name, $post_id ) {

		global $pagenow;

		$status = wpas_get_ticket_status( $post_id );

		$post          = get_post( $post_id );
		$post_status   = $post->post_status;
		$custom_status = wpas_get_post_status();

		if ( 'closed' === $status && ( 'post-new.php' == $pagenow || 'post.php' == $pagenow || 'edit.php' == $pagenow || ( ! is_admin() && 'index.php' === $pagenow ) ) ) {
			$label = __( 'Closed', 'awesome-support' );
			$color = wpas_get_option( "color_$status", '#dd3333' );
			$tag   = "<span class='wpas-label wpas-label-$name' style='background-color:$color;'>$label</span>";

			if ( 'edit.php' == $pagenow && array_key_exists( $post_status, $custom_status ) ) {
				$tag .= '<br/>' . $custom_status[ $post_status ];
			}

		} else {

			$post          = get_post( $post_id );
			$post_status   = $post->post_status;
			$custom_status = wpas_get_post_status();

			if ( ! array_key_exists( $post_status, $custom_status ) ) {
				$label = __( 'Open', 'awesome-support' );
				$color = wpas_get_option( "color_$status", '#169baa' );
				$tag   = "<span class='wpas-label wpas-label-$name' style='background-color:$color;'>$label</span>";
			} else {
				$defaults = array(
					'queued'     => '#1e73be',
					'processing' => '#a01497',
					'hold'       => '#b56629',
				);
				$label    = $custom_status[ $post_status ];
				$color    = wpas_get_option( "color_$post_status", false );

				if ( false === $color ) {
					if ( isset( $defaults[ $post_status ] ) ) {
						$color = $defaults[ $post_status ];
					} else {
						$color = '#169baa';
					}
				}

				$tag = "<span class='wpas-label wpas-label-$name' style='background-color:$color;'>$label</span>";
			}
		}

		echo $tag;

	}

	/**
	 * Display the ticket priority.
	 *
	 * Gets the ticket priority and formats it according to the plugin settings.
	 *
	 * @since  3.3.4
	 *
	 * @param string $name Field / column name. This parameter is important as it is automatically passed by some
	 *                          filters
	 * @param  integer $post_id ID of the post being processed
	 *
	 * @return string           Formatted ticket priority
	 */
	function wpas_cf_display_priority( $name, $post_id ) {

		global $pagenow;

		$terms = array();

		if ( ! $terms = get_the_terms( $post_id, $name ) ) {
			return;
		}

		$term = array_shift( $terms ); // Will get first term, and remove it from $terms array

		$label = __( $term->name, 'awesome-support' );
		$color = get_term_meta( $term->term_id, 'color', true );
		$tag   = "<span class='wpas-label wpas-label-$name' style='background-color:$color;'>$label</span>";

		echo $tag;

	}

	/**
	 * Get the notification wrapper markup
	 *
	 * @since 3.2
	 *
	 * @param string $type Type of notification. Defines the wrapper class to use
	 * @param string $message Notification message
	 *
	 * @return string
	 */
	function wpas_get_notification_markup( $type = 'info', $message = '' ) {

		if ( empty( $message ) ) {
			return '';
		}

		$classes = apply_filters( 'wpas_notification_classes', array(
			'success' => 'wpas-alert wpas-alert-success',
			'failure' => 'wpas-alert wpas-alert-danger',
			'info'    => 'wpas-alert wpas-alert-info',
		) );

		if ( ! array_key_exists( $type, $classes ) ) {
			$type = 'info';
		}

		$markup = apply_filters( 'wpas_notification_wrapper', '<div class="%s">%s</div>' ); // Keep this filter for backwards compatibility
		$markup = apply_filters( 'wpas_notification_markup', sprintf( $markup, $classes[ $type ], $message ), $type );

		return $markup;

	}

	/**
	 * Get pagination link
	 *
	 * This is used for pagination throughout Awesome Support.
	 * It is used for paginating ticket replies as well as tickets lists.
	 *
	 * @since 3.2
	 *
	 * @param string $direction Direction of the link (prev or next)
	 * @param int $posts Total number of pages
	 *
	 * @return string Link to the prev/next page
	 */
	function wpas_pagination_link( $direction = 'next', $posts = 0 ) {

		global $post;

		if ( ! isset( $post ) ) {
			return '';
		}

		$current_page   = isset( $_GET[ 'as-page' ] ) ? filter_input( INPUT_GET, 'as-page', FILTER_SANITIZE_NUMBER_INT ) : 1;
		$posts_per_page = (int) wpas_get_option( 'replies_per_page', 10 );
		$link           = '';

		switch ( $direction ) {

			case 'prev':

				if ( $current_page > 1 ) {
					$page = $current_page - 1;
					$link = get_permalink( $post->ID ) . '?as-page=' . $page;
				}

				break;

			case 'next':

				if ( 0 !== $posts && 0 !== $posts_per_page && $current_page < ceil( $posts / $posts_per_page ) ) {
					$page = $current_page + 1;
					$link = get_permalink( $post->ID ) . '?as-page=' . $page;
				}

				break;

		}

		return empty( $link ) ? $link : esc_url( $link );

	}

	/**
	 * Get previous page link
	 *
	 * @since 3.2
	 *
	 * @param string $label Link anchor
	 * @param bool|true $echo Whether to echo the link or just return it
	 *
	 * @return string
	 */
	function wpas_prev_page_link( $label = '', $echo = true ) {

		if ( empty( $label ) ) {
			$label = '< ' . __( 'Previous Page', 'awesome-support' );
		}

		$link = wpas_pagination_link( 'prev' );

		if ( ! empty( $link ) ) {
			$link = "<a href='$link'>$label</a>";
		}

		if ( true === $echo ) {
			echo $link;
		} else {
			return $link;
		}

		return $link;

	}

	/**
	 * Get next page link
	 *
	 * @since 3.2
	 *
	 * @param string $label Link anchor
	 * @param int $posts Total number of posts
	 * @param bool|true $echo Whether to echo the link or just return it
	 *
	 * @return string
	 */
	function wpas_next_page_link( $label = '', $posts = 0, $echo = true ) {

		if ( empty( $label ) ) {
			$label = __( 'Next Page', 'awesome-support' ) . ' >';
		}

		$link = wpas_pagination_link( 'next', $posts );

		if ( ! empty( $link ) ) {
			$link = "<a href='$link'>$label</a>";
		}

		if ( true === $echo ) {
			echo $link;
		} else {
			return $link;
		}

		return $link;

	}

	add_filter( 'template_include', 'wpas_template_include', 10, 1 );
	/**
	 * Change ticket template.
	 *
	 * By default WordPress uses the single.php template
	 * to display the post type single page as a custom one doesn't exist.
	 * However we don't want all the meta that are usually displayed on a single.php
	 * template. For that reason we switch to the page.php template that usually
	 * doesn't contain all the post metas and author bio.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $template Path to template
	 *
	 * @return string           Path to (possibly) new template
	 */
	function wpas_template_include( $template ) {

		if ( ! is_singular( 'ticket' ) ) {
			return $template;
		}

		$filename      = explode( '/', $template );
		$template_name = $filename[ count( $filename ) - 1 ];

		/* Don't change the template if it's already a custom one */
		if ( 'single-ticket.php' === $template_name ) {
			return $template;
		}

		unset( $filename[ count( $filename ) - 1 ] ); // Remove the template name
		$filename = implode( '/', $filename );
		$filename = $filename . '/page.php';

		if ( file_exists( $filename ) ) {
			return $filename;
		} else {
			return $template;
		}

	}

	add_action( 'wpas_after_registration_fields', 'wpas_terms_and_conditions_checkbox', 10, 3 );
	/**
	 * Add the terms and conditions checkbox.
	 *
	 * Adds a checkbox to the registration form if there are
	 * terms and conditions set in the plugin settings.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	function wpas_terms_and_conditions_checkbox() {

		$terms = wpas_get_option( 'terms_conditions', '' );

		if ( empty( $terms ) ) {
			return;
		}

		$terms = new WPAS_Custom_Field( 'terms', array(
			'name' => 'terms',
			'args' => array(
				'required'   => true,
				'field_type' => 'checkbox',
				'sanitize'   => 'sanitize_text_field',
				'options'    => array( '1' => sprintf( __( 'I accept the %sterms and conditions%s', 'awesome-support' ), '<a href="#wpas-modalterms" class="wpas-modal-trigger">', '</a>' ) ),
			),
		) );

		echo $terms->get_output();

	}

	add_action( 'wpas_after_template', 'wpas_terms_and_conditions_modal', 10, 3 );
	/**
	 * Load terms and conditions.
	 *
	 * Load the terms and conditions if any and if the user
	 * is on the submission page.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $name Template name
	 *
	 * @return boolean           True if the modal is loaded, false otherwise
	 */
	function wpas_terms_and_conditions_modal( $name ) {

		if ( 'registration' !== $name ) {
			return false;
		}

		$terms = wpas_get_option( 'terms_conditions', '' );

		if ( empty( $terms ) ) {
			return false;
		}

		echo '<div style="display: none;"><div id="wpas-modalterms">' . wpautop( wp_kses_post( $terms ) ) . '</div></div>';

		return true;

	}
	
	add_action( 'wpas_after_registration_fields', 'wpas_gdpr_checkboxes', 10, 3 );
	/**
	 * Add the checkboxes for GDPR notices
	 *
	 * Adds one or more checkboxes to the registration form if there are
	 * GDPR options set in the plugin settings.
	 *
	 * @since  4.4.0
	 * @return void
	 */
	function wpas_gdpr_checkboxes() {

		$gdpr_short_desc_01 = wpas_get_option( 'gdpr_notice_short_desc_01', '' );
		$gdpr_long_desc_01 = wpas_get_option( 'gdpr_notice_long_desc_01', '' );

		if ( ! empty( $gdpr_short_desc_01 ) || ! empty( $gdpr_short_desc_01 ) ) {

			$gdpr01 = new WPAS_Custom_Field( 'gdpr01', array(
				'name' => 'gdpr01',
				'args' => array(
					'required'   => true,
					'field_type' => 'checkbox',
					'sanitize'   => 'sanitize_text_field',
					'options'    => array( '1' => $gdpr_short_desc_01 ),
					'desc'		 => $gdpr_long_desc_01,
				),
			) );

			echo $gdpr01->get_output();
		}
		
		$gdpr_short_desc_02 = wpas_get_option( 'gdpr_notice_short_desc_02', '' );
		$gdpr_long_desc_02 = wpas_get_option( 'gdpr_notice_long_desc_02', '' );

		if ( ! empty( $gdpr_short_desc_02 ) || ! empty( $gdpr_short_desc_02 ) ) {

			$gdpr02 = new WPAS_Custom_Field( 'gdpr02', array(
				'name' => 'gdpr02',
				'args' => array(
					'required'   => true,
					'field_type' => 'checkbox',
					'sanitize'   => 'sanitize_text_field',
					'options'    => array( '1' => $gdpr_short_desc_02 ),
					'desc'		 => $gdpr_long_desc_02,
				),
			) );

			echo $gdpr02->get_output();
		}	

		$gdpr_short_desc_03 = wpas_get_option( 'gdpr_notice_short_desc_03', '' );
		$gdpr_long_desc_03 = wpas_get_option( 'gdpr_notice_long_desc_03', '' );

		if ( ! empty( $gdpr_short_desc_03 ) || ! empty( $gdpr_short_desc_03 ) ) {

			$gdpr03 = new WPAS_Custom_Field( 'gdpr03', array(
				'name' => 'gdpr03',
				'args' => array(
					'required'   => true,
					'field_type' => 'checkbox',
					'sanitize'   => 'sanitize_text_field',
					'options'    => array( '1' => $gdpr_short_desc_03 ),
					'desc'		 => $gdpr_long_desc_03,
				),
			) );

			echo $gdpr03->get_output();
		}		

	}	

	add_filter( 'wpas_cf_field_markup_readonly', 'wpas_cf_field_markup_time_tracking_readonly', 10, 2 );
	/**
	 * Check AS Settings to see if agents are allowed to manually edit
	 * time tracking fields. If not set cf readonly setting.
	 *
	 * Filter
	 *
	 * @param $disabled
	 * @param $field
	 *
	 * @return bool
	 */
	function wpas_cf_field_markup_time_tracking_readonly( $readonly, $field ) {

		if ( $field[ 'name' ] === 'ttl_calculated_time_spent_on_ticket'
		     || $field[ 'name' ] === 'ttl_adjustments_to_time_spent_on_ticket'
		     || $field[ 'name' ] === 'time_adjustments_pos_or_neg'
		     || $field[ 'name' ] === 'time_notes'
		) {

			if ( false === boolval( wpas_get_option( 'allow_agents_to_enter_time', $readonly ) ) ) {
				$readonly = true;

				// Disable tiny mce editor
				add_filter( 'tiny_mce_before_init', 'wpas_cf_field_time_tracking_disable_tiny_mce' );

			}

		}

		return $readonly;

	}

	/**
	 * Disable tiny mce editor for Time Tracking Notes when readonly
	 *
	 * @since 3.3.5
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	function wpas_cf_field_time_tracking_disable_tiny_mce( $args ) {

		$args[ 'readonly' ] = true;

		return $args;

	}
	
	/**
	 * Returns the URL that the user should be redirected to when the logout button is pushed
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	function wpas_get_logout_redirect() {

		if ( ! empty( wpas_get_option( 'logout_redirect_fe', '') ) ) {
			return wp_logout_url( wpas_get_option( 'logout_redirect_fe', '') );
		} else {
			return wp_logout_url();
		}
		
	}
	