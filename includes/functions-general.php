<?php
/**
 * Get plugin option.
 *
 * @param  string      $option  Option to look for
 * @param  bool|string $default Value to return if the requested option doesn't exist
 *
 * @return mixed           Value for the requested option
 * @since  1.0.0
 */
function wpas_get_option( $option, $default = false ) {

	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );

	/* Return option value if exists */
	$value = isset( $options[ $option ] ) ? $options[ $option ] : $default;

	return apply_filters( 'wpas_option_' . $option, $value );

}

/**
 * Add a security nonce.
 *
 * The function adds a security nonce to URLs
 * with a trigger for plugin custom action.
 *
 * @param  string $url URL to nonce
 *
 * @return string)      Nonced URL
 * @since  3.0.0
 */
function wpas_nonce_url( $url ) {
	return add_query_arg( array( 'wpas-nonce' => wp_create_nonce( 'wpas_custom_action' ) ), $url );
}

/**
 * Check a custom action nonce.
 *
 * @since  3.1.5
 * @param  string $nonce  Nonce to be checked
 * @return boolean        Nonce validity
 */
function wpas_check_nonce( $nonce ) {
	return wp_verify_nonce( $nonce, 'wpas_custom_action' );
}

/**
 * Add custom action and nonce to URL.
 *
 * The function adds a custom action trigger using the wpas-do
 * URL parameter and adds a security nonce for plugin custom actions.
 *
 * @param  string $url    URL to customize
 * @param  string $action Custom action to add
 *
 * @return string         Customized URL
 * @since  3.0.0
 */
function wpas_url_add_custom_action( $url, $action ) {
	return wpas_nonce_url( add_query_arg( array( 'wpas-do' => sanitize_text_field( $action ) ), $url ) );
}

function wpas_get_open_ticket_url( $ticket_id, $action = 'open' ) {

	$remove = array( 'post', 'message' );
	$args   = $_GET;

	foreach ( $remove as $key ) {

		if ( isset( $args[$key] ) ) {
			unset( $args[$key] );
		}

	}

	$args['post'] = intval( $ticket_id );

	return wpas_url_add_custom_action( add_query_arg( $args, admin_url( 'post.php' ) ), $action );
}

function wpas_get_close_ticket_url( $ticket_id ) {
	return wpas_get_open_ticket_url( $ticket_id, 'close' );
}

/**
 * Get safe tags for content output.
 * 
 * @return array List of allowed tags
 * @since  3.0.0
 */
function wpas_get_safe_tags() {

	$tags = array(
		'a' => array(
			'href' => array (),
			'title' => array ()),
		'abbr' => array(
			'title' => array ()),
		'acronym' => array(
			'title' => array ()),
		'b' => array(),
		'blockquote' => array(
			'cite' => array ()),
		'cite' => array (),
		'code' => array(),
		'pre' => array(),
		'del' => array(
			'datetime' => array ()),
		'em' => array (), 'i' => array (),
		'q' => array(
			'cite' => array ()),
		'strike' => array(),
		'strong' => array(),
		'h1' => array(),
		'h2' => array(),
		'h3' => array(),
		'h4' => array(),
		'h5' => array(),
		'h6' => array(),
		'p' => array(),
	);

	return apply_filters( 'wpas_get_safe_tags', $tags );

}

/**
 * Is plugin page.
 *
 * Checks if the current page belongs to the plugin or not.
 * This is usually used to decide if a resource must be loaded
 * or not, avoiding loading plugin resources on other pages.
 *
 * @param string $slug Optional page slug to check
 *
 * @return boolean ether or not the current page belongs to the plugin
 * @since  3.0.0
 */
