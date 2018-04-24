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
 * Update a plugin option
 *
 * @since 3.2.0
 *
 * @param mixed $option The name of the option to update
 * @param mixed $value  The new value for this option
 * @param bool  $add    Whether or not a new key should be added if $option is not found in the options array
 *
 * @return bool
 */
function wpas_update_option( $option, $value, $add = false ) {

	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );

	// Add a new option key if it doesn't yet exist
	if ( ! array_key_exists( $option, $options ) && true === $add ) {
		$options[ $option ] = '';
	}

	if ( ! array_key_exists( $option, $options ) ) {
		return false;
	}

	if ( $value === $options[ $option ] ) {
		return false;
	}

	$options[ $option ] = $value;

	return update_option( 'wpas_options', serialize( $options ) );

}

/**
 * Get link to (re)open a ticket
 *
 * @param int $ticket_id ID of the ticket ot open
 *
 * @return string
 */
function wpas_get_open_ticket_url( $ticket_id ) {

	$remove = array( 'post', 'message' );
	$args   = $_GET;

	foreach ( $remove as $key ) {

		if ( isset( $args[ $key ] ) ) {
			unset( $args[ $key ] );
		}

	}

	$args['post'] = intval( $ticket_id );

	return wpas_do_url( add_query_arg( $args, admin_url( 'post.php' ) ), 'admin_open_ticket', array( 'post' => (int) $ticket_id ) );

}

/**
 * Get link to close a ticket
 *
 * @param int $ticket_id
 *
 * @return string
 */
