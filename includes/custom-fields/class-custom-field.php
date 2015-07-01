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
			$field = array( 'args' => $this->get_field_defaults() );
		}

		$this->field      = $field;
		$this->field_id   = sanitize_text_field( $field_id );
		$this->field_type = $this->field['args']['field_type'];

		/* Set the legacy mode */
		$this->legacy = ! empty( $field['args']['callback'] ) ? true : false;

		/**
		 * Get the ID of the post the custom field relates to.
		 */
		$this->post_id = false; // Set the default value
		$this->post_id = isset( $post ) ? $post->ID : filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

	}

	/**
	 * Returns the default arguments for a custom field.
	 *
	 * @since 3.2.0
	 */
	public static function get_field_defaults() {

		/* Default arguments */
		$defaults = array(
			'field_type'            => 'text-field',          // Type of custom field to display
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
		    /* Added in 3.2.0 */
		    'html5_pattern'         => '',                    // Adds a validation pattern following the HTML5 standards
			/* The following parameters are users for taxonomies only. */
			'taxo_std'              => false,                 // For taxonomies, should it behave like a standard WordPress taxonomy
			'label'                 => '',
			'label_plural'          => '',
			'taxo_hierarchical'     => true,
			'update_count_callback' => 'wpas_update_ticket_tag_terms_count',
			/* Deprecated */
			// 'callback'              => '', // @deprecated 3.2.0
		);

		return $defaults;

	}

	/**
	 * Get the field class name.
	 *
	 * @since 3.2.0
	 * @return string The class name
	 */
	public function get_class_name() {

		if ( ! isset( $this->class_name ) ) {
			$type             = str_replace( ' ', '_', ucwords( str_replace( array( '-' ), ' ', $this->field['args']['field_type'] ) ) );
			$this->class_name = "WPAS_CF_$type";
		}

		return $this->class_name;

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

		return empty( $value ) ? $default : function_exists( $this->field['args']['sanitize'] ) ? call_user_func( $this->field['args']['sanitize'], $value ) : $value;

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

		if ( class_exists( $class_name ) && method_exists( $class_name, 'wrapper' ) ) {
			$default = $class_name::wrapper();
		} else {
			$default = sprintf( '<div class="%s" id="%s">{{field}}</div>', $this->get_wrapper_class(), $this->get_field_id() );
		}

		return apply_filters( 'wpas_custom_field_wrapper', $default, $this->field );

	}

	/**
	 * Get the field HTML markup.
	 *
	 * @since 3.2.0
	 * @return string The field final markup
	 */
	protected function get_field_markup() {

		$field    = '';
		$callback = $this->field['args']['field_type']; // Used for backwards compatibility

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
		 * We need ot run this first because there will always be a fallback to text-field
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
					if ( ! current_user_can( $this->field['args']['capability'] ) && method_exists( $instance, 'display_no_edit' ) ) {
						$field = $instance->display_no_edit();
					} elseif ( current_user_can( $this->field['args']['capability'] ) && method_exists( $instance, 'display_admin' ) ) {
						$field = $instance->display_admin();
					} else {
						$field = $instance->display();
					}
				} else {
					$field = $instance->display();
				}

			} else {
				$field  = '<!-- ' . __( 'The custom field class does not contain the mandatory method "display"', 'wpas' ) . ' -->';
				$error = true;
			}

		}

		/* In case the field type / callback function does not exist */
		else {
			$field  = '<!-- ' . __( 'The type of custom field you are trying to use does not exist', 'wpas' ) . ' -->';
			$error = true;
		}

		return false === $error ? $this->process_field_markup( apply_filters( 'wpas_cf_field_markup_' . $this->field_type, $field ) ) : $field;

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
		$label_class = isset( $this->field['args']['label_class'] ) ? $this->field['args']['label_class'] : '';
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
		if ( ! empty( $this->field['args']['placeholder'] ) ) {
			$placeholder = wp_strip_all_tags( $this->field['args']['placeholder'] );
			array_push( $atts, "placeholder='$placeholder'" );
		}

		/* Add the field HTML5 pattern */
		if ( ! empty( $this->field['args']['html5_pattern'] ) ) {
			array_push( $atts, "pattern='{$this->field['args']['html5_pattern']}'" );
		}

		/* Add the required attribute */
		if ( true === $this->field['args']['required'] ) {
			array_push( $atts, 'required' );
		}

		$field = str_replace( '{{atts}}', implode( ' ', $atts ), $field );
		$field = str_replace( '{{label_atts}}', implode( ' ', $label_atts ), $field );
		$field = str_replace( '{{label}}', $this->field['args']['label'], $field );

		return apply_filters( 'wpas_cf_field_markup_processed', $field, $this->field );

	}

	/**
	 * Get field container class.
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

		$class_name = $this->get_class_name();

		if ( class_exists( $class_name ) && isset( $class_name::$default_wrapper_class ) ) {
			array_push( $classes, $class_name::$default_wrapper_class );
		}

		/* Add the error class if needed */
		if ( isset( $_SESSION['wpas_submission_error'] ) && is_array( $_SESSION['wpas_submission_error'] ) && in_array( $this->get_field_id(), $_SESSION['wpas_submission_error'] ) ) {
			array_push( $classes, 'has-error' );
		}

		/* Filter the final list */
		$classes = apply_filters( 'wpas_wrapper_class', $classes, $this->field );

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
			$erase = apply_filters( 'wpas_wrapper_class_force_erase_extra', false );

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
	 * @param  string $class Extra classes to pass to the function
	 *
	 * @return string             The class tag with appropriate classes
	 */
	function get_field_class( $class = '' ) {

		/**
		 * Set the classes array with the default class.
		 *
		 * @var $classes array
		 */
		$classes = array(
			'wpas-form-control'
		);

		$class_name = $this->get_class_name();

		if ( class_exists( $class_name ) && isset( $class_name::$default_field_class ) ) {
			array_push( $classes, $class_name::$default_field_class );
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
			$erase = apply_filters( 'wpas_field_class_force_erase_extra', false );

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
		$classes = apply_filters( 'wpas_field_description_class', array( 'backend'  => 'description', 'frontend' => 'wpas-help-block' ), $this->field );

		if ( isset( $this->field['args']['desc'] ) && ! empty( $this->field['args']['desc'] ) ) {
			$class       = is_admin() ? $classes['backend'] : $classes['frontend'];
			$description = sprintf( '<p class="%s">%s</p>', $class, wp_kses_post( $this->field['args']['desc'] ) );
		}

		return $description;

	}

	/**
	 * Returns the custom field complete HTML markup.
	 *
	 * @since 3.2.0
	 * @return string Final HTML markup
	 */
	public function get_output() {

		$field_class_path = WPAS_PATH . "includes/custom-fields/field-types/class-cf-{$this->field['args']['field_type']}.php";

		if ( file_exists( $field_class_path ) ) {
			require_once( $field_class_path );
		}

		$wrapper     = apply_filters( 'wpas_cf_wrapper_markup', $this->get_wrapper_markup(), $this->field );
		$field       = $this->get_field_markup();
		$description = apply_filters( 'wpas_cf_description_markup', $this->get_field_description(), $this->field );

		if ( ! empty( $description ) ) {
			$field .= $description;
		}

		$this->output = str_replace( '{{field}}', $field, $wrapper );

		return $this->output;

	}

}