function wpas_is_plugin_page( $slug = '' ) {

	global $post;

	$plugin_post_types  = apply_filters( 'wpas_plugin_post_types', array( 'ticket' ) );
	$plugin_admin_pages = apply_filters( 'wpas_plugin_admin_pages', array( 'wpas-status', 'wpas-addons' ) );

	/* Check for plugin pages in the admin */
	if ( is_admin() ) {

		/* First of all let's check if there is a specific slug given */
		if ( ! empty( $slug ) && in_array( $slug, $plugin_admin_pages ) ) {
			return true;
		}

		/* If the current post if of one of our post types */
		if ( isset( $post ) && isset( $post->post_type ) && in_array( $post->post_type, $plugin_post_types ) ) {
			return true;
		}

		/* If the page we're in relates to one of our post types */
		if ( isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], $plugin_post_types ) ) {
			return true;
		}

		/* If the page belongs to the plugin */
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $plugin_admin_pages ) ) {
			return true;
		}

		/* In none of the previous conditions was true, return false by default. */

		return false;

	} else {

		global $post;

		$pages = array( wpas_get_option( 'ticket_list' ), wpas_get_option( 'ticket_submit' ) );

		if ( is_singular( 'ticket' ) ) {
			return true;
		}

		if ( isset( $post ) && is_object( $post ) && in_array( $post->ID, $pages ) ) {
			return true;
		}

		return false;

	}

}

/**
 * Get field title from ID.
 *
 * Just a stupid function that converts an ID into
 * a nicely formatted title.
 *
 * @since  3.0.0
 * @param  string $id ID to transform
 * @return string     Nicely formatted title
 */
function wpas_get_title_from_id( $id ) {
	return ucwords( str_replace( array( '-', '_' ), ' ', $id ) );
}

function wpas_get_field_title( $field ) {

	if ( !empty( $field['args']['title'] ) ) {
		return sanitize_text_field( $field['args']['title'] );
	} else {
		return wpas_get_title_from_id( $field['name'] );
	}

}

/**
 * Display debugging information.
 *
 * Another stupid function that just displays
 * a piece of data inside a <pre> to make it
 * more easily readable.
 *
 * @since  3.0.0
 * @param  mixed $thing Data to display
 * @return void
 */
function wpas_debug_display( $thing ) {
	echo '<pre>';
	print_r( $thing );
	echo '</pre>';
}

function wpas_make_button( $label = null, $args = array() ) {

	if ( is_null( $label ) ) {
		$label = __( 'Submit', 'wpas' );
	}

	$defaults = array(
		'type'     => 'button',
		'link'     => '',
		'class'    => wpas_get_option( 'buttons_class', 'wpas-btn wpas-btn-default' ),
		'name'     => 'submit',
		'value'    => '',
		'onsubmit' => ''
	);

	$args = wp_parse_args( $args, $defaults );

	extract( shortcode_atts( $defaults, $args ) );

	if ( 'link' === $args['type'] && !empty( $args['link'] ) ) {
		?><a href="<?php echo esc_url( $args['link'] ); ?>" class="<?php echo $args['class']; ?>" <?php if ( !empty( $args['onsubmit'] ) ): echo "data-onsubmit='{$args['onsubmit']}'"; endif; ?>><?php echo $label; ?></a><?php
	} else {
		?><button type="submit" class="<?php echo $args['class']; ?>" name="<?php echo $args['name']; ?>" value="<?php echo $args['value']; ?>" <?php if ( !empty( $args['onsubmit'] ) ): echo "data-onsubmit='{$args['onsubmit']}'"; endif; ?>><?php echo $label; ?></button><?php
	}

}

/**
 * Get the ticket status.
 *
 * The $post_id parameter is optional. If no ID is passed,
 * the function tries to get it from the global $post object.
 *
 * @since  3.0.0
 * @param  mixed $post_id ID of the ticket to check
 * @return string         Current status of the ticket
 */
