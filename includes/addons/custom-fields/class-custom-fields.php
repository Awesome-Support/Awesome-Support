<?php
/**
 * Awesome Support.
 *
 * @package   Awesome Support/Custom Fields
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */

class WPAS_Custom_Fields {

	public function __construct() {

		/* Load custom fields dependencies */
		require_once( WPAS_PATH . 'includes/addons/custom-fields/class-save.php' );
		require_once( WPAS_PATH . 'includes/addons/custom-fields/class-display.php' );

		/**
		 * Array where all custom fields will be stored.
		 */
		$this->options = array();

		/**
		 * Register the taxonomies
		 */
		add_action( 'init', array( $this, 'register_taxonomies' ) );

		/**
		 * Instantiate the class that handles saving the custom fields.
		 */
		$wpas_save = new WPAS_Save_Fields();

		if( is_admin() ) {

			/**
			 * Add custom columns
			 */
			add_action( 'manage_ticket_posts_columns',          array( $this, 'add_custom_column' ), 10, 1 );
			add_action( 'manage_ticket_posts_columns',          array( $this, 'move_status_first' ), 15, 1 );
			add_action( 'manage_ticket_posts_custom_column' ,   array( $this, 'custom_columns_content' ), 10, 2 );
			add_filter( 'manage_edit-ticket_sortable_columns' , array( $this, 'custom_columns_sortable' ), 10, 1 );
			add_action( 'pre_get_posts',                        array( $this, 'custom_column_orderby' ) );
			
			/**
			 * Add the taxonomies filters
			 */
			add_action( 'restrict_manage_posts', array( $this, 'custom_taxonomy_filter' ), 10, 0 );
			add_action( 'restrict_manage_posts', array( $this, 'status_filter' ), 9, 0 ); // Filter by ticket status
			add_filter( 'parse_query',           array( $this, 'custom_taxonomy_filter_convert_id_term' ), 10, 1 );
			add_filter( 'parse_query',           array( $this, 'status_filter_by_status' ), 10, 1 );

		} else {

			/* Now we can instantiate the save class and save */
			if ( isset( $_POST['wpas_title'] ) && isset( $_POST['wpas_message'] ) ) {

				/* Check for required fields and possibly block the submission. */
				add_filter( 'wpas_before_submit_new_ticket_checks', array( $wpas_save, 'check_required_fields' ) );

				/* Save the custom fields. */
				add_action( 'wpas_open_ticket_after', array( $wpas_save, 'save_submission' ), 10, 2 );
				
			}

			/* Display the custom fields on the submission form */
			add_action( 'wpas_submission_form_inside_after_subject', array( 'WPAS_Custom_Fields_Display', 'submission_form_fields' ) );
		}
		
	}

	/**
	 * Add a new custom field to the ticket.
	 * 
	 * @param (string) $name Option name
	 * @since 3.0.0
	 */
	public function add_field( $name = false, $args = array() ) {

		/* Option name is mandatory */
		if ( !$name ) {
			return;
		}

		$name = sanitize_text_field( $name );

		/* Default arguments */
		$defaults = array(
			'callback'              => 'text',                // Field callback to display its content
			'core'                  => false,                 // Is this a custom fields that belongs to the plugin core
			'required'              => false,                 // Is this field required for front-end submission
			'log'                   => false,                 // Should the content updates of this field be logged in the system
			'capability'            => 'create_ticket',       // Required capability for this field
			'sanitize'              => 'sanitize_text_field', // Sanitize callback for the field value
			'save_callback'         => false,                 // Saving callback if a specific saving method is required
			'show_column'           => false,                 // Show field content in the tickets list & in the admin
			'column_callback'       => 'wpas_cf_value',       // Column callback function
			'sortable_column'       => false,                 // Not compatible with taxonomies
			'filterable'            => true,                  // Used for taxonomies only
			'title'                 => '',                    // Nicely formatted title for this field
			'placeholder'           => '',                    // Placeholder to display in the submission form
			'desc'                  => '',                    // Helper description for the field
			/* The following parameters are users for taxonomies only. */
			'taxo_std'              => false,                 // For taxonomies, should it behave like a standard WordPress taxonomy
			'label'                 => '',
			'label_plural'          => '',
			'taxo_hierarchical'     => true,
			'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
		);

		/* Merge args */
		$arguments = wp_parse_args( $args, $defaults );

		/* Field with args */
		$option = array( 'name' => $name, 'args' => $arguments );

		$this->options[$name] = apply_filters( 'wpas_add_field', $option );

	}

