<?php

	class WPAS_Custom_Field {

		/**
		 * ID of the custom field.
		 *
		 * @since 3.2.0
		 * @var $field_id string
		 */
		public $field_id = '';

		/**
		 * The custom field declaration.
		 *
		 * @since 3.2.0
		 * @var $field array
		 */
		public $field = array();

		/**
		 * The custom field arguments.
		 *
		 * @since 3.2.0
		 * @var $field_args array
		 */
		public $field_args = array();

		/**
		 * Field type.
		 *
		 * @since 3.2.0
		 * @var $field_type string
		 */
		public $field_type;

		/**
		 * ID of the post this custom field is attached to.
		 *
		 * @since 3.2.0
		 * @var $post_id integer
		 */
		protected $post_id;

		/**
		 * The field HTML markup.
		 *
		 * @since 3.2.0
		 * @var $output string
		 */
		protected $output;

		/**
		 * Defines if the custom field uses the latest class or still uses the deprecated registration method.
		 *
		 * @since 3.2.0
		 * @var $legacy bool
		 */
		protected $legacy;

		/**
		 * Name of the custom field type class.
		 *
		 * @since 3.2.0
		 * @var $class_name string
		 */
		public $class_name;

		/**
		 * Constructor.
		 *
		 * @param $field_id string The field ID
		 * @param $field    array The field to process.
		 *
		 * @since 3.2.0
		 */
		public function __construct( $field_id = '', $field = array() ) {

			/**
			 * Set the field arguments just in case the class is used with
			 * the custom field name only (this can happen). This is
			 * basically a fallback to avoid a PHP notice.
			 */
			if ( empty( $field ) ) {
				$field = array( 'name' => $field_id, 'args' => $this->get_field_defaults() );
			}

			$this->field      = $field;
			$this->field_id   = sanitize_text_field( $field_id );
			$this->field_type = $this->field[ 'args' ][ 'field_type' ];
			$this->field_args = $this->field[ 'args' ];

			/* Set the legacy mode */
			$this->legacy = ! empty( $field[ 'args' ][ 'callback' ] ) ? true : false;

			/**
			 * Get the ID of the post the custom field relates to.
			 */
			$this->post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

		}

		/**
		 * Returns the default arguments for a custom field.
		 *
		 * @since 3.2.0
		 */
		public static function get_field_defaults() {

			/* Default arguments */
			$defaults = array(
				'field_type'            => 'text',
				// Type of custom field to display
				'core'                  => false,
				// Is this a custom fields that belongs to the plugin core
				'required'              => false,
				// Is this field required for front-end submission
				'log'                   => false,
				// Should the content updates of this field be logged in the system
				'capability'            => 'create_ticket',
				// Required capability for this field
				'sanitize'              => 'sanitize_text_field',
				// Sanitize callback for the field value
				'save_callback'         => false,
				// Saving callback if a specific saving method is required
				'show_column'           => false,
				// Show field content in the tickets list & in the admin
				'column_callback'       => 'wpas_cf_value',
				// Column callback function
				'sortable_column'       => false,
				// Not compatible with taxonomies
				'filterable'            => true,
				// Used for taxonomies only
				'title'                 => '',
				// Nicely formatted title for this field
				'placeholder'           => '',
				// Placeholder to display in the submission form
				'desc'                  => '',
				// Helper description for the field
				// @since 3.2.0
				'html5_pattern'         => '',
				// Adds a validation pattern following the HTML5 standards
				// @since 3.2.2
				'default'               => '',
				// Field default value
				// @since 3.3
				'column_attributes'     => array(),
				// User-defined attributes to add to the list table columns (required show_column to be true)
				// @since 3.3.5
				'hide_front_end'        => false,
				// Hide the field from being rendered by submission_form_fields() which results in it being hidden from the front end?
				// Setting to true also means that the backend has to have its own rendering logic so be careful.
				'backend_only'          => false,
				// If set to true, will only show on backend AND will show in separate metabox (no custom rendering mandatory unlike the hide_front_end option above.)
				'backend_display_type'  => 'std',
				// If set to "std" then do nothing special - let the default display routines determine which metabox to show the field in.
				// Set to custom to remove from all default metaboxes - something else will have to handle the display.
				/* The following parameters are used for taxonomies only. */
				'taxo_std'              => false,
				// For taxonomies, should it behave like a standard WordPress taxonomy
				'label'                 => '',
				'label_plural'          => '',
				'taxo_hierarchical'     => true,
				'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
				'taxo_manage_terms' 	=> 'create_ticket',
				'taxo_edit_terms'   	=> 'settings_tickets',
				'taxo_delete_terms' 	=> 'settings_tickets',
				'taxo_assign_terms' 	=> 'create_ticket',

				// @since 3.3.5
				'readonly'              => false,
				// Readonly field by default. Can be updated in custom save_callback
				
				// @since 4.1.0
				// Whether or not to show the field in the front-end my-tickets list screen after a ticket is submitted.
				// Sometimes when you get a ton of custom fields, having them all in the list is an issue.
				'show_frontend_list'	=> true,
				
				// @since 4.1.0
				// Whether or not to show the field in the front-end detail screen (header area) for existing tickets.
				// Sometimes when you get a ton of custom fields, having them all in the list is an issue.
				'show_frontend_detail'	=> true,				
				
				// @since 4.3.0
				// Hold extra wrapper classes/ids - applies to front-end only
				'extra_wrapper_css_classes'	=> '' ,

				// @since 4.3.0
				// Hold extra field classes/ids - applies to front-end only
				'extra_field_css_classes'	=> '' ,
				
				// @since 4.3.0
				// Hold extra wrapper classes/ids - applies to back-end only
				'extra_wrapper_css_classes_be'	=> '' ,

				// @since 4.3.0
				// Hold extra field classes/ids - applies to back-end only
				'extra_field_css_classes_be'	=> '' ,				
				
				// @since 4.3.0
				// Hold extra label classes/ids - this one not currently used - for possible future use only.
				'extra_label_css_classes'	=> '' ,
				
				// @since 4.3.0
				// Start or end front-end bootstrap row with this field?
				'boot_strap_row_fe_start'	=> false ,
				'boot_strap_row_fe_end'		=> false ,

				// @since 4.3.0
				// Place this field in a bootstrap column?
				'boot_strap_column_fe'	=> false ,
				
				// @since 4.4.0
				// The sort order of the field - used only by the custom-fields add-on!
				'order'	=> '99999',
				
				// @since 4.4.0
				// Action hook to declare just before rendering the field on the front-end
				'pre_render_action_hook_fe' => '',
				
				// @since 4.4.0
				// Action hook to declare just after rendering the field on the front-end
				'post_render_action_hook_fe' => '',
				
				// @since 4.4.0
				// Action hook to declare just before rendering the field on the back-end
				// Future use only since we don't have a back-end only rendering function yet.
				// Use the front-end hook above for everything for now.
				'pre_render_action_hook_be' => '',
				
				// @since 4.4.0
				// Action hook to declare just after rendering the field on the back-end
				// Future use only since we don't have a back-end only rendering function yet.
				// Use the front-end hook above for everything for now.				
				'post_render_action_hook_be' => '',								

			);

			return $defaults;

		}

		/**
		 * Get the value of a field argument
		 *
		 * @since 3.2.10
		 *
		 * @param string $arg Argument ID
		 * @param mixed $default Default value ot return
		 *
		 * @return mixed
		 */
		public function get_field_arg( $arg, $default = '' ) {

			$value = $default;

			if ( array_key_exists( $arg, $this->field_args ) ) {
				$value = $this->field_args[ $arg ];
			}

			return $value;

		}

		/**
		 * Get the field class name.
		 *
		 * @since 3.2.0
		 * @return string The class name
		 */
		public function get_class_name() {

			if ( ! isset( $this->class_name ) ) {
				$type             = str_replace( ' ', '_', ucwords( str_replace( array(
					'-',
					'_',
				), ' ', $this->field_type ) ) );
				$this->class_name = "WPAS_CF_$type";
			}

			return $this->class_name;

		}

		/**
		 * Check if the field type class exists and loads it if possible.
		 *
		 * @since 3.2.0
		 * @return bool Whether or not the class was loaded
		 */
		protected function require_field_type_class() {

			$field_class_path = WPAS_PATH . "includes/custom-fields/field-types/class-cf-{$this->field['args']['field_type']}.php";

			if ( file_exists( $field_class_path ) ) {

				require_once( $field_class_path );

				return true;

			} else {
				return false;
			}

		}

		/**
		 * Get the field ID.
		 *
		 * If the ID is require during the saving process, an underscore is added
		 * to the ID in order to make the post meta invisible in the GUI.
		 *
		 * @param $save boolean Whether the ID is used in a saving process.
		 *
		 * @since 3.2.0
		 * @return string The ID used in form name attribute.
		 */
		public function get_field_id( $save = false ) {

			$id = 'wpas_' . $this->field_id;

			if ( true === $save ) {
				$id = "_$id";
			}

			return $id;

		}

		/**
		 * Get the field title.
		 *
		 * Get the field title and sanitize it.
		 *
		 * @since 3.2.0
		 * @return string Sanitized field title
		 */
		public function get_field_title() {

			$title = '';

			if ( isset( $this->field[ 'args' ][ 'title' ] ) ) {
				$title = $this->field[ 'args' ][ 'title' ];
			} elseif ( isset( $this->field[ 'args' ][ 'label' ] ) ) {
				$title = $this->field[ 'args' ][ 'label' ];
			}

			return esc_attr( strip_tags( $title ) );

		}

		/**
		 * Get the field label.
		 *
		 * @since 3.2.0
		 * @return string The field label
		 */
		public function get_field_label() {

			if ( isset( $this->field[ 'args' ][ 'label' ] ) && ! empty( $this->field[ 'args' ][ 'label' ] ) ) {
				$label = esc_attr( strip_tags( $this->field[ 'args' ][ 'label' ] ) );
			} else {
				$label = $this->get_field_title();
			}

			return apply_filters( 'wpas_cf_field_label', $label, $this->field, $label );
		}

		/**
		 * Get the custom field value.
		 *
		 * @since 3.2.0
		 *
		 * @param $default mixed The default value to return if no value is found in the database
		 * @param $post_id int ID of the post the custom field is attached to
		 *
		 * @return mixed Field value
		 */
		public function get_field_value( $default = '', $post_id = 0 ) {

			$post_id = 0 !== $post_id ? $post_id : $this->post_id;
			$value   = '';

			if ( 'taxonomy' === $this->field_type ) {

				$current = get_the_terms( $post_id, $this->field_id );

				if ( is_array( $current ) ) {
					foreach ( $current as $term ) {
						$value = $term->slug;
					}
				}

			} else {
				$value = get_post_meta( $post_id, $this->get_field_id( true ), true );
			}

			return ! empty( $value ) ? $this->get_sanitized_value( $value ) : $default;

		}

		/**
		 * This is used to pre-populate a field.
		 *
		 * The method checks for URL vars and values
		 * possibly saved in session.
		 *
		 * @since 3.2.0
		 * @return mixed Field value
		 */
		public function populate() {

			$value = $this->get_field_value();

			if ( empty( $value ) ) {

				$queried_value = filter_input( INPUT_GET, $this->get_field_id(), FILTER_SANITIZE_STRING );

				if ( ! empty( $queried_value ) ) {
					$value = is_array( $_GET[ $this->get_field_id() ] )
						? filter_input( INPUT_GET, $this->get_field_id(), FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY )
						: filter_input( INPUT_GET, $this->get_field_id(), FILTER_SANITIZE_STRING );
				}

				$fields = WPAS()->session->get( 'submission_form' );

				if ( isset( $fields ) && is_array( $fields ) && array_key_exists( $this->get_field_id(), $fields ) ) {
					$value = $this->get_sanitized_value( $fields[ $this->get_field_id() ] );
				}

				if ( ! empty( $this->field_args[ 'default' ] ) ) {
					$value = $this->get_sanitized_value( $this->field_args[ 'default' ] );
				}

			}

			return $value;

		}

		/**
		 * Get the custom field wrapper markup.
		 *
		 * The wrapper markup has a default value that can be overwritten
		 * by filtering the content.
		 *
		 * @since 3.2.0
		 * @return string The wrapper markup
		 */
		protected function get_wrapper_markup() {

			$class_name = $this->get_class_name();
			$wrapper_id = "{$this->get_field_id()}_wrapper";

			if ( class_exists( $class_name ) && method_exists( $class_name, 'wrapper' ) ) {
				$instance = new $class_name( $this->field_id, $this->field );
				$default  = $instance->wrapper();
			} else {
				$default = sprintf( '<div class="%s" id="%s">{{field}}</div>', $this->get_wrapper_class(), $wrapper_id );
			}

			return apply_filters( 'wpas_cf_wrapper_markup', $default, $this->field, $this->get_wrapper_class(), $wrapper_id );

		}

		/**
		 * Get the field HTML markup.
		 *
		 * @since 3.2.0
		 * @return string The field final markup
		 */
		protected function get_field_markup() {

			$field    = '';
			$callback = $this->field[ 'args' ][ 'field_type' ]; // Used for backwards compatibility

			/**
			 * Load user function.
			 *
			 * This method was deprecated in 3.2.0 but we keep it active
			 * for backward compatibility with custom fields created prior
			 * to this change.
			 *
			 * If you're creating custom fields with version 3.2.0, you should
			 * use the field_type argument instead.
			 *
			 * We need ot run this first because there will always be a fallback to text
			 * for the field_type argument.
			 */
			if ( ! empty( $callback ) && function_exists( $callback ) ) {

				ob_start();
				call_user_func( $callback, $field );

				$field = ob_get_contents();

				ob_end_clean();

				return $field;

			}

			if ( class_exists( $this->get_class_name() ) ) {

				$class_name = $this->get_class_name();
				$error      = false;

				if ( method_exists( $class_name, 'display' ) ) {

					/* Instantiate the field type class */
					$instance = new $class_name( $this->field_id, $this->field );

					if ( is_admin() ) {
						if ( ! current_user_can( $this->field[ 'args' ][ 'capability' ] ) && method_exists( $instance, 'display_no_edit' ) ) {
							$field = $instance->display_no_edit();
						} elseif ( current_user_can( $this->field[ 'args' ][ 'capability' ] ) && method_exists( $instance, 'display_admin' ) ) {
							$field = apply_filters( 'wpas_cf_display_admin_markup', $instance->display_admin(), $this->field, $this->populate() );
						} else {
							$field = $instance->display();
						}
					} else {
						$field = $instance->display();
					}

				} else {
					$field = '<!-- ' . __( 'The custom field class does not contain the mandatory method "display"', 'awesome-support' ) . ' -->';
					$error = true;
				}

			} /* In case the field type / callback function does not exist */
			else {
				$field = '<!-- ' . __( 'The type of custom field you are trying to use does not exist', 'awesome-support' ) . ' -->';
				$error = true;
			}

			return false === $error ? $this->process_field_markup( apply_filters( 'wpas_cf_field_markup', $field, $this->populate(), $this->field ) ) : $field;

		}

		/**
		 * Takes a custom field markup and processes it to add the field attributes.
		 *
		 * @param $field  string The raw field to process
		 *
		 * @since 3.2.0
		 * @return string Field final markup
		 */
		protected function process_field_markup( $field = '' ) {

			if ( true === $this->legacy ) {
				return '';
			}

			$atts        = array();
			$label_atts  = array();
			$label_class = isset( $this->field[ 'args' ][ 'label_class' ] ) ? $this->field[ 'args' ][ 'label_class' ] : '';
			$label_class = apply_filters( 'wpas_cf_field_label_class', $label_class, $this->field );

			/* Add the field ID */
			array_push( $atts, "id='{$this->get_field_id()}'" );
			array_push( $label_atts, "for='{$this->get_field_id()}'" );

			/* Add the field class */
			array_push( $atts, "class='{$this->get_field_class()}'" );

			if ( ! empty( $label_class ) ) {
				array_push( $label_atts, "class='{$label_class}'" );
			}

			/* Add the field name */
			array_push( $atts, "name='{$this->get_field_id()}'" );

			/* Add the field placeholder */
			if ( ! empty( $this->field[ 'args' ][ 'placeholder' ] ) ) {
				$placeholder = wp_strip_all_tags( $this->field[ 'args' ][ 'placeholder' ] );
				array_push( $atts, "placeholder='$placeholder'" );
			}

			/* Add the field HTML5 pattern */
			if ( ! empty( $this->field[ 'args' ][ 'html5_pattern' ] ) ) {
				array_push( $atts, "pattern='{$this->field['args']['html5_pattern']}'" );
			}

			/* Add the required attribute */
			if ( true === $this->field[ 'args' ][ 'required' ] ) {
				array_push( $atts, 'required' );
			}

			/* Add the readonly attribute */
			if ( ! empty( $this->field[ 'args' ][ 'readonly' ] ) && true === $this->field[ 'args' ][ 'readonly' ] ) {
				/* Allow filter to change readonly setting */
				if ( true === apply_filters( 'wpas_cf_field_markup_readonly', $this->field[ 'args' ][ 'readonly' ], $this->field ) ) {
					array_push( $atts, 'readonly' );
				}
			}

			$field = str_replace( '{{atts}}', implode( ' ', apply_filters( 'wpas_cf_field_atts', $atts, $field, $this->field ) ), $field );
			$field = str_replace( '{{label_atts}}', implode( ' ', $label_atts ), $field );
			$field = str_replace( '{{label}}', $this->get_field_label(), $field );

			return apply_filters( 'wpas_cf_field_markup_processed', $field, $this->field );

		}

		/**
		 * Get field container class along with any user defined extra classes.
		 *
		 * @since  3.2.0
		 *
		 * @param  array $class Extra classes to pass to the function
		 *
		 * @return string             The class tag with appropriate classes
		 */
		public function get_wrapper_class( $class = array() ) {

			/**
			 * Set the classes array with the default class.
			 *
			 * @var $classes array
			 */
			$classes = array(
				'wpas-form-group',
			);
			
			/* Add in any user defined classes if any (front-end) */
			if ( ! empty( $this->field[ 'args' ][ 'extra_wrapper_css_classes' ] )  && false === is_admin() ) {
				$classes[] = $this->field[ 'args' ][ 'extra_wrapper_css_classes' ] ;
			}

			/* Add in any user defined classes if any (back-end) */
			if ( ! empty( $this->field[ 'args' ][ 'extra_wrapper_css_classes_be' ] )  && true === is_admin() ) {
				$classes[] = $this->field[ 'args' ][ 'extra_wrapper_css_classes_be' ] ;
			}
			
			/* If this field should go into its own bootstrap column add in that class name here */
			if ( ! empty( $this->field[ 'args' ][ 'boot_strap_column_fe' ] ) && true === $this->field[ 'args' ][ 'boot_strap_column_fe' ] && false === is_admin() ) {
				$classes[] = 'col' ;
			}

			/* Ok, now we can get the classname for the wrapper for the field */
			$class_name = $this->get_class_name();

			if ( class_exists( $class_name ) && property_exists( $class_name, 'default_wrapper_class' ) ) {
				$reflection = new ReflectionClass( $class_name ); // PHP 5.2 doesn't let us access the static property
				array_push( $classes, $reflection->getStaticPropertyValue( 'default_wrapper_class', strval( $class_name ) ) );
			}

			/* Add the error class if needed */
			if ( isset( $_SESSION[ 'wpas_submission_error' ] ) && is_array( $_SESSION[ 'wpas_submission_error' ] ) && in_array( $this->get_field_id(), $_SESSION[ 'wpas_submission_error' ] ) ) {
				array_push( $classes, 'has-error' );
			}

			/* Filter the final list */
			$classes = apply_filters( 'wpas_cf_wrapper_class', $classes, $this->field );

			/**
			 * Possibly add the extra classes.
			 *
			 * We do this after filtering the classes in order to avoid
			 * someone erasing extra classes that would have been added manually.
			 * If really someone wished to erase extra classes for some reason,
			 * a filter is available to force this action.
			 */
			if ( ! empty( $class ) ) {

				/**
				 * Whether or not to force erase extra classes.
				 *
				 * This filter is to be used in case someone wants to get rid
				 * of the possible extra classes a dev could have added
				 * while customizing the output of custom fields.
				 *
				 * This should be used in very rare cases as manually added classes
				 * should be kept with no modification in most cases.
				 *
				 * @var $erase bool
				 */
				$erase = apply_filters( 'wpas_cf_wrapper_class_force_erase_extra', false );

				if ( false === $erase ) {
					$classes = array_merge( $classes, $class );
				}

			}

			return implode( ' ', $classes );

		}

		/**
		 * Get field class.
		 *
		 * @since  3.2.0
		 *
		 * @param  array $class Extra classes to pass to the function
		 *
		 * @return string             The class tag with appropriate classes
		 */
		public function get_field_class( $class = array() ) {

			/**
			 * Set the classes array with the default class along with any user defined extra classes.
			 *
			 * @var $classes array
			 */
			$classes = array(
				'wpas-form-control',
			);
			
			/* Add in any user defined classes if any (front-end) */
			if ( ! empty( $this->field[ 'args' ][ 'extra_field_css_classes' ] ) && false === is_admin() ) {
				$classes[] = $this->field[ 'args' ][ 'extra_field_css_classes' ] ;
			}
			
			/* Add in any user defined classes if any (back-end) */
			if ( ! empty( $this->field[ 'args' ][ 'extra_field_css_classes_be' ] ) && true === is_admin() ) {
				$classes[] = $this->field[ 'args' ][ 'extra_field_css_classes_be' ] ;
			}			

			$class_name = $this->get_class_name();

			if ( class_exists( $class_name ) && property_exists( $class_name, 'default_field_class' ) ) {
				$reflection = new ReflectionClass( $class_name ); // PHP 5.2 doesn't let us access the static property
				array_push( $classes, $reflection->getStaticPropertyValue( 'default_field_class', strval( $class_name ) ) );
			}

			/* Filter the final list */
			$classes = apply_filters( 'wpas_cf_field_class', $classes, $this->field );

			/**
			 * Possibly add the extra classes.
			 *
			 * We do this after filtering the classes in order to avoid
			 * someone erasing extra classes that would have been added manually.
			 * If really someone wished to erase extra classes for some reason,
			 * a filter is available to force this action.
			 */
			if ( ! empty( $class ) ) {

				/**
				 * Whether or not to force erase extra classes.
				 *
				 * This filter is to be used in case someone wants to get rid
				 * of the possible extra classes a dev could have added
				 * while customizing the output of custom fields.
				 *
				 * This should be used in very rare cases as manually added classes
				 * should be kept with no modification in most cases.
				 *
				 * @var $erase bool
				 */
				$erase = apply_filters( 'wpas_cf_field_class_force_erase_extra', false );

				if ( false === $erase ) {
					$classes = array_merge( $classes, $class );
				}

			}

			return implode( ' ', $classes );

		}

		/**
		 * Get the custom field description if any.
		 *
		 * @since 3.2.0
		 * @return string The field description
		 */
		public function get_field_description() {

			$description = '';

			/**
			 * Possible classes for the description block.
			 *
			 * @var $classes array
			 */
			$classes = apply_filters( 'wpas_cf_field_description_class', array(
				'backend'  => 'description',
				'frontend' => 'wpas-help-block',
			), $this->field );

			if ( isset( $this->field[ 'args' ][ 'desc' ] ) && ! empty( $this->field[ 'args' ][ 'desc' ] ) ) {
				$class       = is_admin() ? $classes[ 'backend' ] : $classes[ 'frontend' ];
				$description = sprintf( '<p class="%s">%s</p>', $class, wp_kses_post( $this->field[ 'args' ][ 'desc' ] ) );
			}

			return apply_filters( 'wpas_cf_description_markup', $description );

		}

		/**
		 * Returns the custom field complete HTML markup.
		 *
		 * @since 3.2.0
		 * @return string Final HTML markup
		 */
		public function get_output() {

			$this->require_field_type_class();

			$wrapper     = $this->get_wrapper_markup();
			
			/* Add a beginning DIV if this field marks the start of a bootstrap row and we're displaying on the front-end... */	
			if ( ! empty( $this->field[ 'args' ][ 'boot_strap_row_fe_start' ] ) && true === $this->field[ 'args' ][ 'boot_strap_row_fe_start' ] && false === is_admin() ) {
				$wrapper = ' <div class="wpas-fe-bs4-row row"> ' . $wrapper;
			}

			/* Add an ending DIV if this field marks the end of a bootstrap row and we're displaying on the front-end... */		
			if ( ! empty( $this->field[ 'args' ][ 'boot_strap_row_fe_end' ] ) && true === $this->field[ 'args' ][ 'boot_strap_row_fe_end' ] && false === is_admin() ) {
				$wrapper .= ' </div> ' ;
			}			
			
			$field       = $this->get_field_markup();
			
			$description = $this->get_field_description();

			if ( ! empty( $description ) ) {
				$field .= $description;
			}

			$this->output = str_replace( '{{field}}', $field, $wrapper );

			return $this->output;

		}

		/**
		 * Returns the field value sanitized with the appropriate callback.
		 *
		 * @since 3.2.0
		 *
		 * @param mixed $value Raw value to sanitize
		 *
		 * @return mixed Sanitized value
		 */
		public function get_sanitized_value( $value ) {

			$sanitize_function = 'sanitize_text_field' ;
			
			if ( isset( $this->field[ 'args' ][ 'sanitize' ] ) ) {
				$sanitize_function = function_exists( $this->field[ 'args' ][ 'sanitize' ] ) ? $this->field[ 'args' ][ 'sanitize' ] : 'sanitize_text_field';
			}

			if ( is_array( $value ) ) {
				$sanitized_value = array_map( $sanitize_function, $value );
			} else {
				$sanitized_value = call_user_func( $sanitize_function, $value );
			}

			return $sanitized_value;
		}

		/**
		 * Update the custom field value.
		 *
		 * @since 3.2.0
		 *
		 * @param mixed $value The value to update the custom field with
		 * @param int $post_id ID of the post this custom field should be attached to
		 *
		 * @return integer Result
		 */
		public function update_value( $value, $post_id ) {

			/**
			 * This variable will contain the update result.
			 * It is used for logging the action performed.
			 * The result must be an int containing one
			 * of those three options:
			 *
			 * - 0: nothing happened, no changes
			 * - 1: if there was no old value and the new value is added
			 * - 2: if the old value was updated with a new one
			 * - 3: if the new value is empty and the old one deleted
			 * - 4: the user doesn't have sufficient capability to edit this field
			 *
			 * @var int $result
			 */
			$result = 0;

			/**
			 * The first thing we do is make sure the current user has the required capability to save a new value for this custom field.
			 */
			if ( ! current_user_can( $this->field_args[ 'capability' ] ) ) {
				return 4;
			}

			/**
			 * Get the field ID for saving purpose.
			 */
			$field_id = $this->get_field_id( true );

			/**
			 * First of all let's sanitize the value.
			 */
			$value = $this->get_sanitized_value( $value );

			/**
			 * Check for a custom save callback function.
			 */
			if ( false !== $this->field[ 'args' ][ 'save_callback' ] && function_exists( $this->field[ 'args' ][ 'save_callback' ] ) ) {
				$result = call_user_func( $this->field[ 'args' ][ 'save_callback' ], $value, $post_id, $field_id, $this->field );
			} /**
			 * Use our built-in save function otherwise.
			 */
			else {

				$class_name = $this->get_class_name();

				/* Use a custom save function if any. */
				if ( $this->require_field_type_class() && class_exists( $class_name ) && method_exists( $class_name, 'update' ) ) {

					/* Instantiate the field type class */
					$instance = new $class_name( $this->field_id, $this->field );

					$result = $instance->update( $value, $post_id );

				} /* Default save function otherwise. */
				else {

					/**
					 * Check if custom field exists - requires WP 3.3.0 or higher.
					 *
					 * @since   3.3.5
					 *
					 * @var  $key_exists
					 */
					$key_exists = metadata_exists( 'post', $post_id, $field_id );

					/**
					 * Get the current field value.
					 */
					$current = $key_exists ? get_post_meta( $post_id, $field_id, true ) : null;

					/**
					 * First case scenario
					 *
					 * The option exists in DB but the new value
					 * is empty. This is often the case for checkboxes.
					 *
					 * Action: Delete option
					 */
					if ( $key_exists && empty( $value ) ) {
						if ( delete_post_meta( $post_id, $field_id, $current ) ) {
							$result = 3;
						}
					} /**
					 * Second case scenario
					 *
					 * The option exists in DB and the new value is not empty.
					 *
					 * Action: Update post meta OR delete it
					 */
					elseif ( $key_exists && ! empty( $value ) ) {

						/* Make sure the old and new values aren't the same */
						if ( $current !== $value ) {
							if ( false !== update_post_meta( $post_id, $field_id, $value, $current ) ) {
								$result = 2;
							}
						}

					} /**
					 * Third case scenario
					 *
					 * The option doesn't exist in DB but a value was passed in the POST.
					 *
					 * Action: Add post meta
					 */
					elseif ( ! $key_exists && ! empty( $value ) ) {
						if ( false !== add_post_meta( $post_id, $field_id, $value, true ) ) {
							$result = 1;
						}
					}

				}

			}

			return $result;

		}

	}