function wpas_get_ticket_status( $post_id = null ) {

	if ( is_null( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	}

	return get_post_meta( $post_id, '_wpas_status', true );

}

/**
 * Get the ticket state.
 *
 * Gets the ticket status. If the ticket is closed nothing fancy.
 * If not, we return the ticket state instead of the "Open" status.
 *
 * @since  3.1.5
 *
 * @param  integer $post_id Post ID
 *
 * @return string           Ticket status / state
 */
function wpas_get_ticket_status_state( $post_id ) {

	$status = wpas_get_ticket_status( $post_id );

	if ( 'closed' === $status ) {
		$output = __( 'Closed', 'wpas' );
	} else {

		$post          = get_post( $post_id );
		$post_status   = $post->post_status;
		$custom_status = wpas_get_post_status();

		if ( ! array_key_exists( $post_status, $custom_status ) ) {
			$output = __( 'Open', 'wpas' );
		} else {
			$output = $custom_status[ $post_status ];
		}
	}

	return $output;

}

function wpas_get_current_admin_url() {

	global $pagenow;

	$get = $_GET;

	if ( !isset( $get ) || !is_array( $get ) ) {
		$get = array();
	}

	return esc_url( add_query_arg( $get, admin_url( $pagenow ) ) );

}

/**
 * Redirect to another page.
 *
 * The function will redirect to another page by using
 * wp_redirect if headers haven't been sent already. Otherwise
 * it uses a meta refresh tag.
 *
 * @since  3.0.0
 * @param  string  $case     Redirect case used for filtering
 * @param  string  $location URL to redirect to
 * @param  mixed   $post_id  The ID of the post to redirect to (or null if none specified)
 * @return integer           Returns false if location is not provided, true otherwise
 */
function wpas_redirect( $case, $location = null, $post_id = null ) {

	if ( is_null( $location ) ) {
		return false;
	}

	/**
	 * Filter the redirect URL.
	 *
	 * @param  string URL to redirect to
	 * @param  mixed  ID of the post to redirect to or null if none specified
	 */
	$location = apply_filters( "wpas_redirect_$case", $location, $post_id );
	$location = wp_sanitize_redirect( $location );

	if ( !headers_sent() ) {
		wp_redirect( $location, 302 );
	} else {
		echo "<meta http-equiv='refresh' content='0; url=$location'>";
	}

	return true;

}

/**
 * Write log file.
 *
 * Wrapper function for WPAS_Logger. The function
 * will open (or create if needed) a log file
 * and write the $message at the end of it.
 *
 * @since  3.0.2
 * @param  string $handle  The log file handle
 * @param  string $message The message to write
 * @return void
 */
function wpas_write_log( $handle, $message ) {
	$log = new WPAS_Logger( $handle );
	$log->add( $message );
}

/**
 * Show a warning if dependencies aren't loaded.
 *
 * If the dependencies aren't present in the plugin folder
 * we display a warning to the user and explain him how to 
 * fix the issue.
 *
 * @since  3.0.2
 * @return void
 */
function wpas_missing_dependencied() { ?>
	<div class="error">
        <p><?php printf( __( 'Awesome Support dependencies are missing. The plugin can&#39;t be loaded properly. Please run %s before anything else. If you don&#39;t know what this is you should <a href="%s" class="thickbox">install the production version</a> of this plugin instead.', 'wpas' ), '<a href="https://getcomposer.org/doc/00-intro.md#using-composer" target="_blank"><code>composer install</code></a>', esc_url( add_query_arg( array( 'tab' => 'plugin-information', 'plugin' => 'awesome-support', 'TB_iframe' => 'true', 'width' => '772', 'height' => '935' ), admin_url( 'plugin-install.php' ) ) ) ); ?></p>
    </div>
<?php }

/**
 * Wrap element into lis.
 *
 * Takes a string and wraps it into a pair
 * or <li> tags.
 *
 * @since  3.1.3
 * @param  string $entry  The entry to wrap
 * @return string         The wrapped element
 */
function wpas_wrap_li( $entry ) {

	if ( is_array( $entry ) ) {
		$entry = wpas_array_to_ul( $entry );
	}

	$entry = htmlentities( $entry );

	return "<li>$entry</li>";
}

/**
 * Convert array into an unordered list.
 *
 * @since  3.1.3
 * @param  array $array Array to convert
 * @return string       Unordered list
 */
function wpas_array_to_ul( $array ) {
	$wrapped = array_map( 'wpas_wrap_li', $array );
	return '<ul>' . implode( '', $wrapped ) . '</ul>';
}

/**
 * Create dropdown of things.
 *
 * @since  3.1.3
 * @param  array $args     Dropdown settings
 * @param  string $options Dropdown options
 * @return string          Dropdown with custom options
 */
function wpas_dropdown( $args, $options ) {

	$defaults = array(
		'name'          => 'wpas_user',
		'id'            => '',
		'class'         => '',
		'please_select' => false,
		'select2'       => false,
		'disabled'      => false,
	);

	$args = wp_parse_args( $args, $defaults );

	$class = (array) $args['class'];

	if ( true === $args['select2'] ) {
		array_push( $class, 'wpas-select2' );
	}

	/* Start the buffer */
	ob_start(); ?>

	<select name="<?php echo $args['name']; ?>" <?php if ( !empty( $class ) ) echo 'class="' . implode( ' ' , $class ) . '"'; ?> <?php if ( !empty( $id ) ) echo "id='$id'"; ?> <?php if( true === $args['disabled'] ) { echo 'disabled'; } ?>>
		<?php
		if ( $args['please_select'] ) {
			echo '<option value="">' . __( 'Please select', 'wpas' ) . '</option>';
		}

		echo $options;
		?>
	</select>

	<?php
	/* Get the buffer contents */
	$contents = ob_get_contents();

	/* Clean the buffer */
	ob_end_clean();

	return $contents;

}

/**
 * Get a dropdown of the tickets.
 *
 * @since  3.1.3
 * @param  array  $args   Dropdown arguments
 * @param  string $status Specific ticket status to look for
 * @return void
 */
function wpas_tickets_dropdown( $args = array(), $status = '' ) {

	$defaults = array(
		'name'          => 'wpas_tickets',
		'id'            => '',
		'class'         => '',
		'exclude'       => array(),
		'selected'      => '',
		'select2'       => true,
		'please_select' => false
	);

	/* List all tickets */
	$tickets = get_tickets( $status );
	$options = '';

	foreach ( $tickets as $ticket ) {
		$options .= "<option value='$ticket->ID'>$ticket->post_title</option>";
	}

	echo wpas_dropdown( wp_parse_args( $args, $defaults ), $options );

}

add_filter( 'locale','wpas_change_locale', 10, 1 );
/**
 * Change the site's locale.
 *
 * This is used for debugging purpose. This function
 * allows for changing the locale during WordPress
 * initialization. This will only affect the current user.
 *
 * @since  3.1.5
 * @param  string $locale Site locale
 * @return string         Possibly modified locale
 */
function wpas_change_locale( $locale ) {

   $wpas_locale = filter_input( INPUT_GET, 'wpas_lang', FILTER_SANITIZE_STRING );

	if ( ! empty( $wpas_locale ) ) {
		$locale = $wpas_locale;
	}

	return $locale;
}

/**
 * Get plugin settings page URL.
 *
 * @since  3.1.5
 * @param  string $tab Tab ID
 * @return string      URL to the required settings page
 */
function wpas_get_settings_page_url( $tab = '' ) {

	$admin_url  = admin_url( 'edit.php' );
	$query_args = array( 'post_type' => 'ticket', 'page' => 'settings' );

	if ( ! empty( $tab ) ) {
		$query_args['tab'] = sanitize_text_field( $tab );
	}

	return add_query_arg( $query_args, $admin_url );

}

/**
 * Shuffle an associative array.
 *
 * @param array $list The array to shuffle
 *
 * @return array Shuffled array
 *
 * @link  http://php.net/manual/en/function.shuffle.php#99624
 * @since 3.1.10
 */
function shuffle_assoc( $list ) {

	if ( ! is_array( $list ) ) {
		return $list;
	}

	$keys   = array_keys( $list );
	$random = array();

	shuffle( $keys );

	foreach ( $keys as $key ) {
		$random[ $key ] = $list[ $key ];
	}

	return $random;

}

if ( ! function_exists( 'wpas_get_admin_path_from_url' ) ) {
	/**
	 * Get the admin path based on the URL.
	 *
	 * @since 3.1.11
	 * @return string Admin path
	 */
	function wpas_get_admin_path_from_url() {

		$admin_url      = get_admin_url();
		$site_url       = get_bloginfo( 'url' );
		$admin_protocol = substr( $admin_url, 0, 5 );
		$site_protocol  = substr( $site_url, 0, 5 );

		if ( $site_protocol !== $admin_protocol ) {
			if ( 'https' === $admin_protocol ) {
				$site_url = 'https' . substr( $site_url, 4 );
			}
		}

		return str_replace( trailingslashit( $site_url ), ABSPATH, get_admin_url() );

	}
}