	/**
	 * Remove a custom field.
	 * 
	 * @param  string $id ID of the field to remove
	 * @since  3.0.0
	 */
	public function remove_field( $id ) {

		$fields = $this->options;

		if( isset( $fields[$id] ) )
			unset( $fields[$id] );

		$this->options = $fields;

	}

	/**
	 * Register all custom taxonomies.
	 *
	 * @since  3.0.0
	 */
	public function register_taxonomies() {

		$options         = $this->options;
		$this->remove_mb = array();

		foreach( $options as $option ) {

			/* Reset vars for safety */
			$labels = array();
			$args   = array();
			$name   = '';
			$plural = '';

			if( 'taxonomy' == $option['args']['callback'] ) {

				$name         = !empty( $option['args']['label'] ) ? sanitize_text_field( $option['args']['label'] ) : ucwords( str_replace( array( '_', '-' ), ' ', $option['name'] ) );
				$plural       = !empty( $option['args']['label_plural'] ) ? sanitize_text_field( $option['args']['label_plural'] ) : $name . 's';
				$column       = true === $option['args']['taxo_std'] ? true : false;
				$hierarchical = $option['args']['taxo_hierarchical'];

				$labels = array(
					'name'              => $plural,
					'singular_name'     => $name,
					'search_items'      => sprintf( __( 'Search %s', 'wpas' ), $plural ),
					'all_items'         => sprintf( __( 'All %s', 'wpas' ), $plural ),
					'parent_item'       => sprintf( __( 'Parent %s', 'wpas' ), $name ),
					'parent_item_colon' => sprintf( _x( 'Parent %s:', 'Parent term in a taxonomy where %s is dynamically replaced by the taxonomy (eg. "book")', 'wpas' ), $name ),
					'edit_item'         => sprintf( __( 'Edit %s', 'wpas' ), $name ),
					'update_item'       => sprintf( __( 'Update %s', 'wpas' ), $name ),
					'add_new_item'      => sprintf( __( 'Add New %s', 'wpas' ), $name ),
					'new_item_name'     => sprintf( _x( 'New %s Name', 'A new taxonomy term name where %s is dynamically replaced by the taxonomy (eg. "book")', 'wpas' ), $name ),
					'menu_name'         => $plural,
				);

				$args = array(
					'hierarchical'      => $hierarchical,
					'labels'            => $labels,
					'show_ui'           => true,
					'show_admin_column' => $column,
					'query_var'         => true,
					'rewrite'           => array( 'slug' => $option['name'] ),
					'capabilities'      => array(
						'manage_terms' => 'create_ticket',
						'edit_terms'   => 'settings_tickets',
						'delete_terms' => 'settings_tickets',
						'assign_terms' => 'create_ticket'
					)
				);

				if ( false !== $option['args']['update_count_callback'] && function_exists( $option['args']['update_count_callback'] ) ) {
					$args['update_count_callback'] = $option['args']['update_count_callback'];
				}

				register_taxonomy( $option['name'], array( 'ticket' ), $args );

				if( false === $option['args']['taxo_std'] )
					array_push( $this->remove_mb, $option['name'] );

			}

		}

		/* Remove metaboxes that won't be used */
		if( !empty( $this->remove_mb ) )
			add_action( 'admin_menu', array( $this, 'remove_taxonomy_metabox' ) );

	}

