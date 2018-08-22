<?php

namespace WPAS_API\API;

use WPAS_API\Auth\User;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_REST_Settings_Controller;
use TitanFramework;

/**
 * Core class used to manage a site's settings via the REST API.
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class Settings extends WP_REST_Settings_Controller {

	/**
	 * Whether or not we've loaded the admin files and framework.
	 *
	 * @var bool
	 */
	protected static $_framework_loaded = false;

	/**
	 * @var TitanFramework
	 */
	public static $_titan;

	/**
	 * Constructor.
	 *
	 * @since 4.7.0
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		$this->namespace = wpas_api()->get_api_namespace();
		$this->rest_base = 'settings';

		self::load_settings_framework();
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'args'                => array(),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

	}

	/**
	 * Checks if a given request has access to read and manage settings.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function get_item_permissions_check( $request ) {
		
		if ( current_user_can( 'settings_tickets' ) or current_user_can( 'create_ticket' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if a given request has access to update settings.
	 *
	 * @since 4.7.1
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function update_item_permissions_check( $request ) {
		return current_user_can( 'settings_tickets' );
	}


	/**
	 * Retrieves the settings.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error Array on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$options  = $this->get_registered_options();
		$response = array();

		foreach ( $options as $name => $args ) {

			/**
			 * Filters the value of a setting recognized by the REST API.
			 *
			 * Allow hijacking the setting value and overriding the built-in behavior by returning a
			 * non-null value.  The returned value will be presented as the setting value instead.
			 *
			 * @since 4.7.0
			 *
			 * @param mixed  $result Value to use for the requested setting. Can be a scalar
			 *                       matching the registered schema for the setting, or null to
			 *                       follow the default get_option() behavior.
			 * @param string $name   Setting name (as shown in REST API responses).
			 * @param array  $args   Arguments passed to register_setting() for this setting.
			 */
			$response[ $name ] = apply_filters( 'rest_pre_get_setting', null, $name, $args );

			if ( is_null( $response[ $name ] ) ) {
				// Default to a null value as "null" in the response means "not set".
				$response[ $name ] = self::$_titan->getOption( $name );
			}

			/**
			 * cast values to the type they are registered with.
			 */
			$response[ $name ] = $this->prepare_value( $response[ $name ], $args['schema'] );
		}

		return $response;
	}

	/**
	 * Prepares a value for output based off a schema array.
	 *
	 * @since 4.7.0
	 * @access protected
	 *
	 * @param mixed $value  Value to prepare.
	 * @param array $schema Schema to match.
	 * @return mixed The prepared value.
	 */
	protected function prepare_value( $value, $schema ) {
		// If the value is not a scalar, it's not possible to cast it to anything.
		if ( ! is_scalar( $value ) ) {
			return null;
		}

		switch ( $schema['type'] ) {
			case 'string':
				return (string) $value;
			case 'integer':
				return (int) $value;
			case 'number':
				return (float) $value;
			case 'boolean':
				return (bool) $value;
			default:
				return null;
		}
	}

	/**
	 * Updates settings for the settings object.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error Array on success, or error object on failure.
	 */
	public function update_item( $request ) {
		$options = $this->get_registered_options();
		$params  = $request->get_params();

		foreach ( $options as $name => $args ) {
			if ( ! array_key_exists( $name, $params ) ) {
				continue;
			}

			/**
			 * Filters whether to preempt a setting value update.
			 *
			 * Allows hijacking the setting update logic and overriding the built-in behavior by
			 * returning true.
			 *
			 * @since 4.7.0
			 *
			 * @param bool   $result Whether to override the default behavior for updating the
			 *                       value of a setting.
			 * @param string $name   Setting name (as shown in REST API responses).
			 * @param mixed  $value  Updated setting value.
			 * @param array  $args   Arguments passed to register_setting() for this setting.
			 */
			$updated = apply_filters( 'rest_pre_update_setting', false, $name, $request[ $name ], $args );

			if ( $updated ) {
				continue;
			}

			self::$_titan->setOption( $args['option_name'], $request[ $name ] );
		}

		self::$_titan->saveInternalAdminPageOptions();

		return $this->get_item( $request );
	}

	/**
	 * Retrieves all of the registered options for the Settings API.
	 *
	 * @since 4.7.0
	 * @access protected
	 *
	 * @return array Array of registered options.
	 */
	protected function get_registered_options() {
		$settings     = isset( self::$_titan->optionsUsed ) ? self::$_titan->optionsUsed : false;
		$rest_options = array();

		if ( empty( $settings ) ) {
			return $rest_options;
		}

		foreach ( $settings as $option ) {
			$args = $option->settings;

			if ( isset( $args['show_in_rest'] ) && false === $args['show_in_rest'] ) {
				continue;
			}

			$rest_args = array();

			if ( isset( $args['show_in_rest'] ) && is_array( $args['show_in_rest'] ) ) {
				$rest_args = $args['show_in_rest'];
			}

			$defaults = array(
				'schema' => array(),
			);

			$rest_args = array_merge( $defaults, $rest_args );

			switch( $args['type'] ) {
				case 'select' :
				case 'radio' :
				case 'text' :
				case 'textarea' :
				case 'editor' :
				case 'color' :
					$type = 'string';
					break;
				case 'checkbox' :
					$type = 'boolean';
					break;
				default:
					$type = false;
			}

			$default_schema = array(
				'name'        => empty( $args['name'] ) ? null : $args['name'],
				'type'        => $type,
				'description' => empty( $args['desc'] ) ? '' : $args['desc'],
				'default'     => isset( $args['default'] ) ? $args['default'] : null,
				'enum'        => empty( $args['options'] ) ? null : array_keys( $args['options'] ),
			);

			$rest_args['schema'] = array_merge( $default_schema, $rest_args['schema'] );
			$rest_args['option_name'] = $args['id'];

			// Skip over settings that don't have a defined type in the schema.
			if ( empty( $rest_args['schema']['type'] ) ) {
				continue;
			}

			/*
			 * Whitelist the supported types for settings, as we don't want invalid types
			 * to be updated with arbitrary values that we can't do decent sanitizing for.
			 */
			if ( ! in_array( $rest_args['schema']['type'], array( 'number', 'integer', 'string', 'boolean' ), true ) ) {
				continue;
			}

			$rest_options[ $args['id'] ] = $rest_args;
		}

		return $rest_options;
	}

	/**
	 * Retrieves the site setting schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$options = $this->get_registered_options();

		$schema = array(
			'$schema'    => 'http://json-schema.org/schema#',
			'title'      => 'settings',
			'type'       => 'object',
			'properties' => array(),
		);

		foreach ( $options as $option_name => $option ) {
			$schema['properties'][ $option_name ] = $option['schema'];
			$schema['properties'][ $option_name ]['arg_options'] = array(
				'sanitize_callback' => array( $this, 'sanitize_callback' ),
			);
		}

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Custom sanitize callback used for all options to allow the use of 'null'.
	 *
	 * By default, the schema of settings will throw an error if a value is set to
	 * `null` as it's not a valid value for something like "type => string". We
	 * provide a wrapper sanitizer to whitelist the use of `null`.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @param  mixed           $value   The value for the setting.
	 * @param  WP_REST_Request $request The request object.
	 * @param  string          $param   The parameter name.
	 * @return mixed|WP_Error
	 */
	public function sanitize_callback( $value, $request, $param ) {
		if ( is_null( $value ) ) {
			return $value;
		}

		return rest_parse_request_arg( $value, $request, $param );
	}

	protected static function load_settings_framework() {
		// only load the framework on a REST REQUEST
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return;
		}

		require_once( WPAS_PATH . 'includes/admin/settings/functions-settings.php' );
		require_once( WPAS_PATH . 'includes/admin/settings/settings-general.php' );
		require_once( WPAS_PATH . 'includes/admin/settings/settings-style.php' );
		require_once( WPAS_PATH . 'includes/admin/settings/settings-notifications.php' );
		require_once( WPAS_PATH . 'includes/admin/settings/settings-advanced.php' );
		require_once( WPAS_PATH . 'includes/admin/settings/settings-licenses.php' );
		require_once( WPAS_PATH . 'includes/admin/settings/settings-products-management.php' );
		require_once( WPAS_PATH . 'includes/admin/settings/settings-basic-time-tracking.php' );

		/**
		 * When using the embedded framework, use it only if the framework
		 * plugin isn't activated.
		 */

		// Don't do anything when we're activating a plugin to prevent errors
		// on redeclaring Titan classes
		if ( 'activate' === filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING )
		     && ! empty( filter_input( INPUT_GET, 'plugin' ) )
		) {
			return;
		}

		// Check if the framework plugin is activated
		$useEmbeddedFramework = true;
		$activePlugins        = get_option( 'active_plugins' );
		if ( is_array( $activePlugins ) ) {
			foreach ( $activePlugins as $plugin ) {
				if ( is_string( $plugin ) ) {
					if ( stripos( $plugin, '/titan-framework.php' ) !== false ) {
						$useEmbeddedFramework = false;
						break;
					}
				}
			}
		}

		// Use the embedded Titan Framework
		if ( $useEmbeddedFramework && ! class_exists( 'TitanFramework' ) ) {
			require_once( WPAS_PATH . 'vendor/gambitph/titan-framework/titan-framework.php' );
		}

		/*
		 * Start your Titan code below
		 */
		self::$_titan = TitanFramework::getInstance( 'wpas' );

		$settings = self::$_titan->createContainer( array(
				'type'       => 'admin-page',
				'name'       => __( 'Settings', 'awesome-support' ),
				'title'      => __( 'Awesome Support Settings', 'awesome-support' ),
				'id'         => 'wpas-settings',
				'parent'     => '',
				'capability' => 'settings_tickets'
			)
		);

		/**
		 * Get plugin core options
		 *
		 * @var  (array)
		 * @see  admin/includes/settings.php
		 */
		$options = wpas_get_raw_settings();

		/* Parse options */
		foreach ( $options as $option ) {
			$settings->createOption( $option );
		}

	}
}
