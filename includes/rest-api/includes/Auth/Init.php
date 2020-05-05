<?php

namespace WPAS_API\Auth;

use WPAS_API\API\Passwords;

use WP_REST_Server;
use WP_User;
use WPAS_API\Auth\User;
use WP_Error;

/**
 * Class for displaying, modifying, & sanitizing application passwords.
 *
 * @since 0.1-dev
 *
 * @package Two_Factor
 */
class Init {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of Init
	 *
	 * @return Init
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Init ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected function __construct() {
		$this->add_hooks();
	}

	/**
	 * Add various hooks.
	 */
	public function add_hooks() {
		add_action( 'show_user_profile', array( __CLASS__, 'show_user_profile' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'show_user_profile' ) );
		add_filter( 'determine_current_user', array( __CLASS__, 'rest_api_auth_handler' ), 20 );
		add_filter( 'wp_rest_server_class', array( __CLASS__, 'wp_rest_server_class' ) );
		self::fallback_populate_username_password();
	}

	/**
	 * Prevent caching of unauthenticated status.  See comment below.
	 *
	 * We don't actually care about the `wp_rest_server_class` filter, it just
	 * happens right after the constant we do care about is defined.
	 */
	public static function wp_rest_server_class( $class ) {
		global $current_user;
		if ( defined( 'REST_REQUEST' )
		     && REST_REQUEST
		     && $current_user instanceof WP_User
		     && 0 === $current_user->ID ) {
			/*
			 * For our authentication to work, we need to remove the cached lack
			 * of a current user, so the next time it checks, we can detect that
			 * this is a rest api request and allow our override to happen.  This
			 * is because the constant is defined later than the first get current
			 * user call may run.
			 */
			$current_user = null;
		}
		return $class;
	}

	/**
	 * REST API endpoint to list existing application passwords for a user.
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public static function rest_list_api_passwords( $data ) {

		$user = new User( $data['user_id'] );
		$api_passwords = $user->get_api_passwords();
		$with_slugs = array();

		if ( $api_passwords ) {
			foreach ( $api_passwords as $slug => $item ) {
				$item['slug'] = $slug;
				unset( $item['raw'] );
				unset( $item['password'] );

				$item['created'] = date( get_option( 'date_format', 'r' ), $item['created'] );

				if ( empty( $item['last_used'] ) ) {
					$item['last_used'] =  '—';
				} else {
					$item['last_used'] = date( get_option( 'date_format', 'r' ), $item['last_used'] );
				}

				if ( empty( $item['last_ip'] ) ) {
					$item['last_ip'] =  '—';
				}

				$with_slugs[ $item['slug'] ] = $item;
			}
		}

		return $with_slugs;
	}

	/**
	 * REST API endpoint to add a new application password for a user.
	 *
	 * @param $data
	 *
	 * @return array | WP_Error
	 */
	public static function rest_add_api_password( $data ) {

		$user = new User( $data['user_id'] );

		if ( empty( $data['name'] ) ) {
			return new WP_Error( 'no-name', __( 'Please provide a name to use for the new password.', 'awesome-support' ), array( 'status' => 404 ) );
		}

		list( $new_password, $new_item ) = $user->create_new_api_password( $data['name'] );

		// Some tidying before we return it.
		$new_item['slug']      = User::password_unique_slug( $new_item );
		$new_item['created']   = date( get_option( 'date_format', 'r' ), $new_item['created'] );
		$new_item['last_used'] = '—';
		$new_item['last_ip']   = '—';
		unset( $new_item['password'] );

		return array(
			'row'      => $new_item,
			'password' => User::chunk_password( $new_password )
		);
	}

	/**
	 * REST API endpoint to delete a given application password.
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public static function rest_delete_api_password( $data ) {
		$user = new User( $data['user_id'] );
		return $user->delete_api_password( $data['slug'] );
	}

	/**
	 * REST API endpoint to delete all of a user's application passwords.
	 *
	 * @param $data
	 *
	 * @return int The number of deleted passwords
	 */
	public static function rest_delete_all_api_passwords( $data ) {
		$user = new User( $data['user_id'] );
		return $user->delete_all_api_passwords();
	}