	/**
	 * Remove taxonomies metaboxes.
	 *
	 * In some cases taxonomies are used as select.
	 * Hence, we don't need the standard taxonomy metabox.
	 *
	 * @since  3.0.0
	 */
	public function remove_taxonomy_metabox() {

		foreach( $this->remove_mb as $key => $mb )
			remove_meta_box( $mb . 'div', 'ticket', 'side' );
	}

	/**
	 * Return the list of fields
	 * 
	 * @return (array) List of custom fields
	 * @since 3.0.0
	 */
	public function get_custom_fields() {
		return apply_filters( 'wpas_get_custom_fields', $this->options );
	}

	/**
	 * Check if custom fields are registered.
	 *
	 * If there are registered custom fields, the method returns true.
	 * Core fields are not considered registered custom fields by default
	 * but that can be overridden with the $core parameter.
	 *
	 * @since  3.0.0
	 * @param  boolean $core True if core fields should be counted as registered custom fields.
	 * @return boolean       True if custom fields are present, false otherwise
	 */
	public function have_custom_fields( $core = false ) {
		$fields = $this->get_custom_fields();
		$have = false;

		foreach ( $fields as $key => $field ) {
			if ( false === boolval( $field['args']['core'] ) || true === $core && true === boolval( $field['args']['core'] ) ) {
				$have= true;
			}
		}

		return $have;
	}

	/**
	 * Retrieve post meta value.
	 * 
	 * @param  (string)   $name    Option name
	 * @param  (integer)  $post_id Post ID
	 * @param  (mixed)    $default Default value
	 * @return (mixed)             Meta value
	 * @since  3.0.0
	 */
	public static function get_value( $name, $post_id, $default = false, $echo = false ) {

		if ( '_' !== substr( $name, 0, 1 ) ) {
			if ( 'wpas' === substr( $name, 0, 4 ) ) {
				$name = "_$name";
			} else {
				$name = "_wpas_$name";
			}
		} else {
			if ( '_wpas' !== substr( $name, 0, 5) ) {
				$name = "_wpas$name";
			}
		}

		/* Get option */
		$value = get_post_meta( $post_id, $name, true );

		/* Return value */
		if ( '' === $value ) {
			$value = $default;
		}

		if ( true === $echo ) {
			echo $value;
		} else {
			return $value;
		}

	}

	/**
	 * Add possible custom columns to tickets list.
	 * 
	 * @param  array $columns List of default columns
	 * @return array          Updated list of columns
	 * @since  3.0.0
	 */
	public function add_custom_column( $columns ) {

		$new    = array();
		$custom = array();
		$fields = $this->get_custom_fields();

		/**
		 * Prepare all custom fields that are supposed to show up
		 * in the admin columns.
		 */
		foreach ( $fields as $field ) {

			/* If CF is a regular taxonomy we don't handle it, WordPress does */
			if( 'taxonomy' == $field['args']['callback'] && true === $field['args']['taxo_std'] ) {
				continue;
			}

			if( true === $field['args']['show_column'] ) {
				$id          = $field['name'];
				$title       = wpas_get_field_title( $field );
				$custom[$id] = $title;
			}

		}

		/**
		 * Parse the old columns and add the new ones.
		 */
		foreach( $columns as $col_id => $col_label ) {

			/* Merge all custom columns right before the date column */
			if( 'date' == $col_id ) {
				$new = array_merge( $new, $custom );
			}

			$new[$col_id] = $col_label;

		}

		return $new;
	}

	/**
	 * Reorder the admin columns.
	 *
	 * @since  3.0.0
	 * @param  array $columns List of admin columns
	 * @return array          Re-ordered list
	 */
	public function move_status_first( $columns ) {

		if ( isset( $columns['status'] ) ) {
			$status_content = $columns['status'];
			unset( $columns['status'] );
		} else {
			return $columns;
		}

		$new = array();

		foreach ( $columns as $column => $content ) {

			if ( 'title' === $column ) {
				$new['status'] = $status_content;
			}

			$new[$column] = $content;

		}

		return $new;

	}

