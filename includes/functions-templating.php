<?php
/**
 * Templating Functions.
 *
 * This file contains all the templating functions. It aims at making it easy
 * for developers to gather ticket details and insert them in a custom template.
 *
 * @package   Awesome_Support
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */

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
 * @param  string $content Post content
 * @return string          Ticket single
 */
function wpas_single_ticket( $content ) {

	global $post;

	$slug = 'ticket';

	/* Don't touch the admin */
	if ( is_admin() ) {
		return $content;
	}

	/* Only apply this on the ticket single. */
	if ( $slug !== $post->post_type ) {
		return $content;
	}

	/* Only apply this on the main query. */
	if( ! is_main_query() ) {
		return $content;
	}

	/* Only apply this if it's inside of a loop. */
	if( ! in_the_loop() ) {
		return $content;
	}

	/* Remove the filter to avoid infinite loops. */
	remove_filter( 'the_content', 'wpas_single_ticket' );

	/* Check if the current user can view the ticket */
	if ( ! wpas_can_view_ticket( $post->ID ) ) {

		if ( is_user_logged_in() ) {
			return wpas_notification( false, 13, false );
		} else {

			$output = '';
			$output .= wpas_notification( false, 13, false );

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
	$template      = $template[$count-1];

	/* Don't apply the modifications on a custom template */
	if ( "single-$slug.php" === $template ) {
		return $content;
	}

	/* Get the ticket content */
	ob_start();

	/**
	 * Display possible messages to the visitor.
	 */
	if ( isset( $_GET['message'] ) ) {
		wpas_notification( false, $_GET['message'] );
	}

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
 * @param  array  $args Pass variables to the template
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
			WPAS_TEMPLATE_PATH . $filename
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
		$template =  WPAS_PATH . "themes/$theme/css/style.css";
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

	$theme = wpas_get_theme();

	$template = locate_template(
		array(
			WPAS_TEMPLATE_PATH . 'style.css',
			WPAS_TEMPLATE_PATH . 'css/style.css',
		)
	);

	if ( ! $template ) {
		$template =  WPAS_PATH . "themes/$theme/css/style.css";
	}

	/* Remove the root path and replace backslashes by slashes */
	$truncate = str_replace('\\', '/', str_replace( untrailingslashit( ABSPATH ), '', $template ) );

	/* Make sure the truncated string doesn't start with a slash because we trailing slash the home URL) */
	if ( '/' === substr( $truncate, 0, 1 ) ) {
		$truncate = substr( $truncate, 1 );
	}

	/* Build the final URL to the resource */
	$uri = trailingslashit( home_url() ) . $truncate;

	return apply_filters( 'wpas_get_theme_stylesheet_uri', $uri ); 

}

/**
 * Get the ticket header.
 *
 * @since  3.0.0
 * @param  array  $args Additional parameters
 * @return void
 */
function wpas_ticket_header( $args = array() ) {

	global $wpas_cf, $post;

	$default = array(
		'container'       => '',
		'container_id'    => '',
		'container_class' => '',
		'table_id'        => "header-ticket-$post->ID",
		'table_class'     => 'wpas-table wpas-ticket-details-header',
	);

	extract( shortcode_atts( $default, $args ) );

	$custom_fields = $wpas_cf->get_custom_fields();

	$columns = array(
		'id'     => __( 'ID', 'wpas' ),
		'status' => __( 'Status', 'wpas' ),
		'date'   => __( 'Date', 'wpas' )
	);

	$columns_callbacks = array(
		'id'     => 'id',
		'status' => 'wpas_cf_display_status',
		'date'   => 'date',
	);

	foreach ( $custom_fields as $field ) {

		/* Don't display core fields */
		if ( true === $field['args']['core'] ) {
			continue;
		}

		/* Don't display fields that aren't specifically designed to */
		if ( true === $field['args']['show_column'] ) {
			$columns[$field['name']]           = !empty( $field['args']['title'] ) ? sanitize_text_field( $field['args']['title'] ) : wpas_get_title_from_id( $field['name'] );
			$columns_callbacks[$field['name']] = ( 'taxonomy' === $field['args']['callback'] && true === $field['args']['taxo_std'] ) ? 'taxonomy' : $field['args']['column_callback'];
		}

	}

	$columns           = apply_filters( 'wpas_tickets_details_columns', $columns );
	$columns_callbacks = apply_filters( 'wpas_tickets_details_columns_callbacks', $columns_callbacks );
	?>

	<?php if ( !empty( $container ) ): ?><<?php echo $container; ?>><?php endif; ?>

		<table id="<?php echo $table_id; ?>" class="<?php echo $table_class; ?>">
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

	<?php if ( !empty( $container ) ): ?></<?php echo $container; ?>><?php endif; ?>

	<?php

}

/**
 * Display the reply form.
 *
 * @since  3.0.0
 * @param  array  $args Additional arguments
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

	extract( shortcode_atts( $defaults, $args ) );

	/**
	 * Filter the form class.
	 *
	 * This can be useful for addons doing something on the reply form,
	 * like adding an upload feature for instance.
	 *
	 * @since  3.0.0
	 * @var    string
	 */
	$form_class = apply_filters( 'wpas_frontend_reply_form_class', $form_class );

	/**
	 * wpas_ticket_details_reply_form_before hook
	 *
	 * @since  3.0.0
	 */
	do_action( 'wpas_ticket_details_reply_form_before' );

	if( 'closed' === $status ):

		wpas_notification( 'info', sprintf( __( 'The ticket has been closed. If you feel that your issue has not been solved yet or something new came up in relation to this ticket, <a href="%s">you can re-open it by clicking this link</a>.', 'wpas' ), wpas_get_reopen_url() ) );

	/**
	 * Check if the ticket is currently open and if the current user
	 * is allowed to post a reply.
	 */
	elseif( 'open' === $status && true === wpas_can_reply_ticket() ): ?>

		<form id="<?php echo $form_id; ?>" class="<?php echo $form_class; ?>" method="post" action="<?php echo get_permalink( $post_id ); ?>" enctype="multipart/form-data">

			<?php
			/**
			 * wpas_ticket_details_reply_textarea_before hook
			 *
			 * @since  3.0.0
			 */
			do_action( 'wpas_ticket_details_reply_textarea_before' ); ?>

			<<?php echo $container; ?> id="<?php echo $container_id; ?>" class="<?php echo $container_class; ?>">
				<?php echo $textarea_before;

					/**
					 * Load the visual editor if enabled
					 */
					if( true === boolval( wpas_get_option( 'frontend_wysiwyg_editor' ) ) ) {

						$editor_defaults = apply_filters( 'wpas_ticket_editor_args', array(
							'media_buttons' => false,
							'textarea_name' => 'wpas_user_reply',
							'textarea_rows' => 10,
							'tabindex'      => 2,
							'editor_class'  => wpas_get_field_class( 'wpas_reply', $textarea_class, false ),
							'quicktags'     => false,
							'tinymce'       => array(
								'toolbar1' => 'bold,italic,underline,strikethrough,hr,|,bullist,numlist,|,link,unlink',
								'toolbar2' => ''
							),
						) );

						wp_editor( '', 'wpas-reply-wysiwyg', apply_filters( 'wpas_reply_wysiwyg_args', $editor_defaults ) );

					}

					/**
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
						<textarea class="form-control" rows="10" name="wpas_user_reply" rows="6" id="wpas-reply-textarea" placeholder="<?php _e( 'Type your reply here.', 'wpas' ); ?>" <?php if ( false === $can_submit_empty ): ?>required="required"<?php endif; ?>></textarea>
					<?php }
				
				echo $textarea_after; ?>
			</<?php echo $container; ?>>

			<?php
			/**
			 * wpas_ticket_details_reply_textarea_after hook
			 *
			 * @since  3.0.0
			 */
			do_action( 'wpas_ticket_details_reply_textarea_after' );

			if ( current_user_can( 'close_ticket' ) ): ?>

				<div class="checkbox">
					<label for="close_ticket" data-toggle="tooltip" data-placement="right" title="" data-original-title="<?php _e( 'No reply is required to close', 'wpas' ); ?>">
						<input type="checkbox" name="wpas_close_ticket" id="close_ticket" value="true"> <?php _e( 'Close this ticket', 'wpas' ); ?>
					</label>
				</div>

			<?php endif;
			
			/**
			 * wpas_ticket_details_reply_close_checkbox_after hook
			 *
			 * @since  3.0.0
			 */
			do_action( 'wpas_ticket_details_reply_close_checkbox_after' ); ?>

			<input type="hidden" name="ticket_id" value="<?php echo $post_id; ?>" />

			<?php
			wp_nonce_field( 'send_reply', 'client_reply', false, true );
			wpas_make_button( __( 'Reply', 'wpas' ), array( 'name' => 'wpas-submit', 'onsubmit' => __( 'Please Wait...', 'wpas' ) ) );

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
	elseif( 'open' === $status && false === wpas_can_reply_ticket() ):
		wpas_notification( 'info', sprintf( __( 'To reply to this ticket, please <a href="%s">go to your admin panel</a>.', 'wpas' ), add_query_arg( array( 'post' => $post_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) ) );
	else:
		wpas_notification( 'info', __( 'You are not allowed to reply to this ticket.', 'wpas' ) );
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
 * @param  integer $ticket_id ID of the ticket to re-open
 * @return string             The URL to trigger re-opening the ticket
 */
function wpas_get_reopen_url( $ticket_id = null ) {

	global $wp_query;

	if ( is_null( $ticket_id ) ) {
		$ticket_id = intval( $wp_query->post->ID );
	}

	$url = add_query_arg( array( 'action' => 'reopen', 'ticket_id' => $ticket_id ), get_permalink( $ticket_id ) );

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
 * Shows the message field.
 *
 * The function echoes the textarea where the user
 * may input the ticket description. The field can be
 * either a textarea or a WYSIWYG depending on the plugin settings.
 * The WYSIWYG editor uses TinyMCE with a minimal configuration.
 *
 * @since  3.0.0
 * @param  array  $editor_args Arguments used for TinyMCE
 * @return void
 */
function wpas_get_message_textarea( $editor_args = array() ) {

	/**
	 * Check if the description field should use the WYSIWYG editor
	 * 
	 * @var string
	 */
	$textarea_class = ( true === ( $wysiwyg = boolval( wpas_get_option( 'frontend_wysiwyg_editor' ) ) ) ) ? 'wpas-wysiwyg' : 'wpas-textarea';

	if ( true === $wysiwyg ) {

		$editor_defaults = apply_filters( 'wpas_ticket_editor_args', array(
			'media_buttons' => false,
			'textarea_name' => 'wpas_message',
			'textarea_rows' => 10,
			'tabindex'      => 2,
			'editor_class'  => wpas_get_field_class( 'wpas_message', $textarea_class, false ),
			'quicktags'     => false,
			'tinymce'       => array(
				'toolbar1' => 'bold,italic,underline,strikethrough,hr,|,bullist,numlist,|,link,unlink',
				'toolbar2' => ''
			),
		) );

		?><div class="wpas-submit-ticket-wysiwyg"><?php
			wp_editor( wpas_get_field_value( 'wpas_message' ), 'wpas-ticket-message', apply_filters( 'wpas_reply_wysiwyg_args', $editor_defaults ) );
		?></div><?php

	} else {

		/**
		 * Define if the body can be submitted empty or not.
		 *
		 * @since  3.0.0
		 * @var boolean
		 */
		$can_submit_empty = apply_filters( 'wpas_can_message_be_empty', false );
		?>
		<div class="wpas-submit-ticket-wysiwyg">
			<textarea <?php wpas_get_field_class( 'wpas_message', $textarea_class ); ?> id="wpas-ticket-message" name="wpas_message" placeholder="<?php echo apply_filters( 'wpas_form_field_placeholder_wpas_message', __( 'Describe your problem as accurately as possible', 'wpas' ) ); ?>" rows="10" <?php if ( false === $can_submit_empty ): ?>required="required"<?php endif; ?>><?php echo wpas_get_field_value( 'wpas_message' ); ?></textarea>
		</div>
	<?php }

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

	global $wpas_cf;

	$custom_fields = $wpas_cf->get_custom_fields();

	$columns = array(
		'status' => array( 'title' => __( 'Status', 'wpas' ), 'callback' => 'wpas_cf_display_status' ),
		'title'  => array( 'title' => __( 'Title', 'wpas' ), 'callback' => 'title' ),
		'date'   => array( 'title' => __( 'Date', 'wpas' ), 'callback' => 'date' ),
	);

	foreach ( $custom_fields as $field ) {

		/* Don't display core fields */
		if ( true === $field['args']['core'] ) {
			continue;
		}

		/* Don't display fields that aren't specifically designed to */
		if ( true === $field['args']['show_column'] ) {
			$column_title            = !empty( $field['args']['title'] ) ? sanitize_text_field( $field['args']['title'] ) : wpas_get_title_from_id( $field['name'] );
			$column_callback         = ( 'taxonomy' === $field['args']['callback'] && true === $field['args']['taxo_std'] ) ? 'taxonomy' : $field['args']['column_callback'];
			$columns[$field['name']] = array( 'title' => $column_title, 'callback' => $column_callback );
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
 * @param  string $column_id ID of the current column
 * @param  array  $column    Columns data
 * @return void
 */
function wpas_get_tickets_list_column_content( $column_id, $column ) {

	$callback = $column['callback'];

	switch( $callback ) {

		case 'id':
			echo '#' . get_the_ID();
		break;

		case 'status':
			echo wpas_get_ticket_status( get_the_ID() );
		break;

		case 'title':
			?><a href="<?php echo get_permalink( get_the_ID() ); ?>"><?php the_title(); ?></a><?php
		break;

		case 'date':
			$offset = wpas_get_offset_html5();
			?><time datetime="<?php echo get_the_date( 'Y-m-d\TH:i:s' ) . $offset ?>"><?php echo get_the_date( get_option( 'date_format' ) ) . ' ' . get_the_date( get_option( 'time_format' ) ); ?></time><?php
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

			if ( function_exists( $callback ) ) {
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
	$hours   = $offset[0];
	$minutes = isset( $offset[1] ) ? $offset[1] : '00';
	$sign    = ( '-' === substr( $hours, 0, 1 ) ) ? '-' : '+';

	/* Remove the sign from the hours */
	if (  '-' === substr( $hours, 0, 1 ) ) {
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
 * @param  string $field    ID of the field to display
 * @param  integer $post_id ID of the current post
 * @return void
 */
function wpas_show_taxonomy_column( $field, $post_id, $separator = ', ' ) {

	$terms = get_the_terms( $post_id, $field );
	$list  = array();

	if ( ! is_array( $terms ) ) {
		echo '';
	} else {

		foreach ( $terms as $term ) {

			if ( is_admin() ) {
				$get         = (array) $_GET;
				$get[$field] = $term->slug;
				$url         = add_query_arg( $get, admin_url( 'edit.php' ) );
				$item        = "<a href='$url'>{$term->name}</a>";
			} else {
				$item = $term->name;
			}

			array_push( $list, $item );

		}

		echo implode( $separator, $list );

	}

}