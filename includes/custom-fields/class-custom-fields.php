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

	/**
	 * List of metaboxes to remove.
	 */
	public $remove_mb;

	public function __construct() {

		/**
		 * Array where all custom fields will be stored.
		 */
		$this->options = array();

		/**
		 * Register the taxonomies
		 */
		add_action( 'init', array( $this, 'register_taxonomies' ) );

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

			/* Check for required fields and possibly block the submission. */
			add_filter( 'wpas_before_submit_new_ticket_checks', array( $this, 'check_required_fields' ) );

			/* Save the custom fields. */
			add_action( 'wpas_open_ticket_before_assigned', array( $this, 'frontend_submission' ), 10, 2 );

			/* Display the custom fields on the submission form */
			add_action( 'wpas_submission_form_inside_after_subject', array( $this, 'submission_form_fields' ) );

		}

	}

	/**
	 * Add a new custom field to the ticket.
	 *
	 * @param string $name Option name
	 * @param array  $args Field arguments
	 *
	 * @return bool Whether or not the field was added
	 *
	 * @since 3.0.0
	 */
	public function add_field( $name = '', $args = array() ) {

		/* Option name is mandatory */
		if ( empty( $name ) ) {
			return false;
		}

		$name = sanitize_text_field( $name );

		/* Default arguments */
		$defaults = WPAS_Custom_Field::get_field_defaults();

		/* Merge args */
		$arguments = wp_parse_args( $args, $defaults );

		/* Convert the callback for backwards compatibility */
		if ( ! empty( $arguments['callback'] ) ) {

			_deprecated_argument( 'WPAS_Custom_Fields::add_field()', '3.2', sprintf( __( 'Please use %s to register your custom field type', 'awesome-support' ), '<code>field_type</code>' ) );

			switch ( $arguments['callback'] ) {

				case 'taxonomy';
					$arguments['field_type'] = 'taxonomy';
					$arguments['callback'] = '';
					break;

				case 'text':
					$arguments['field_type'] = 'text';
					$arguments['callback'] = '';
					break;

			}
		}

		/* Field with args */
		$option = array( 'name' => $name, 'args' => $arguments );

		$this->options[ $name ] = apply_filters( 'wpas_add_field', $option );

		return true;

	}

	/**
	 * Remove a custom field.
	 *
	 * @param  string $id ID of the field to remove
	 *
	 * @return void
	 *
	 * @since  3.0.0
	 */
	public function remove_field( $id ) {

		$fields = $this->options;

		if ( isset( $fields[ $id ] ) ) {
			unset( $fields[ $id ] );
		}

		$this->options = $fields;

	}

	/**
	 * Register all custom taxonomies.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function register_taxonomies() {

		$options         = $this->options;
		$this->remove_mb = array();

		foreach ( $options as $option ) {

			if ( 'taxonomy' == $option['args']['field_type'] ) {

				$name         = ! empty( $option['args']['label'] ) ? sanitize_text_field( $option['args']['label'] ) : ucwords( str_replace( array( '_', '-' ), ' ', $option['name'] ) );
				$plural       = ! empty( $option['args']['label_plural'] ) ? sanitize_text_field( $option['args']['label_plural'] ) : $name . 's';
				$column       = true === $option['args']['taxo_std'] ? true : false;
				$hierarchical = $option['args']['taxo_hierarchical'];

				$labels = array(
					'name'              => $plural,
					'singular_name'     => $name,
					'search_items'      => sprintf( __( 'Search %s', 'awesome-support' ), $plural ),
					'all_items'         => sprintf( __( 'All %s', 'awesome-support' ), $plural ),
					'parent_item'       => sprintf( __( 'Parent %s', 'awesome-support' ), $name ),
					'parent_item_colon' => sprintf( _x( 'Parent %s:', 'Parent term in a taxonomy where %s is dynamically replaced by the taxonomy (eg. "book")', 'awesome-support' ), $name ),
					'edit_item'         => sprintf( __( 'Edit %s', 'awesome-support' ), $name ),
					'update_item'       => sprintf( __( 'Update %s', 'awesome-support' ), $name ),
					'add_new_item'      => sprintf( __( 'Add New %s', 'awesome-support' ), $name ),
					'new_item_name'     => sprintf( _x( 'New %s Name', 'A new taxonomy term name where %s is dynamically replaced by the taxonomy (eg. "book")', 'awesome-support' ), $name ),
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

				if ( false === $option['args']['taxo_std'] ) {
					array_push( $this->remove_mb, $option['name'] );
				}

			}

		}

		/* Remove metaboxes that won't be used */
		if ( ! empty( $this->remove_mb ) ) {
			add_action( 'admin_menu', array( $this, 'remove_taxonomy_metabox' ) );
		}

	}

	/**
	 * Remove taxonomies metaboxes.
	 *
	 * In some cases taxonomies are used as select.
	 * Hence, we don't need the standard taxonomy metabox.
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function remove_taxonomy_metabox() {

		foreach ( $this->remove_mb as $key => $mb ) {
			remove_meta_box( $mb . 'div', 'ticket', 'side' );
		}

	}

	/**
	 * Return the list of fields
	 *
	 * @return array List of custom fields
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
	 *
	 * @param  boolean $core True if core fields should be counted as registered custom fields.
	 *
	 * @return boolean       True if custom fields are present, false otherwise
	 */
	public function have_custom_fields( $core = false ) {
		$fields = $this->get_custom_fields();
		$have   = false;

		foreach ( $fields as $key => $field ) {
			if ( false === boolval( $field['args']['core'] ) || true === $core && true === boolval( $field['args']['core'] ) ) {
				$have = true;
			}
		}

		return $have;
	}

	/**
	 * Add possible custom columns to tickets list.
	 *
	 * @param  array $columns List of default columns
	 *
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
			if ( 'taxonomy' == $field['args']['field_type'] && true === $field['args']['taxo_std'] ) {
				continue;
			}

			if ( true === $field['args']['show_column'] ) {
				$id            = $field['name'];
				$title         = wpas_get_field_title( $field );
				$custom[ $id ] = $title;
			}

		}

		/**
		 * Parse the old columns and add the new ones.
		 */
		foreach ( $columns as $col_id => $col_label ) {

			/* Merge all custom columns right before the date column */
			if ( 'date' == $col_id ) {
				$new = array_merge( $new, $custom );
			}

			$new[ $col_id ] = $col_label;

		}

		return $new;
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

			$new[ $column ] = $content;

		}

		return $new;

	}

	/**
	 * Manage custom columns content
	 *
	 * @param  string  $column  The name of the column to display
	 * @param  integer $post_id ID of the post being processed
	 *
	 * @return void
	 *
	 * @since  3.0.0
	 */
	public function custom_columns_content( $column, $post_id ) {

		$fields = $this->get_custom_fields();

		if ( isset( $fields[ $column ] ) ) {

			if ( true === $fields[ $column ]['args']['show_column'] ) {

				/* In case a custom callback is specified we use it */
				if ( function_exists( $fields[ $column ]['args']['column_callback'] ) ) {
					call_user_func( $fields[ $column ]['args']['column_callback'], $fields[ $column ]['name'], $post_id );
				}

				/* Otherwise we use the default rendering options */
				else {
					wpas_cf_value( $fields[ $column ]['name'], $post_id );
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

		$new    = array();
		$fields = $this->get_custom_fields();

		foreach ( $fields as $field ) {

			/* If CF is a regular taxonomy we don't handle it, WordPress does */
			if ( 'taxonomy' == $field['args']['field_type'] && true === $field['args']['taxo_std'] ) {
				continue;
			}

			if ( true === $field['args']['show_column'] && true === $field['args']['sortable_column'] ) {
				$id         = $field['name'];
				$new[ $id ] = $id;
			}

		}

		return array_merge( $columns, $new );

	}

	/**
	 * Reorder custom columns based on custom values.
	 *
	 * @param  object $query Main query
	 *
	 * @return void
	 *
	 * @since  3.0.0
	 */
	public function custom_column_orderby( $query ) {

		if ( ! isset( $_GET['post_type'] ) || 'ticket' !== $_GET['post_type'] ) {
			return;
		}

		$fields  = $this->get_custom_fields();
		$orderby = $query->get( 'orderby' );

		if ( ! empty( $orderby ) && array_key_exists( $orderby, $fields ) ) {

			if ( 'taxonomy' != $fields[ $orderby ]['args']['field_type'] ) {
				$query->set( 'meta_key', '_wpas_' . $orderby );
				$query->set( 'orderby', 'meta_value' );
			}

		}

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

				if ( true !== $fields[ $tax_slug ]['args']['filterable'] ) {
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

				if ( isset( $_GET[ $tax_slug ] ) ) {
					$args['selected'] = filter_input( INPUT_GET, $tax_slug, FILTER_SANITIZE_STRING );
				}

				wp_dropdown_categories( $args );

			}
		}

	}

	/**
	 * Add status dropdown in the filters bar.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public function status_filter() {

		global $typenow;

		if ( ('ticket' != $typenow ) || isset( $_GET['post_status'] ) ) {
			return;
		}

		$this_sort       = isset( $_GET['wpas_status'] ) ? filter_input( INPUT_GET, 'wpas_status', FILTER_SANITIZE_STRING ) : '';
		$all_selected    = ( '' === $this_sort ) ? 'selected="selected"' : '';
		$open_selected   = ( ! isset( $_GET['wpas_status'] ) && true === (bool) wpas_get_option( 'hide_closed' ) || 'open' === $this_sort ) ? 'selected="selected"' : '';
		$closed_selected = ( 'closed' === $this_sort ) ? 'selected="selected"' : '';
		$dropdown        = '<select id="wpas_status" name="wpas_status">';
		$dropdown        .= "<option value='' $all_selected>" . __( 'Any Status', 'awesome-support' ) . "</option>";
		$dropdown        .= "<option value='open' $open_selected>" . __( 'Open', 'awesome-support' ) . "</option>";
		$dropdown        .= "<option value='closed' $closed_selected>" . __( 'Closed', 'awesome-support' ) . "</option>";
		$dropdown        .= '</select>';

		echo $dropdown;

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
		if ( is_admin() && 'edit.php' == $pagenow && isset( $_GET['post_type'] ) && 'ticket' === $_GET['post_type'] && $query->is_main_query() ) {

			/* Get all custom fields */
			$fields = $this->get_custom_fields();

			/* Filter custom fields that are taxonomies */
			foreach ( $query->query_vars as $arg => $value ) {

				if ( array_key_exists( $arg, $fields ) && 'taxonomy' === $fields[ $arg ]['args']['field_type'] && true === $fields[ $arg ]['args']['filterable'] ) {

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
	 * Filter tickets by status.
	 *
	 * When filtering, WordPress uses the ID by default in the query but
	 * that doesn't work. We need to convert it to the taxonomy term.
	 *
	 * @since  3.0.0
	 *
	 * @param  object $query WordPress current main query
	 *
	 * @return void
	 */
	public function status_filter_by_status( $query ) {

		global $pagenow;

		/* Check if we are in the correct post type */
		if ( is_admin()
		     && 'edit.php' == $pagenow
		     && isset( $_GET['post_type'] )
		     && 'ticket' == $_GET['post_type']
		     && isset( $_GET['wpas_status'] )
		     && ! empty( $_GET['wpas_status'] )
		     && $query->is_main_query()
		) {

			$query->query_vars['meta_key']     = '_wpas_status';
			$query->query_vars['meta_value']   = sanitize_text_field( $_GET['wpas_status'] );
			$query->query_vars['meta_compare'] = '=';
		}

	}

	/**
	 * Display the custom fields on submission form.
	 *
	 * @since 3.2.0
	 * @return void
	 */
	public function submission_form_fields() {

		$fields = $this->get_custom_fields();

		if ( ! empty( $fields ) ) {

			foreach ( $fields as $name => $field ) {

				/* Do not display core fields */
				if ( true === $field['args']['core'] ) {
					continue;
				}

				$this_field = new WPAS_Custom_Field( $name, $field );
				$output     = $this_field->get_output();

				echo $output;

			}

		}

	}

	/**
	 * Trigger the custom fields save function upon front-end submission of a new ticket.
	 *
	 * @since 3.2.0
	 *
	 * @param int $ticket_id ID of the ticket that's just been added
	 *
	 * @return void
	 */
	public function frontend_submission( $ticket_id ) {
		$post = $_POST;
		$this->save_custom_fields( $ticket_id, $post, false );
	}

	/**
	 * Save all custom fields given in $data to the database.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $post_id   ID of the post being saved
	 * @param array $data      Array of data that might contain custom fields values.
	 * @param bool  $allow_log Whether or not to allow logging actions. If this is set to false and the custom field is
	 *                         set to true, $log has higher priority. I tis used to prevent logging on ticket creation.
	 *
	 * @return array Array of custom field / value saved to the database
	 */
	public function save_custom_fields( $post_id, $data = array(), $allow_log = true ) {

		/* We store all the data to log in here */
		$log = array();

		/* Store all fields saved to DB and the value saved */
		$saved = array();

		$fields = $this->get_custom_fields();

		/**
		 * wpas_save_custom_fields_before hook
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_save_custom_fields_before', $post_id );

		foreach ( $fields as $field_id => $field ) {

			/**
			 * All name attributes are prefixed with wpas_
			 * so we need to add it to get the real field ID.
			 */
			$field_form_id = "wpas_$field_id";

			/* Process core fields differently. */
			if ( true === $field['args']['core'] ) {

				if ( isset( $data[ $field_form_id ] ) ) {
					$this->save_core_field( $post_id, $field, $data[ $field_form_id ] );
				}

				continue;
			}

			/**
			 * Ignore fields in "no edit" mode.
			 *
			 * If we're on the admin and the custom field is set as
			 * "no edit" (by restricting the capability), then the field
			 * won't be passed in the $_POST, which as a result would have
			 * the field deleted.
			 *
			 * If the no edit mode is enabled for the current field, we simply ignore it.
			 */
			if ( is_admin() && ! current_user_can( $field['args']['capability'] ) ) {
				continue;
			}

			/**
			 * Get the custom field object.
			 */
			$custom_field = new WPAS_Custom_Field( $field_id, $field );

			if ( isset( $data[ $field_form_id ] ) ) {

				$value  = $custom_field->get_sanitized_value( $data[ $field_form_id ] );
				$result = $custom_field->update_value( $value, $post_id );

			} else {
				/**
				 * This is actually important as passing an empty value
				 * for custom fields that aren't set in the form allows
				 * for deleting values that aren't used from the database.
				 * An unchecked checkbox for instance will not be set
				 * in the form even though the value has to be deleted.
				 */
				$value  = '';
				$result = $custom_field->update_value( $value, $post_id );
			}

			if ( 1 === $result || 2 === $result ) {
				$saved[ $field['name'] ] = $value;
			}

			if ( true === $field['args']['log'] && true === $allow_log ) {

				/**
				 * If the custom field is a taxonomy we need to convert the term ID into its name.
				 *
				 * By checking if $result is different from 0 we make sure that the term actually exists.
				 * If the term didn't exist the save function would have seen it and returned 0.
				 */
				if ( 'taxonomy' === $field['args']['field_type'] && 0 !== $result ) {
					$term  = get_term( (int) $value, $field['name'] );
					$value = $term->name;
				}

				/**
				 * If the "options" parameter is set for this field, we assume it is because
				 * the field type has multiple options. In order to make is more readable,
				 * we try to replace the field value by the value label.
				 *
				 * This process is based on the fact that field types options always follow
				 * the schema option_id => option_label.
				 */
				if ( isset( $field['args']['options'] ) && is_array( $field['args']['options'] ) ) {

					/* Make sure arrays are still readable */
					if ( is_array( $value ) ) {

						$new_values = array();

						foreach ( $value as $val ) {
							if ( array_key_exists( $val, $field['args']['options'] ) ) {
								array_push( $new_values, $field['args']['options'][ $val ] );
							}
						}

						/* Only if all original values were replaced we update the $value var. */
						if ( count( $new_values ) === count( $value ) ) {
							$value = $new_values;
						}

						$value = implode( ', ', $value );

					} else {

						if ( array_key_exists( $value, $field['args']['options'] ) ) {
							$value = $field['args']['options'][ $value ];
						}

					}

				}

				$tmp = array(
					'action'   => '',
					'label'    => wpas_get_field_title( $field ),
					'value'    => $value,
					'field_id' => $field['name']
				);

				switch ( (int) $result ) {

					case 1:
						$tmp['action'] = 'added';
						break;

					case 2:
						$tmp['action'] = 'updated';
						break;

					case 3:
						$tmp['action'] = 'deleted';
						break;

				}

				/* Only add this to the log if something was done to the field value */
				if ( ! empty( $tmp['action'] ) ) {
					$log[] = $tmp;
				}

			}

		}

		/**
		 * Log the changes if any.
		 */
		if ( ! empty( $log ) ) {
			wpas_log( $post_id, $log );
		}

		/**
		 * wpas_save_custom_fields_before hook
		 *
		 * @since  3.0.0
		 */
		do_action( 'wpas_save_custom_fields_after', $post_id );

		return $saved;

	}

	/**
	 * Save the core fields.
	 *
	 * Core fields are processed differently and won't use the same
	 * saving function as the standard custom fields of the same type.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $post_id ID of the post being saved
	 * @param array $field   Field array
	 * @param mixed $value   Field new value
	 *
	 * @return void
	 */
	public function save_core_field( $post_id, $field, $value ) {

		switch ( $field['name'] ) {

			case 'assignee':

				if ( $value !== get_post_meta( $post_id, '_wpas_assignee', true ) ) {
					wpas_assign_ticket( $post_id, $value, $field['args']['log'] );
				}

				break;

		}

	}

	/**
	 * Checks required custom fields.
	 *
	 * This function is hooked on the filter wpas_before_submit_new_ticket_checks
	 * through the parent class. It checks all required custom fields
	 * and if they were correctly filled. If one or more required field(s) is/are
	 * missing then the submission process is stopped and an error message is returned.
	 *
	 * @since  3.0.0
	 *
	 * @param array $data Array of data containing the custom fields to check for required attribute
	 *
	 * @return mixed True if no error or a WP_Error otherwise
	 */
	public function check_required_fields( $go, $data = array() ) {

		if ( empty( $data ) && ! empty( $_POST ) ) {
			$data = $_POST;
		}

		$result = $this->is_field_missing( $data );

		return false === $result ? true : $result;

	}

	/**
	 * Makes sure no required custom field is missing from the data passed.
	 *
	 * @since 3.2.0
	 *
	 * @param array $data Array of data to check
	 *
	 * @return bool|WP_Error False if no field is missing, WP_Error with the list of missing fields otherwise
	 */
	public function is_field_missing( $data = array() ) {

		if ( empty( $data ) && ! empty( $_POST ) ) {
			$data = $_POST;
		}

		$fields = $this->get_custom_fields();

		/* Set the result as true by default, which is the "green light" value */
		$result = false;

		foreach ( $fields as $field_id => $field ) {

			/**
			 * Get the custom field object.
			 */
			$custom_field = new WPAS_Custom_Field( $field_id, $field );

			/* Prepare the field name as used in the form */
			$field_name = $custom_field->get_field_id();

			if ( true === $field['args']['required'] && false === $field['args']['core'] ) {

				if ( ! isset( $data[ $field_name ] ) || empty( $data[ $field_name ] ) ) {

					/* Get field title */
					$title = ! empty( $field['args']['title'] ) ? $field['args']['title'] : wpas_get_title_from_id( $field['name'] );

					/* Add the error message for this field. */
					if ( ! is_object( $result ) ) {
						$result = new WP_Error( 'required_field_missing', sprintf( __( 'The field %s is required.', 'awesome-support' ), "<a href='#$field_name'><code>$title</code></a>", array( 'errors' => $field_name ) ) );
					} else {
						$result->add( 'required_field_missing', sprintf( __( 'The field %s is required.', 'awesome-support' ), "<code>$title</code>", array( 'errors' => $field_name ) ) );
					}

				}
			}

		}

		return $result;

	}

}