	/**
	 * Whether or not the current user can edit the specified user.
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public static function rest_edit_user_callback( $data ) {
		return current_user_can( 'edit_user', $data['user_id'] );
	}

	/**
	 * Loosely Based on https://github.com/WP-API/Basic-Auth/blob/master/basic-auth.php
	 *
	 * @param $input_user
	 *
	 * @return WP_User|bool
	 */
	public static function rest_api_auth_handler( $input_user ){
		// Don't authenticate twice
		if ( ! empty( $input_user ) ) {
			return $input_user;
		}

		// Check that we're trying to authenticate
		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			return $input_user;
		}

		// limit this authentication to these api route
		global $wp;
		$route = isset( $wp->query_vars['rest_route'] ) ? $wp->query_vars['rest_route'] : '';

		$api_request = ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ( false !== strpos( $route, wpas_api()->get_api_namespace() ) );
		if ( ! apply_filters( 'wpas_api_authenticate_request', $api_request ) ) {
			return $input_user;
		}

		// get the user by the username
		$user = new User( 0,  $_SERVER['PHP_AUTH_USER'] );

		if ( $user->authenticate( $_SERVER['PHP_AUTH_PW'] ) ) {
			return $user->ID;
		}

		// If it wasn't a user what got returned, just pass on what we had received originally.
		return $input_user;
	}

	/**
	 * Test whether PHP can see Basic Authorization headers passed to the web server.
	 *
	 * @return WP_Error|array
	 */
	public static function rest_test_basic_authorization_header() {
		$response = array();

		if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			$response['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];
		}

		if ( isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$response['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'];
		}

		if ( empty( $response ) ) {
			return new WP_Error( 'no-credentials', __( 'No HTTP Basic Authorization credentials were found submitted with this request.', 'awesome-support' ), array( 'status' => 404 ) );
		}

		return $response;
	}

	/**
	 * Some servers running in CGI or FastCGI mode don't pass the Authorization
	 * header on to WordPress.  If it's been rewritten to the `REMOTE_USER` header,
	 * fill in the proper $_SERVER variables instead.
	 */
	public static function fallback_populate_username_password() {
		// If we don't have anything to pull from, return early.
		if ( ! isset( $_SERVER['REMOTE_USER'] ) && ! isset( $_SERVER['REDIRECT_REMOTE_USER'] ) ) {
			return;
		}

		// If either PHP_AUTH key is already set, do nothing.
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) || isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			return;
		}

		// From our prior conditional, one of these must be set.
		$header = isset( $_SERVER['REMOTE_USER'] ) ? $_SERVER['REMOTE_USER'] : $_SERVER['REDIRECT_REMOTE_USER'];

		// Test to make sure the pattern matches expected.
		if ( ! preg_match( '%^Basic [a-z\d/+]*={0,2}$%i', $header ) ) {
			return;
		}

		// Removing `Bearer ` the token would start six characters in.
		$token               = substr( $header, 6 );
		$userpass            = base64_decode( $token );
		list( $user, $pass ) = explode( ':', $userpass );

		// Now shove them in the proper keys where we're expecting later on.
		$_SERVER['PHP_AUTH_USER'] = $user;
		$_SERVER['PHP_AUTH_PW']   = $pass;

		return;
	}

	/**
	 * Display the application password section in a users profile.
	 *
	 * This executes during the `show_user_security_settings` action.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public static function show_user_profile( $user ) {

		// convert \WP_User into Auth\User
		$user = new User( $user );

		wp_enqueue_style( 'wpas-api-css', WPAS_URL . 'includes/rest-api/assets/admin/css/admin.css', array() );
		wp_enqueue_script( 'wpas-api-js', WPAS_URL . 'includes/rest-api/assets/admin/js/admin.js', array() );

		wp_localize_script( 'wpas-api-js', 'wpasAPI', array(
			'root'       => esc_url_raw( rest_url() ),
			'namespace'  => wpas_api()->get_api_namespace(),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'user_id'    => $user->ID,
			'text'       => array(
				'no_credentials' => __( 'Due to a potential server misconfiguration, it seems that HTTP Basic Authorization may not work for the REST API on this site: `Authorization` headers are not being sent to WordPress by the web server. <a href="https://github.com/georgestephanis/application-passwords/wiki/Basic-Authorization-Header----Missing">You can learn more about this problem, and a possible solution, on our GitHub Wiki.</a>' ),
			),
		) );

		?>
		<div id="wpas_user_profile_segment">
			<div class="wpas-api hide-if-no-js" id="wpas-api-section">
				<h2 id="wpas-api"><?php esc_html_e( 'API Passwords' ); ?></h2>
				<p><?php esc_html_e( 'API passwords allow authentication via the REST API without providing your actual password. API passwords can be easily revoked. They cannot be used for traditional logins to your website.', 'awesome-support' ); ?></p>
				<div class="create-wpas-api-password">
					<input type="text" size="30" name="new_wp_api_password_name" placeholder="<?php esc_attr_e( 'New API Password Name', 'awesome-support' ); ?>" class="input" />
					<?php submit_button( __( 'Add New' ), 'secondary', 'do_new_wp_api_password', false ); ?>
				</div>

				<div class="wpas-api-list-table-wrapper">
				<?php
					$wp_api_passwords_list_table = new PasswordList();
					$wp_api_passwords_list_table->items = array_reverse( $user->get_api_passwords() );
					$wp_api_passwords_list_table->prepare_items();
					$wp_api_passwords_list_table->display();
				?>
				</div>
			</div>
		</div>
		<script type="text/html" id="tmpl-new-wpas-api-password">
			<div class="new-wpas-api-password notification-dialog-wrap">
				<div class="app-pass-dialog-background notification-dialog-background">
					<div class="app-pass-dialog notification-dialog">
						<div class="new-wpas-api-password-content">
							<?php
							printf(
								esc_html_x( 'Your new password for %1$s is: %2$s', 'application, password' ),
								'<strong>{{ data.name }}</strong>',
								'<kbd>{{ data.password }}</kbd>'
							);
							?>
						</div>
						<p><?php esc_attr_e( 'Be sure to save this in a safe location.  You will not be able to retrieve it.' ); ?></p>
						<button class="button button-primary wpas-api-password-modal-dismiss"><?php esc_attr_e( 'Dismiss' ); ?></button>
					</div>
				</div>
			</div>
		</script>

		<script type="text/html" id="tmpl-wpas-api-password-row">
			<tr data-slug="{{ data.slug }}">
				<td class="name column-name has-row-actions column-primary" data-colname="<?php esc_attr_e( 'Name' ); ?>">
					{{ data.name }}
				</td>
				<td class="created column-created" data-colname="<?php esc_attr_e( 'Created' ); ?>">
					{{ data.created }}
				</td>
				<td class="last_used column-last_used" data-colname="<?php esc_attr_e( 'Last Used' ); ?>">
					{{ data.last_used }}
				</td>
				<td class="last_ip column-last_ip" data-colname="<?php esc_attr_e( 'Last IP' ); ?>">
					{{ data.last_ip }}
				</td>
				<td class="revoke column-revoke" data-colname="<?php esc_attr_e( 'Revoke' ); ?>">
					<input type="submit" name="revoke-wpas-api-password" class="button delete" value="<?php esc_attr_e( 'Revoke' ); ?>">
				</td>
			</tr>
		</script>

		<script type="text/html" id="tmpl-wpas-api-password-notice">
			<div class="notice notice-{{ data.type }}"><p>{{{ data.message }}}</p></div>
		</script>
		<?php
	}

	public static function get_password_schema() {
		$schema = array(
			'name'      => array(
				'description' => __( "The name of the new password" ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit', 'embed' ),
			),
			'password'  => array(
				'description' => __( "The hashed password that was created" ),
				'type'        => 'string',
				'format'      => 'date-time',
				'context'     => array( 'edit' ),
			),
			'created'   => array(
				'description' => __( 'The date the password was created' ),
				'type'        => 'string',
				'format'      => 'date-time',
				'context'     => array( 'view', 'edit' ),
			),
			'last_used' => array(
				'description' => __( 'The date the password was last used' ),
				'type'        => 'string',
				'format'      => 'date-time',
				'context'     => array( 'view', 'edit' ),
			),
			'last_ip'   => array(
				'description' => __( 'The IP address that the password was last used from' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'slug'      => array(
				'description' => __( 'The password\'s unique sluge' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		);

		return apply_filters( 'wpas_api_get_password_schema', $schema );
	}

}