	/**
	 * Manage custom columns content
	 * 
	 * @param  array   $column  Columns currently processed
	 * @param  integer $post_id ID of the post being processed
	 * @since  3.0.0
	 */
	public function custom_columns_content( $column, $post_id ) {

		$fields = $this->get_custom_fields();

		if ( isset( $fields[$column] ) ) {

			if ( true === $fields[$column]['args']['show_column'] ) {

				/* In case a custom callback is specified we use it */
				if ( function_exists( $fields[$column]['args']['column_callback'] ) ) {
					call_user_func( $fields[$column]['args']['column_callback'], $fields[$column]['name'], $post_id );
				}

				/* Otherwise we use the default rendering options */
				else {
					wpas_cf_value( $fields[$column]['name'], $post_id );
				}

			}

		}

	}

	/**
	 * Make custom columns sortable
	 *
	 * @param  array $columns Already sortable columns
	 * @return array          New sortable columns
	 * @since  3.0.0
	 */
	public function custom_columns_sortable( $columns ) {

		$new    = array();
		$fields = $this->get_custom_fields();

		foreach ( $fields as $field ) {

			/* If CF is a regular taxonomy we don't handle it, WordPress does */
			if ( 'taxonomy' == $field['args']['callback'] && true === $field['args']['taxo_std'] ) {
				continue;
			}

			if ( true === $field['args']['show_column'] && true === $field['args']['sortable_column'] ) {

				$id       = $field['name'];
				$new[$id] = $id;

			}

		}

		return array_merge( $columns, $new );

	}

	/**
	 * Reorder custom columns based on custom values.
	 * 
	 * @param  object $query Main query
	 * @since  3.0.0
	 */
	public function custom_column_orderby( $query ) {

		$fields  = $this->get_custom_fields();
		$orderby = $query->get( 'orderby' );

		if( array_key_exists( $orderby, $fields ) ) {

			if( 'taxonomy' != $fields[$orderby]['args']['callback'] ) {
				$query->set( 'meta_key', '_wpas_' . $orderby );
				$query->set( 'orderby', 'meta_value' );
			}

		}

	}

	/**
	 * Add filters for custom taxonomies
	 *
	 * @since  2.0.0
	 */
	public function custom_taxonomy_filter() {

		global $typenow;

		if ( 'ticket' != $typenow ) {
			return;
		}

		$post_types = get_post_types( array( '_builtin' => false ) );

		if ( in_array( $typenow, $post_types ) ) {

			$filters = get_object_taxonomies( $typenow );

			/* Get all custom fields */
			$fields = $this->get_custom_fields();

			foreach ( $filters as $tax_slug ) {

				if( !array_key_exists( $tax_slug, $fields ) ) {
					continue;
				}

				if( true !== $fields[$tax_slug]['args']['filterable'] ) {
					continue;
				}

				$tax_obj = get_taxonomy( $tax_slug );

				$args = array(
					'show_option_all' => __( 'Show All ' . $tax_obj->label ),
					'taxonomy'        => $tax_slug,
					'name'            => $tax_obj->name,
					'orderby'         => 'name',
					'hierarchical'    => $tax_obj->hierarchical,
					'show_count'      => true,
					'hide_empty'      => true,
					'hide_if_empty'   => true,
				);

				if( isset( $_GET[$tax_slug] ) ) {
					$args['selected'] = $_GET[$tax_slug];
				}

				wp_dropdown_categories( $args );
			}
		}

	}

