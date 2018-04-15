<?php
/**
 * Awesome Support.
 *
 * @package   Awesome Support/Custom Fields
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
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

		if ( ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {

			if( ! is_admin() ) {

				/* Check for required fields and possibly block the submission. */
				add_filter( 'wpas_before_submit_new_ticket_checks', array( $this, 'check_required_fields' ) );

				/* Save the custom fields. */
				add_action( 'wpas_open_ticket_before_assigned', array( $this, 'frontend_submission' ), 10, 2 );

			}
		}

	}

	/**
	 * Register and enqueue the select2 assets
	 *
	 * This method will be called if the select2 parameter is passed with a custom field (could be a select or a
	 * taxonomy field).
	 *
	 * @since 3.3
	 * @return void
	 */
	public function enqueue_select2_assets() {

		global $post;

		// This will usually be packaged with all other components which is why it's not registered with the rest
		wp_register_script( 'wpas-select2-component', WPAS_URL . 'assets/public/js/component_select2.js', array( 'wpas-select2' ), '4.0.0', true );

		$ticket_submit = wpas_get_option( 'ticket_submit' );

		if ( ! is_array( $ticket_submit ) ) {
			$ticket_submit = (array) $ticket_submit;
		}

		if ( ! is_object( $post ) || ! in_array( $post->ID, $ticket_submit ) ) {
			return;
		}

		if ( false === wp_style_is( 'wpas-select2', 'enqueued' ) ) {
			wp_enqueue_style( 'wpas-select2' );
		}

		if ( false === wp_script_is( 'wpas-select2', 'enqueued' ) ) {
			wp_enqueue_script( 'wpas-select2' );
		}

		if ( false === wp_script_is( 'wpas-select2-component', 'enqueued' ) ) {
			wp_enqueue_script( 'wpas-select2-component' );
		}

	}

	/**
	 * Register and enqueue the datepicker assets
	 *
	 * This method will be called if the field_type parameter date so we can enqueue assets
	 * included in WP.
	 *
	 * @since 4.0.0
	 * @return void
	 */
	public function enqueue_datepicker_assets() {

		global $post;

		// This will usually be packaged with all other components which is why it's not registered with the rest
		wp_register_script( 'wpas-datepicker-component', WPAS_URL . 'assets/public/js/component_datepicker.js', array( 'wpas-date' ), '4.0.0', true );

		$ticket_submit = wpas_get_option( 'ticket_submit' );

		if ( ! is_array( $ticket_submit ) ) {
			$ticket_submit = (array) $ticket_submit;
		}

		if ( ! is_object( $post ) || ! in_array( $post->ID, $ticket_submit ) ) {
			return;
		}

		if ( false === wp_script_is( 'jquery-ui-datepicker', 'enqueued' ) ) {
			// Load the datepicker script (pre-registered in WordPress).
            wp_enqueue_script( 'jquery-ui-datepicker' );
		}

		if ( false === wp_script_is( 'wpas-datepicker-component', 'enqueued' ) ) {
			wp_enqueue_script( 'wpas-datepicker-component' );
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

		// If date field we load the required assets
		if ( isset( $arguments['field_type'] ) && 'date-field' === $arguments['field_type'] ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_datepicker_assets' ) );
		}

		// If select2 is enabled we load the required assets
		if ( isset( $arguments['select2'] ) && true === $arguments['select2'] ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_select2_assets' ) );
		}

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
				$rewrite	  = ! empty( $option['args']['rewrite'] ) && ! empty( $option['args']['rewrite']['slug'] ) ? sanitize_text_field( $option['args']['rewrite']['slug'] ) : $name;				
				
				$hierarchical = $option['args']['taxo_hierarchical'];
				
				$taxo_manage_terms 	= $option['args']['taxo_manage_terms'];
				$taxo_edit_terms 	= $option['args']['taxo_edit_terms'];
				$taxo_delete_terms 	= $option['args']['taxo_delete_terms'];
				$taxo_assign_terms 	= $option['args']['taxo_assign_terms'];				
				
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
					'rewrite'           => array( 'slug' => $rewrite ),
					'capabilities'      => array(
						'manage_terms' => $taxo_manage_terms,
						'edit_terms'   => $taxo_edit_terms,
						'delete_terms' => $taxo_delete_terms,
						'assign_terms' => $taxo_assign_terms
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
	 * Display the custom fields on submission form.
	 * This function is used to display the custom fields on both
	 * the front-end and back-end.  Probably should have a 
	 * back-end only version at some point.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function submission_form_fields() {

		$fields = $this->get_custom_fields();
		$fields = $this->sort_custom_fields( $fields ) ;		

		if ( ! empty( $fields ) ) {

			// If we're painting the custom fields on the front-end wrap them in a bootstrap container class.		
			if ( false === is_admin() ) {
				?> 
				<div class="wpas-submission-form-inside-after-subject container"> 
				<?php
			}
		
			foreach ( $fields as $name => $field ) {

				/* Do not display core fields */
				if ( true === $field['args']['core'] ) {
					continue;
				}
				
				/* Do not display if hide_front_end attribute is true */				
				if ( true === $field['args']['hide_front_end'] ) {
					continue;
				}
				
				/* Do not display if backend display type is set to custom */
				If ( 'custom' === $field['args']['backend_display_type'] ) {
					continue;
				}
				
				/* Do not display if backend_only attribute is true */				
				if ( true === $field['args']['backend_only'] ) {
					continue;
				}				
				
				$this_field = new WPAS_Custom_Field( $name, $field );
				$output     = $this_field->get_output();
				
				/* Add the pre-render action hook */
				if ( ! empty( $field['args']['pre_render_action_hook_fe'] ) ) {
					do_action( $field['args']['pre_render_action_hook_fe'] ) ;
				}
				
				/* Render the field */
				echo $output;
				
				/* add the post-render action hook */
				if ( ! empty( $field['args']['post_render_action_hook_fe'] ) ) {
					do_action( $field['args']['post_render_action_hook_fe'] ) ;
				}
			}
			
			// If we're painting the custom fields on the front-end wrap them in a bootstrap container class (in this case, just the ending div tag to match the one we added above)
			if ( false === is_admin() ) {
				?> 
				</div> 
				<?php
			}
		}

	}
	
	/**
	 * Sort custom fields array
	 *
	 * @since 4.4.0
	 *
	 * @return array
	 */	
	public function sort_custom_fields( $fields ) {
		
		array_multisort(array_map(function($element) {
			return $element['args']['order'];
			}, $fields), $fields );
			
		return $fields ;
		
	}	

	/**
	 * Display the backend only custom fields in whatever metabox template it is called from
	 *
	 * @since 3.3.5
	 * @return void
	 */
	public function show_backend_custom_form_fields() {

		$fields = $this->get_custom_fields();

		if ( ! empty( $fields ) ) {

			foreach ( $fields as $name => $field ) {

				If  ( ( true === $field['args']['backend_only'] ) && ( 'custom' <> $field['args']['backend_display_type'] ) ) {
				
					$this_field = new WPAS_Custom_Field( $name, $field );
					$output     = $this_field->get_output();

					echo $output;
				}

			}

		}

	}
	
	
	/**
	 * Display just a single custom field on submission form.
	 * This can be more efficient but not sure how to do
	 * the array search properly so ended up in an inefficient loop.
	 *
	 * @since 3.3.5
	 * @return void
	 */
	public function display_single_field( $cffieldname ) {

		$fields = $this->get_custom_fields();
		
		foreach ( $fields as $name => $field ) {

			If ( $cffieldname === $name ) {
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
	 * @todo if you update this functionality, be sure to do the same in the Rest-API plugin in /includes/API/Tickets.php on line 648
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

			/* Allow custom save_callback (if specified) to modify $value if needed */
			if( is_array( $result ) ) {
				$value  = $custom_field->get_sanitized_value( $result[ 'value' ] );

				/* Validate return $result is int and valid */
				if( (int) $result[ 'result' ] === $result[ 'result' ]
					&& 0 <= $result [ 'result' ] && 4 >= $result[ 'result' ]
				) {
					$result = $result[ 'result' ];
				} else {
					/* Invalid $result returned from custom save_callback */
					$result = 0;
				}
			}

			if ( 1 === $result || 2 === $result ) {
				$saved[ $field['name'] ] = $value;
				do_action('wpas_custom_field_updated', $field_id ,$post_id, $value);
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