function wpas_get_close_ticket_url( $ticket_id ) {

	$url = add_query_arg( 'post_type', 'ticket', admin_url( 'edit.php' ) );

	return wpas_do_url( $url, 'admin_close_ticket', array( 'post' => $ticket_id ) );
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

	$ticket_list   = wpas_get_option( 'ticket_list' );
	$ticket_submit = wpas_get_option( 'ticket_submit' );

	/* Make sure these are arrays. Multiple selects were only used since 3.2, in earlier versions those options are strings */
	if( ! is_array( $ticket_list ) ) { $ticket_list = (array) $ticket_list; }
	if( ! is_array( $ticket_submit ) ) { $ticket_submit = (array) $ticket_submit; }

	$plugin_post_types     = apply_filters( 'wpas_plugin_post_types',     array( 'ticket', 'canned-response', 'documentation', 'wpas_unassigned_mail', 'wpas_mailbox_config', 'wpas_inbox_rules', 'faq', 'wpas_gadget', 'as_security_profile', 'ruleset', 'trackedtimes', 'wpas_sla', 'wpas_issue_tracking', 'wpas_company_profile' ) );
	$plugin_admin_pages    = apply_filters( 'wpas_plugin_admin_pages',    array( 'wpas-status', 'wpas-addons', 'wpas-settings', 'wpas-optin' ) );
	$plugin_frontend_pages = apply_filters( 'wpas_plugin_frontend_pages', array_merge( $ticket_list, $ticket_submit ) );

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

		if ( empty( $post ) ) {
			$protocol = stripos( $_SERVER['SERVER_PROTOCOL'], 'https' ) === true ? 'https://' : 'http://';
			$post_id  = url_to_postid( $protocol . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'] );
			$post     = get_post( $post_id );
		}

		if ( is_singular( 'ticket' ) ) {
			return true;
		}

		if ( isset( $post ) && is_object( $post ) && is_a( $post, 'WP_Post' ) ) {

			// Check for post IDs
			if ( in_array( $post->ID, $plugin_frontend_pages ) ) {
				return true;
			}

			// Check for post types
			if ( in_array( $post->post_type,$plugin_post_types  ) ) {
				return true;
			}

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
		$label = __( 'Submit', 'awesome-support' );
	}

	$defaults = array(
		'type'     => 'button',
		'link'     => '',
		'class'    => apply_filters( 'wpas_make_button_class', 'wpas-btn wpas-btn-default' ),
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
		$output = __( 'Closed', 'awesome-support' );
	} else {

		$post          = get_post( $post_id );
		$post_status   = $post->post_status;
		$custom_status = wpas_get_post_status();

		if ( ! array_key_exists( $post_status, $custom_status ) ) {
			$output = __( 'Open', 'awesome-support' );
		} else {
			$output = $custom_status[ $post_status ];
		}
	}

	return $output;

}

/**
 * Get the ticket state slug.
 *
 * Gets the ticket status. If the ticket is closed nothing fancy.
 * If not, we return the ticket state instead of the "Open" status.
 *
 * The difference with wpas_get_ticket_status_state() is that only slugs are returned. No translation or capitalized
 * terms.
 *
 * @since  3.3
 *
 * @param  integer $post_id Post ID
 *
 * @return string           Ticket status / state
 */
function wpas_get_ticket_status_state_slug( $post_id ) {

	$status = wpas_get_ticket_status( $post_id );

	if ( 'closed' === $status ) {
		return $status;
	}

	$post          = get_post( $post_id );
	$post_status   = $post->post_status;
	$custom_status = wpas_get_post_status();

	if ( ! array_key_exists( $post_status, $custom_status ) ) {
		return 'open';
	}

	return $post->post_status;

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
 *
 * @param  string $case     Redirect case used for filtering
 * @param  string $location URL to redirect to
 * @param  mixed  $post_id  The ID of the post to redirect to (or null if none specified)
 *
 * @return integer           Returns false if location is not provided, true otherwise
 */
function wpas_redirect( $case, $location = null, $post_id = null ) {

	if ( is_null( $location ) ) {
		return false;
	}

	/**
	 * Filter the redirect URL.
	 *
	 * @param  string $location URL to redirect to
	 * @param  mixed  $post_id  ID of the post to redirect to or null if none specified
	 */
	$location = apply_filters( "wpas_redirect_$case", $location, $post_id );
	$location = wp_sanitize_redirect( $location );

	if ( ! headers_sent() ) {
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
function wpas_missing_dependencies() { ?>
	<div class="error">
        <p><?php printf( __( 'Awesome Support dependencies are missing. The plugin can’t be loaded properly. Please run %s before anything else. If you don’t know what this is you should <a href="%s" class="thickbox">install the production version</a> of this plugin instead.', 'awesome-support' ), '<a href="https://getcomposer.org/doc/00-intro.md#using-composer" target="_blank"><code>composer install</code></a>', esc_url( add_query_arg( array( 'tab' => 'plugin-information', 'plugin' => 'awesome-support', 'TB_iframe' => 'true', 'width' => '772', 'height' => '935' ), admin_url( 'plugin-install.php' ) ) ) ); ?></p>
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

	$entry = wp_kses_post( $entry );

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
 *
 * @param  array  $args    Dropdown settings
 * @param  string $options Dropdown options
 *
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
		'data_attr'     => array(),
		'multiple'	=> false,
	);

	$args = wp_parse_args( $args, $defaults );

	$class           = (array) $args['class'];
	$data_attributes = array();

	if ( true === $args['select2'] ) {
		array_push( $class, 'wpas-select2' );
	}

	// If there are some data attributes we prepare them
	if ( ! empty( $args['data_attr'] ) ) {

		foreach ( $args['data_attr'] as $attr => $value ) {
			$data_attributes[] = "data-$attr='$value'";
		}

		$data_attributes = implode( ' ', $data_attributes );

	}
	
	$id = $args['id'];

	/* Start the buffer */
	ob_start(); ?>

	<select<?php if ( true === $args['multiple'] ) echo ' multiple' ?> name="<?php echo $args['name']; ?>" <?php if ( !empty( $class ) ) echo 'class="' . implode( ' ' , $class ) . '"'; ?> <?php if ( !empty( $id ) ) echo "id='$id'"; ?> <?php if ( ! empty( $data_attributes ) ): echo $data_attributes; endif ?> <?php if( true === $args['disabled'] ) { echo 'disabled'; } ?>>
		<?php
		if ( $args['please_select'] ) {
			echo '<option value="">' . __( 'Please select', 'awesome-support' ) . '</option>';
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
	$tickets = wpas_get_tickets( $status );
	$options = '';

	foreach ( $tickets as $ticket ) {
		$options .= "<option value='$ticket->ID' " . selected( $args['selected'], $ticket->ID ) . ">$ticket->post_title</option>";
	}

	echo wpas_dropdown( wp_parse_args( $args, $defaults ), $options );

}

/**
 * Generate html markup for drop-downs that pull data from taxonomies
 *
 * Example use: echo show_dropdown( 'department', "html_inboxrules_rule_new_dept", "wpas-multi-inbox-config-item wpas-multi-inbox-config-item-select", $new_dept );
 *
 * @since 4.0.3
 *
 * @param string    $taxonomy       The taxonomy to be used as the dropdown passed as a string parameter
 * @param string    $field_id       The html id name to be used in the generated markup - passed as a string
 * @param string    $class          The HTML class string to wrap around the dropdown - passed as a string
 * @param string    $selected       Returns the item that was selected by the user.  If this has an initial value the selected value in the dropdown will be set to that item.
 * @param bool      $showcount      A flag to control whether or not to show the taxonomy count in parens next to each item in the dropdown.
 *
 * @return string
 */
function wpas_show_taxonomy_terms_dropdown( $taxonomy, $field_id, $class, $selected, $showcount = false ) {
	$categories = get_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );

	$select = "<select name='$field_id' id='$field_id' class='$class'>";
	$select .= "<option value='-1'>Select</option>";

	foreach( $categories as $category ) {
		$is_selected = (int)$selected === $category->term_id ? ' selected ' : '';
		
		$countstr='';
		if ( true === $showcount ) {
			$countstr = " (" . $category->count . ") ";
		}
		
		$select .= "<option value='" . $category->term_id . "' " . $is_selected . "' >" . $category->name . $countstr . "</option>";
	}
	$select .= "</select>";

	return $select;
}


/**
 * Generate html markup for a standard html agent dropdown
 *
 * @since 4.0.3
 *
 * @param string    $field_id       The html id name to be used in the generated markup - passed as a string
 * @param string    $class          The HTML class string to wrap around the dropdown - passed as a string
 * @param string	$new_assignee	Returns the item that was selected by the user.  If this has an initial value the selected value in the dropdown will be set to that item.
 *
 * Note: We should move this to CORE AS later!
 */
function wpas_show_assignee_dropdown_simple( $field_id, $class, $new_assignee = "" ) {

	$args = array(
		'name' => $field_id,
		'id' => $field_id,
		'class' => $class,
		'exclude' => array(),
		'selected' => empty($new_assignee) ? false : $new_assignee,		
		'cap' => 'edit_ticket',
		'cap_exclude' => '',
		'agent_fallback' => false,
		'please_select' => 'Select',
		'select2' => false,
		'disabled' => false,
		'data_attr' => array()
	);

	echo wpas_users_dropdown( $args );
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
	$query_args = array( 'post_type' => 'ticket', 'page' => 'wpas-settings' );

	if ( ! empty( $tab ) ) {
		$query_args['tab'] = sanitize_text_field( $tab );
	}

	return add_query_arg( $query_args, $admin_url );

}

if ( ! function_exists( 'shuffle_assoc' ) ) {
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
}

if ( ! function_exists( 'wpas_get_admin_path_from_url' ) ) {
	/**
	 * Get the admin path based on the URL.
	 *
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
			} elseif( 'https' === $site_protocol ) {
				$admin_url = 'https' . substr( $admin_url, 4 );
			}
		}

		$abspath = str_replace( '\\', '/', ABSPATH );

		return str_replace( trailingslashit( $site_url ), $abspath, $admin_url );

	}
}

/**
 * Recursively sort an array of taxonomy terms hierarchically. Child categories will be
 * placed under a 'children' member of their parent term.
 *
 * @since  3.0.1
 *
 * @param Array   $cats     taxonomy term objects to sort
 * @param Array   $into     result array to put them in
 * @param integer $parentId the current parent ID to put them in
 *
 * @link   http://wordpress.stackexchange.com/a/99516/16176
 */
function wpas_sort_terms_hierarchicaly( &$cats = array(), &$into = array(), $parentId = 0 ) {

	foreach ( $cats as $i => $cat ) {
		if ( $cat->parent == $parentId ) {
			$into[ $cat->term_id ] = $cat;
			unset( $cats[ $i ] );
		}
	}

	foreach ( $into as $topCat ) {
		$topCat->children = array();
		wpas_sort_terms_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
	}
}

/**
 * Recursively displays hierarchical options into a select dropdown.
 *
 * @since  3.0.1
 *
 * @param  object $term  The term to display
 * @param  string $value The value to compare against
 * @param  int    $level The current level in the drop-down hierarchy
 *
 * @return void
 */
function wpas_hierarchical_taxonomy_dropdown_options( $term, $value, $level = 1 ) {

	$option = '';

	/* Add a visual indication that this is a child term */
	if ( 1 !== $level ) {
		for ( $i = 1; $i < ( $level - 1 ); $i++ ) {
			$option .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		$option .= '&angrt; ';
	}

	$option .= apply_filters( 'wpas_hierarchical_taxonomy_dropdown_options_label', $term->name, $term, $value, $level );
	?>

	<option value="<?php echo $term->term_id; ?>" <?php if( (int) $value === (int) $term->term_id || $value === $term->slug ) { echo 'selected="selected"'; } ?>><?php echo $option; ?></option>

	<?php if ( isset( $term->children ) && !empty( $term->children ) ) {
		++$level;
		foreach ( $term->children as $child ) {
			wpas_hierarchical_taxonomy_dropdown_options( $child, $value, $level );
		}
	}

}

/**
 * Get URL of a submission page
 *
 * As the plugin can handle multiple submission pages, we need to
 * make sure that a give post ID is indeed a submission page, and if no
 * post ID is provided we return the URL of the first submission page.
 *
 * @since 3.2
 *
 * @param bool|false $post_id ID of the submission page
 *
 * @return string
 */
function wpas_get_submission_page_url( $post_id = false ) {

	$submission = wpas_get_submission_pages();

	if ( empty( $submission ) ) {
		return '';
	}

	if ( is_int( $post_id ) && in_array( $post_id, $submission ) ) {
		$url = get_permalink( (int) $post_id );
	} else {
		$url = get_permalink( (int) $submission[0] );
	}

	return wp_sanitize_redirect( $url );

}

/**
 * Get the submission pages IDs
 *
 * @since 3.2.3
 * @return array
 */
function wpas_get_submission_pages() {

	$submission = wpas_get_option( 'ticket_submit' );

	if ( ! is_array( $submission ) ) {
		$submission = array_filter( (array) $submission );
	}

	return $submission;

}

/**
 * Get URL of the tickets list page
 *
 * @since 3.2.2
 *
 * @return string
 */
function wpas_get_tickets_list_page_url() {

	$list = wpas_get_option( 'ticket_list' );

	if ( empty( $list ) ) {
		return '';
	}

	if ( is_array( $list ) && ! empty( $list ) ) {
		$list = $list[0];
	}

	return wp_sanitize_redirect( get_permalink( (int) $list ) );

}

/**
 * Get the link to a ticket reply
 *
 * @since 3.2
 *
 * @param int $reply_id ID of the reply to get the link to
 *
 * @return string|bool Reply link or false if the reply doesn't exist
 */
function wpas_get_reply_link( $reply_id ) {

	$reply = get_post( $reply_id );

	if ( empty( $reply ) ) {
		return false;
	}

	if ( 'ticket_reply' !== $reply->post_type || 0 === (int) $reply->post_parent ) {
		return false;
	}

	$replies = wpas_get_replies( $reply->post_parent, array( 'read', 'unread' ) );

	if ( empty( $replies ) ) {
		return false;
	}

	$position = 0;

	foreach ( $replies as $key => $post ) {

		if ( $reply_id === $post->ID ) {
			$position = $key + 1;
		}

	}

	// We have more replies that what's displayed on one page, so let's set a session var to force displaying all replies
	if ( $position > wpas_get_option( 'replies_per_page', 10 ) ) {
		WPAS()->session->add( 'force_all_replies', true );
	}

	$link = get_permalink( $reply->post_parent ) . "#reply-$reply_id";

	return esc_url( $link );

}

add_action( 'wpas_after_template', 'wpas_credit', 10, 3 );
/**
 * Display a link to the plugin page.
 *
 * @since  3.1.3
 * @var string $name Template name
 * @return void
 */
function wpas_credit( $name ) {

	if ( ! in_array( $name, array( 'details', 'registration', 'submission', 'list' ) ) ) {
		return;
	}

	if ( true === (bool) wpas_get_option( 'credit_link' ) ) {
		echo '<p class="wpas-credit">Built with Awesome Support,<br> the most versatile <a href="https://wordpress.org/plugins/awesome-support/" target="_blank" title="The best support plugin for WordPress">WordPress Support Plugin</a></p>';
	}

}

add_filter( 'plugin_locale', 'wpas_change_plugin_locale', 10, 2 );
/**
 * Change the plugin locale
 *
 * This is used to temporarily change the plugin locale on a site,
 * mainly for debugging purpose.
 *
 * @since 3.2.2
 *
 * @param string $locale Current plugin locale
 * @param string $domain Current plugin domain
 *
 * @return string
 */
function wpas_change_plugin_locale( $locale, $domain ) {

	if ( 'wpas' !== $domain ) {
		return $locale;
	}

	/**
	 * Custom locale.
	 *
	 * The custom locale defined by the URL var $wpas_locale
	 * is used for debugging purpose. It makes testing language
	 * files easy without changing the site main language.
	 * It can also be useful when doing support on a site that's
	 * not in English.
	 *
	 * @since  3.1.5
	 * @var    string
	 */
	$wpas_locale = filter_input( INPUT_GET, 'wpas_locale', FILTER_SANITIZE_STRING );

	if ( ! empty( $wpas_locale ) ) {
		$locale = $wpas_locale;
	}

	return $locale;

}

add_filter( 'wpas_logs_handles', 'wpas_default_log_handles', 10, 1 );
/**
 * Register default logs handles.
 *
 * @since  3.0.2
 *
 * @param  array $handles Array of registered log handles
 *
 * @return array          Array of registered handles with the default ones added
 */
function wpas_default_log_handles( $handles ) {
	array_push( $handles, 'error' );

	return $handles;
}

add_filter( 'wp_link_query_args', 'wpas_remove_tinymce_links_internal', 10, 1 );
/**
 * Filter the link query arguments to remove completely internal links from the list.
 *
 * @since 3.2.0
 *
 * @param array $query An array of WP_Query arguments.
 *
 * @return array $query
 */
function wpas_remove_tinymce_links_internal( $query ) {

	/**
	 * Getting the post ID this way is quite dirty but it seems to be the only way
	 * as we are in an Ajax query and the only given parameter is the $query
	 */
	$url     = wp_get_referer();
	$post_id = url_to_postid( $url );

	if ( $post_id === wpas_get_option( 'ticket_submit' ) ) {
		$query['post_type'] = array( 'none' );
	}

	return $query;

}

/**
 * Convert an array to a string of key/value pairs
 *
 * This function does not work with multidimensional arrays.
 *
 * @since 3.3
 *
 * @param array $array The array to convert
 *
 * @return string
 */
function wpas_array_to_key_value_string( $array ) {

	$pairs = array();

	foreach ( $array as $key => $value ) {

		// Convert boolean values to string
		if ( is_bool( $value ) ) {
			$value = $value ? 'true' : false;
		}

		$pairs[] = "$key='$value'";
	}

	return implode( ' ', $pairs );

}

/**
 * Convert an associative array into a key/value pairs string
 *
 * The function also takes care of prefixing the attributes with data- if needed.
 *
 * @since 3.3
 *
 * @param array $array      The array to convert
 * @param bool  $user_funct Whether or not to check if the value passed is in fact a function to use for getting the
 *                          actual value
 *
 * @return array
 */
function wpas_array_to_data_attributes( $array, $user_funct = false ) {

	$clean = array();

	foreach ( $array as $key => $value ) {

		if ( 'data-' !== substr( $key, 0, 5 ) ) {
			$key = "data-$key";
		}

		if ( true === $user_funct ) {

			$function = is_array( $value ) ? $value[0] : $value;
			$args     = array();

			if ( is_array( $value ) ) {
				$args = $value;
				unset( $args[0] ); // Remove the function name from the args
			}

			if ( function_exists( $function ) ) {
				$value = call_user_func( $function, array_values( $args ) );
			}

		}

		// This function does not work with multidimensional arrays
		if ( is_array( $value ) ) {
			continue;
		}

		$clean[ $key ] = $value;

	}

	return wpas_array_to_key_value_string( $clean );

}

/**
 * Dumb wrapper for get_the_time() that passes the desired format for getting a Unix timestamp
 *
 * This function is used as a user callback when preparing the front-end tickets list table.
 *
 * @see   wpas_get_tickets_list_columns()
 * @since 3.3
 * @return string
 */
function wpas_get_the_time_timestamp() {
	return get_the_time( 'U' );
}

/**
 * Check if multi agent is enabled
 * @return boolean
 */
function wpas_is_multi_agent_active() {
	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );
	
	if ( isset( $options['multiple_agents_per_ticket'] ) && true === boolval( $options['multiple_agents_per_ticket'] ) ) {
		return true;
	}
	
	return false;
}

/**
 * Check if support priority is active
 * @return boolean
 */
function wpas_is_support_priority_active() {
	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );
	
	if ( isset( $options['support_priority'] ) && true === boolval( $options['support_priority'] ) ) {
		return true;
	}
	
	return false;
}

/**
 * Create a pseduo GUID
 *
 * @return string
 */
 function wpas_create_pseudo_guid(){
	 return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
 }
 

/**
 * Create a random MD5 based hash.
 *
 * @return string
 */ 
 function wpas_random_hash() {
	
	$time  = time();
	$the_hash = md5( $time . (string) random_int(0, getrandmax()) );
	
	return $the_hash;
	
}

/**
 * Wrapper for FILTER_INPUT using the INPUT_SERVER parameter.
 * Includes a work-around for a known issue.
 * See: https://github.com/xwp/stream/issues/254
 * 
 * @since 4.3.3
 *
 * @return string
 */ 
 function wpas_filter_input_server( $input_var = 'REQUEST_URI' ) {
	 
	 $filtered_input = filter_input( INPUT_SERVER, $input_var, FILTER_SANITIZE_STRING );
	 
	 if ( empty( $filtered_input ) ) {
		 
		if ( filter_has_var(INPUT_SERVER, $input_var )) {
				$filtered_input = filter_input( INPUT_SERVER, $input_var, FILTER_SANITIZE_STRING );
			} else {
				if (isset($_SERVER["REQUEST_URI"]))
					$filtered_input = filter_var( $_SERVER[$input_var], FILTER_SANITIZE_STRING );
				else
					$filtered_input = null;
			}		 
	 }
	 
	 return $filtered_input ;
	 
 }
 
 /**
 * Returns TRUE if running in SAAS mode, False otherwise
 *
 * @since 4.3.6
 *
 * @return boolean
 */
 function is_saas() {
	 
	if ( ! defined( 'WPAS_SAAS' ) ) {
		return false ;
	} elseif  ( ( defined( 'WPAS_SAAS' ) && false === WPAS_SAAS ) ) {
		return false ;
	} elseif  ( ( defined( 'WPAS_SAAS' ) && true === WPAS_SAAS ) ) {
		return true ;
	}
  
	return false ;
  
 }
 
 /**
 * Returns TRUE if we are declaring compatibility with GUTENBERG.
 * Returns FALSE if not.  The default is FALSE - we are not
 * compatible
 *
 * @since 4.4.0
 *
 * @return boolean
 */
 function wpas_gutenberg_meta_box_compatible() {
	 $is_compatible = false ;
	 
	 /**
	  * if our REST API is NOT enabled, return TRUE since the lack of a REST API will force GUTENBERG 
	  * to fallback to the regular editor anyway.  This will then prevent the "Gutenberg Incompatible Meta Box"
	  * message from showing up in our metaboxes
	  */ 
	  if ( ! class_exists( 'WPAS_API' ) ) {
		  $is_compatible = true ;
	  }
		  
	 // Override everything anyway based on a variable in the wp-config file.
	 if ( defined('WPAS_GUTENBERG_META_BOX_COMPATIBLE') && true === WPAS_GUTENBERG_META_BOX_COMPATIBLE )  {
		 $is_compatible = true ;
	 }
	 
	 return $is_compatible;
 }
 
 /**
 * Returns TRUE if the current user is an agent
 * Returns FALSE if not.  
 *
 * @since 4.4.0
 *
 * @return boolean
 */
 function wpas_is_agent() {
	return current_user_can( 'edit_ticket' ) ;
 }
 
 /**
 * Returns TRUE if the current user is an Awesome Support Admin
 * Returns FALSE if not.  
 *
 * @since 4.4.0
 *
 * @return boolean
 */
 function wpas_is_asadmin() {
	return ( is_super_admin() || current_user_can( 'administrator' ) || current_user_can( 'administer_awesome_support' ) );
 }
 
 /**
 * Returns TRUE if the current user is an agent on the ticket
 * Returns FALSE if not.  
 *
 * @since 4.4.0
 *
 * @param int|post Ticket id or post object
 *
 * @return boolean
 */
 function wpas_is_user_agent_on_ticket( $ticket ) {
	 
	$ticket_id = null;	
	$post = null ;
	$is_agent_on_ticket = false ;

	/**
	 * Get the post data if $ticket passed in is a ticket id.
	 * Otherwise, get the id if $ticket passed is a post/ticket object.
	 */	
	if ( 'array' == gettype( $ticket ) || 'object' === gettype( $ticket ) ) {
		$post = $ticket;
		if ( ! empty( $post ) ) {
			$ticket_id = $post->ID;
		}
	} else {
		$ticket_id = $ticket ;
		if ( ! empty( $ticket ) ) {
			$post = get_post( $ticket_id ); 
		}		
	}
	
	if (!empty($post)) {
	
		/**
		 * Get author and agent ids on the ticket
		 */
		$author_id = intval( $post->post_author );
		$agent_id = intval(get_post_meta( $post->ID, '_wpas_assignee', true ));
		$agent_id2 = intval(get_post_meta( $post->ID, '_wpas_secondary_assignee', true ));
		$agent_id3 = intval(get_post_meta( $post->ID, '_wpas_tertiary_assignee', true ));		
		
		$current_user = get_current_user_id();

		if (   ( $current_user === $author_id  && current_user_can( 'view_ticket' ) ) 
			|| ( $current_user === $agent_id  && current_user_can( 'view_ticket' ) )
			|| ( $current_user === $agent_id2  && current_user_can( 'view_ticket' ) ) 
			|| ( $current_user === $agent_id3  && current_user_can( 'view_ticket' ) ) ) {
				
			$is_agent_on_ticket = true;
			
		}		
		
	}
	
	return apply_filters('wpas_is_user_agent_on_ticket', $is_agent_on_ticket);
	
 }
 

 /**
 * Returns the role of the current logged in user.
 *
 * Returns FALSE if user is not logged in.
 *
 * @since 4.4.0
 *
 * @return boolean
 */
function wpas_get_current_user_role() {
	
	if( is_user_logged_in() ) {
		
		$user = wp_get_current_user();
		$role = ( array ) $user->roles;
		return $role[0];
		
	} else {
		
		return false;
		
	}
 }
 
 /**
 * Returns ALL the roles of the current logged in user.
 *
 * This is sometimes needed when using a plugin like USER ROLE EDITOR 
 * that can assign multiple roles to a user.
 *
 * Returns FALSE if user is not logged in.
 *
 * @since 4.4.0
 *
 * @return boolean
 */
function wpas_get_current_user_roles() {
	
	if( is_user_logged_in() ) {
		
		$user = wp_get_current_user();
		$role = ( array ) $user->roles;
		return $role;
		
	} else {
		
		return false;
		
	}
 }
 
 /**
 * Checks to see if a role is in a list of roles.
 *
 * Returns true if $role is in $role_list.
 * otherwise returns false.
 *
 * $role_list is a comma separate list of values.
 *
 * Since all parameters are strings this could be a generic search for a string in a comma separated list of strings...
 *
 * @since 4.4.0
 *
 * @param string $role 		The name of the role to search for
 * @param string $role_list	The list of roles to search in - comma separated values.
 *
 * @return boolean
 */
 function wpas_role_in_list( $role, $role_list ) {
	 
	$roles = explode( ',', $role_list ) ;
		
	if ( empty( $roles) ) return false ;  // no roles listed so return false - row is not in the list ;
			
	if ( in_array( $role, $roles, true ) ) {
		return true ;
	} else {
		return false ;
	}
	 
 }
 
 /**
 * Checks to see if the current user's role is in a list of roles.
 *
 * Returns true if the current user's role is in $role_list.
 * otherwise returns false.
 *
 * $role_list is a comma separate list of values.
 *
 *
 * @since 4.4.0
 *
 * @param string $role_list	The list of roles to search in - comma separated values.
 *
 * @return boolean
 */
 function wpas_current_role_in_list( $role_list ) {
	 
	 // If list of roles is empty for some reason return false
	 if ( true === empty( $role_list ) ) {
		 return false ;
	 }
	 
	$current_roles = wpas_get_current_user_roles();  // note that we are expect an array of roles.
	
	if ( empty( $current_roles ) ) return false ;  // user not logged in for some reason so return false ;
	
	foreach ( $current_roles as $current_role ) {
		
		if ( true === wpas_role_in_list( $current_role, $role_list ) ) {
			// role found so break prematurely and just return;
			return true ;
		}
		
	}
	
	return false ;
	 
	 
 }
 