	/**
	 * Add status dropdown in the filters bar.
	 *
	 * @since  2.0.0
	 */
	public function status_filter() {

		global $typenow;

		if ( 'ticket' != $typenow ) {
			return;
		}

		if ( isset( $_GET['post_status'] ) ) {
			return false;
		}

		$this_sort       = isset( $_GET['wpas_status'] ) ? $_GET['wpas_status'] : '';
		$all_selected    = ( '' === $this_sort ) ? 'selected="selected"' : '';
		$open_selected   = ( 'open' === $this_sort ) ? 'selected="selected"' : '';
		$closed_selected = ( 'closed' === $this_sort ) ? 'selected="selected"' : '';
		$dropdown        = '<select id="wpas_status" name="wpas_status">';
		$dropdown        .= "<option value='' $all_selected>" . __( 'Any Status', 'wpas' ) . "</option>";
		$dropdown        .= "<option value='open' $open_selected>" . __( 'Open', 'wpas' ) . "</option>";
		$dropdown        .= "<option value='closed' $closed_selected>" . __( 'Closed', 'wpas' ) . "</option>";
		$dropdown        .= '</select>';

		echo $dropdown;

	}

	/**
	 * Convert taxonomy ID into term.
	 *
	 * When filtering, WordPress uses the ID by default in the query but
	 * that doesn't work. We need to convert it to the taxonomy term.
	 * 
	 * @param  object $query WordPress current main query
	 * @since  2.0.0
	 * @link   http://wordpress.stackexchange.com/questions/578/adding-a-taxonomy-filter-to-admin-list-for-a-custom-post-type
	 */
	public function custom_taxonomy_filter_convert_id_term( $query ) {

		global $pagenow;

		/* Check if we are in the correct post type */
		if ( is_admin() && 'edit.php' == $pagenow && isset( $_GET['post_type'] ) && 'ticket' === $_GET['post_type'] && $query->is_main_query() ) {

			/* Get all custom fields */
			$fields = $this->get_custom_fields();

			/* Filter custom fields that are taxonomies */
			foreach ( $query->query_vars as $arg => $value ) {
				if ( array_key_exists( $arg, $fields ) && 'taxonomy' === $fields[$arg]['args']['callback'] && true === $fields[$arg]['args']['filterable'] ) {

					$term = get_term_by( 'id', $value, $arg );

					if ( false !== $term ) {
						$query->query_vars[$arg] = $term->slug;
					}

				}
			}

		}
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
	public function status_filter_by_status( $query ) {

		global $pagenow;

		/* Check if we are in the correct post type */
		if ( is_admin()
			&& 'edit.php' == $pagenow
			&& isset( $_GET['post_type'] )
			&& 'ticket' == $_GET['post_type']
			&& isset( $_GET['wpas_status'] )
			&& !empty( $_GET['wpas_status'] )
			&& $query->is_main_query() ) {
			
			$query->query_vars['meta_key']     = '_wpas_status';
			$query->query_vars['meta_value']   = sanitize_text_field( $_GET['wpas_status'] );
			$query->query_vars['meta_compare'] = '=';
		}

	}

}

/**
 * Instantiate the global $wpas_cf object containing all the custom fields.
 * This object is used throughout the entire plugin so it is capital to be able
 * to access it anytime and not to redeclare a second object when registering
 * new custom fields.
 *
 * @since  3.0.0
 * @var    object
 */
$wpas_cf = new WPAS_Custom_Fields;

/**
 * Return a custom field value.
 *
 * @param  (string)   $name    Option name
 * @param  (integer)  $post_id Post ID
 * @param  (mixed)    $default Default value
 * @return (mixed)             Meta value
 * @since  3.0.0
 */
function wpas_get_cf_value( $name, $post_id, $default = false ) {
	return WPAS_Custom_Fields::get_value( $name, $post_id, $default = false );
}

/**
 * Return a custom field value.
 *
 * @param  (string)   $name    Option name
 * @param  (integer)  $post_id Post ID
 * @param  (mixed)    $default Default value
 * @return (mixed)             Meta value
 * @since  3.0.0
 */
function wpas_cf_value( $name, $post_id, $default = '' ) {
	return WPAS_Custom_Fields::get_value( $name, $post_id, $default, true );
}

/**
 * Add a new custom field.
 *
 * @since  3.0.0
 * @param  string  $name  The ID of the custom field to add
 * @param  array   $args  Additional arguments for the custom field
 * @return boolean        Returns true on success or false on failure
 */
function wpas_add_custom_field( $name, $args = array() ) {

	global $wpas_cf;

	if( !isset( $wpas_cf ) || !class_exists( 'WPAS_Custom_Fields' ) )
		return false;

	$wpas_cf->add_field( $name, $args );

	return true;

}

/**
 * Add a new custom taxonomy.
 *
 * @since  3.0.0
 * @param  string  $name  The ID of the custom field to add
 * @param  array   $args  Additional arguments for the custom field
 * @return boolean        Returns true on success or false on failure
 */
function wpas_add_custom_taxonomy( $name, $args = array() ) {

	global $wpas_cf;

	if( !isset( $wpas_cf ) || !class_exists( 'WPAS_Custom_Fields' ) )
		return false;

	/* Force the custom fields type to be a taxonomy. */
	$args['callback']        = 'taxonomy';
	$args['column_callback'] = 'wpas_show_taxonomy_column';

	/* Add the taxonomy. */
	$wpas_cf->add_field( $name, $args );

	return true;

}

wpas_register_core_fields();
/**
 * Register the cure custom fields.
 *
 * @since  3.0.0
 * @return void
 */
function wpas_register_core_fields() {

	global $wpas_cf;

	if ( !isset( $wpas_cf ) ) {
		return;
	}

	$wpas_cf->add_field( 'assignee',   array( 'core' => true, 'show_column' => false, 'log' => true, 'title' => __( 'Support Staff', 'wpas' ) ) );
	// $wpas_cf->add_field( 'ccs',        array( 'core' => true, 'show_column' => false, 'log' => true ) );
	$wpas_cf->add_field( 'status',     array( 'core' => true, 'show_column' => true, 'log' => false, 'callback' => false, 'column_callback' => 'wpas_cf_display_status', 'save_callback' => null ) );
	$wpas_cf->add_field( 'ticket-tag', array(
		'core'                  => true,
		'show_column'           => true,
		'log'                   => true,
		'callback'              => 'taxonomy',
		'taxo_std'              => true,
		'column_callback'       => 'wpas_cf_display_status',
		'save_callback'         => null,
		'label'                 => __( 'Tag', 'wpas' ),
		'name'                  => __( 'Tag', 'wpas' ),
		'label_plural'          => __( 'Tags', 'wpas' ),
		'taxo_hierarchical'     => false,
		'update_count_callback' => 'wpas_update_ticket_tag_terms_count'
		)
	);

	$options = maybe_unserialize( get_option( 'wpas_options', array() ) );

	if ( isset( $options['support_products'] ) && true === boolval( $options['support_products'] ) ) {

		$slug = defined( 'WPAS_PRODUCT_SLUG' ) ? WPAS_PRODUCT_SLUG : 'product';

		/* Filter the taxonomy labels */
		$labels = apply_filters( 'wpas_product_taxonomy_labels', array(
			'label'        => __( 'Product', 'wpas' ),
			'name'         => __( 'Product', 'wpas' ),
			'label_plural' => __( 'Products', 'wpas' )
			)
		);

		$wpas_cf->add_field( 'product', array(
			'core'                  => false,
			'show_column'           => true,
			'log'                   => true,
			'callback'              => 'taxonomy',
			'taxo_std'              => false,
			'column_callback'       => 'wpas_show_taxonomy_column',
			'label'                 => $labels['label'],
			'name'                  => $labels['name'],
			'label_plural'          => $labels['label_plural'],
			'taxo_hierarchical'     => true,
			'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
			'rewrite'               => array( 'slug' => $slug )
			)
		);

	}

}