<?php
/**
 * Remote Dashboard Notifications.
 *
 * This class is part of the Remote Dashboard Notifications plugin.
 * This plugin allows you to send notifications to your client's
 * WordPress dashboard easily.
 *
 * Notification you send will be displayed as admin notifications
 * using the standard WordPress hooks. A "dismiss" option is added
 * in order to let the user hide the notification.
 *
 * No data is ever stored on the remote server.
 *
 * @package   Remote Dashboard Notifications
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @link      http://wordpress.org/plugins/remote-dashboard-notifications/
 * @link 	  https://github.com/ThemeAvenue/Remote-Dashboard-Notifications
 * @copyright 2016-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Remote_Dashboard_Notifications_Client' ) ) {

	final class Remote_Dashboard_Notifications_Client {

		/**
		 * @var Remote_Dashboard_Notifications_Client Holds the unique instance
		 * @since 1.3.0
		 */
		private static $instance;

		/**
		 * Minimum version of WordPress required ot run the plugin
		 *
		 * @since 1.3.0
		 * @var string
		 */
		public $wordpress_version_required = '3.8';

		/**
		 * Required version of PHP.
		 *
		 * Follow WordPress latest requirements and require
		 * PHP version 5.2 at least.
		 *
		 * @since 1.3.0
		 * @var string
		 */
		public $php_version_required = '5.2';

		/**
		 * Holds all the registered notifications
		 *
		 * @since 1.3.0
		 * @var array
		 */
		public $notifications = array();

		/**
		 * Instantiate and return the unique object
		 *
		 * @since     1.2.0
		 * @return object Remote_Dashboard_Notifications_Client Unique instance
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Awesome_Support ) ) {
				self::$instance = new Remote_Dashboard_Notifications_Client;
				self::$instance->init();
			}

			return self::$instance;

		}

		/**
		 * Instantiate the plugin
		 *
		 * @since 1.3.0
		 * @return void
		 */
		private function init() {

			// Make sure the WordPress version is recent enough
			if ( ! self::$instance->is_version_compatible() ) {
				return;
			}

			// Make sure we have a version of PHP that's not too old
			if ( ! self::$instance->is_php_version_enough() ) {
				return;
			}

			// Call the dismiss method before testing for Ajax
			if ( isset( $_GET['rn'] ) && isset( $_GET['notification'] ) ) {
				add_action( 'init', array( self::$instance, 'dismiss' ) );
			}

			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
				add_action( 'admin_print_styles', array( self::$instance, 'style' ), 100 );
				add_action( 'admin_notices', array( self::$instance, 'show_notices' ) );
				add_action( 'admin_footer', array( self::$instance, 'script' ) );
			}

			add_action( 'wp_ajax_rdn_fetch_notifications', array( self::$instance, 'remote_get_notice_ajax' ) );
			add_filter( 'heartbeat_received', array( self::$instance, 'heartbeat' ), 10, 2 );

		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 3.2.5
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'awesome-support' ), '3.2.5' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @since 3.2.5
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'awesome-support' ), '3.2.5' );
		}

		/**
		 * Check if the core version is compatible with this addon.
		 *
		 * @since  1.3.0
		 * @return boolean
		 */
		private function is_version_compatible() {

			if ( empty( self::$instance->wordpress_version_required ) ) {
				return true;
			}

			if ( version_compare( get_bloginfo( 'version' ), self::$instance->wordpress_version_required, '<' ) ) {
				return false;
			}

			return true;

		}

		/**
		 * Check if the version of PHP is compatible with this addon.
		 *
		 * @since  1.3.0
		 * @return boolean
		 */
		private function is_php_version_enough() {

			/**
			 * No version set, we assume everything is fine.
			 */
			if ( empty( self::$instance->php_version_required ) ) {
				return true;
			}

			if ( version_compare( phpversion(), self::$instance->php_version_required, '<' ) ) {
				return false;
			}

			return true;

		}

		/**
		 * Register a new remote notification
		 *
		 * @since 1.3.0
		 *
		 * @param int    $channel_id  Channel ID on the remote server
		 * @param string $channel_key Channel key for authentication with the server
		 * @param string $server      Notification server URL
		 * @param int    $cache       Cache lifetime (in hours)
		 *
		 * @return bool|string
		 */
		public function add_notification( $channel_id, $channel_key, $server, $cache = 6 ) {

			$notification = array(
				'channel_id'     => (int) $channel_id,
				'channel_key'    => $channel_key,
				'server_url'     => esc_url( $server ),
				'cache_lifetime' => apply_filters( 'rn_notice_caching_time', $cache ),
			);

			// Generate the notice unique ID
			$notification['notice_id'] = $notification['channel_id'] . (string) strtotime('today');

			// Double check that the required info is here
			if ( '' === ( $notification['channel_id'] || $notification['channel_key'] || $notification['server_url'] ) ) {
				return false;
			}

			// Check that there is no notification with the same ID
			if ( array_key_exists( $notification['notice_id'], $this->notifications ) ) {
				return false;
			}

			$this->notifications[ $notification['notice_id'] ] = $notification;

			return $notification['notice_id'];

		}

		/**
		 * Remove a registered notification
		 *
		 * @since 1.3.0
		 *
		 * @param string $notice_id ID of the notice to remove
		 *
		 * @return void
		 */
		public function remove_notification( $notice_id ) {
			if ( array_key_exists( $notice_id, $this->notifications ) ) {
				unset( $this->notifications[ $notice_id ] );
			}
		}

		/**
		 * Get all registered notifications
		 *
		 * @since 1.3.0
		 * @return array
		 */
		public function get_notifications() {
			return $this->notifications;
		}

		/**
		 * Get a specific notification
		 *
		 * @since 1.3.0
		 *
		 * @param string $notice_id ID of the notice to retrieve
		 *
		 * @return bool|array
		 */
		public function get_notification( $notice_id ) {

			if ( ! array_key_exists( $notice_id, $this->notifications ) ) {
				return false;
			}

			return $this->notifications[ $notice_id ];
		}

		/**
		 * Adds inline style for non standard notices
		 *
		 * This function will only be called if the notice style is not standard.
		 *
		 * @since 0.1.0
		 */
		public function style() { ?>
			<style type="text/css">div.rn-alert{padding:15px 35px 15px 15px;margin-bottom:20px;border:1px solid transparent;-webkit-box-shadow:none;box-shadow:none}div.rn-alert p:empty{display:none}div.rn-alert ol,div.rn-alert ol li,div.rn-alert ul,div.rn-alert ul li{list-style:inherit!important}div.rn-alert ol,div.rn-alert ul{padding-left:30px}div.rn-alert hr{-moz-box-sizing:content-box;box-sizing:content-box;height:0;margin-top:20px;margin-bottom:20px;border:0;border-top:1px solid #eee}div.rn-alert h1,h2,h3,h4,h5,h6{margin-top:0;color:inherit}div.rn-alert a{font-weight:700}div.rn-alert a:hover{text-decoration:underline}div.rn-alert>p{margin:0;padding:0;line-height:1}div.rn-alert>p,div.rn-alert>ul{margin-bottom:0}div.rn-alert>p+p{margin-top:5px}div.rn-alert .rn-dismiss-btn{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;position:relative;top:-2px;right:-21px;padding:0;cursor:pointer;background:0;border:0;-webkit-appearance:none;float:right;font-size:21px;font-weight:700;line-height:1;color:#000;text-shadow:0 1px 0 #fff;opacity:.2;filter:alpha(opacity=20);text-decoration:none}div.rn-alert-success{background-color:#dff0d8;border-color:#d6e9c6;color:#3c763d}div.rn-alert-success hr{border-top-color:#c9e2b3}div.rn-alert-success a{color:#2b542c}div.rn-alert-info{background-color:#d9edf7;border-color:#bce8f1;color:#31708f}div.rn-alert-info hr{border-top-color:#a6e1ec}div.rn-alert-info a{color:#245269}div.rn-alert-warning{background-color:#fcf8e3;border-color:#faebcc;color:#8a6d3b}div.rn-alert-warning hr{border-top-color:#f7e1b5}div.rn-alert-warning a{color:#66512c}div.rn-alert-danger{background-color:#f2dede;border-color:#ebccd1;color:#a94442}div.rn-alert-danger hr{border-top-color:#e4b9c0}div.rn-alert-danger a{color:#843534}</style>
		<?php }

		/**
		 * Display all the registered and available notifications
		 *
		 * @since 1.3.0
		 * @return void
		 */
		public function show_notices() {

			// Don't put notices on page unless its an admin!
			if ( ! wpas_is_asadmin() ) {
				return ;
			}				
		
			foreach ( $this->notifications as $id => $notification ) {

				$rn = $this->get_remote_notification( $notification );

				if ( empty( $rn ) || is_wp_error( $rn ) ) {
					continue;
				}

				if ( $this->is_notification_error( $rn ) ) {
					continue;
				}

				if ( $this->is_notice_dismissed( $rn->slug ) ) {
					continue;
				}

				if ( $this->is_post_type_restricted( $rn ) ) {
					continue;
				}

				if ( ! $this->is_notification_started( $rn ) ) {
					continue;
				}

				if ( $this->has_notification_ended( $rn ) ) {
					continue;
				}

				// Output the admin notice
				$this->create_admin_notice( $rn->message, $this->get_notice_class( isset( $rn->style ) ? $rn->style : 'updated' ), $this->get_notice_dismissal_url( $rn->slug ) );

			}

		}

		/**
		 * Check if the notification has been dismissed
		 *
		 * @since 1.2.0
		 *
		 * @param string $slug Slug of the notice to check
		 *
		 * @return bool
		 */
		protected function is_notice_dismissed( $slug ) {

			global $current_user;

			$dismissed = array_filter( (array) get_user_option( '_rn_dismissed', $current_user->ID ) );

			if ( is_array( $dismissed ) && in_array( $slug, $dismissed ) ) {
				return true;
			}

			return false;

		}

		/**
		 * Check if the notification can be displayed for the current post type
		 *
		 * @since 1.2.0
		 *
		 * @param stdClass $notification The notification object
		 *
		 * @return bool
		 */
		protected function is_post_type_restricted( $notification ) {

			/* If the type array isn't empty we have a limitation */
			if ( isset( $notification->type ) && is_array( $notification->type ) && ! empty( $notification->type ) ) {

				/* Get current post type */
				$pt = get_post_type();

				/**
				 * If the current post type can't be retrieved
				 * or if it's not in the allowed post types,
				 * then we don't display the admin notice.
				 */
				if ( false === $pt || ! in_array( $pt, $notification->type ) ) {
					return true;
				}

			}

			return false;

		}

		/**
		 * Check if the notification has started yet
		 *
		 * @since 1.2.0
		 *
		 * @param stdClass $notification The notification object
		 *
		 * @return bool
		 */
		protected function is_notification_started( $notification ) {

			if ( isset( $notification->date_start ) && ! empty( $notification->date_start ) && strtotime( $notification->date_start ) < time() ) {
				return true;
			}

			return false;

		}

		/**
		 * Check if the notification has expired
		 *
		 * @since 1.2.0
		 *
		 * @param stdClass $notification The notification object
		 *
		 * @return bool
		 */
		protected function has_notification_ended( $notification ) {

			if ( isset( $notification->date_end ) && ! empty( $notification->date_end ) && strtotime( $notification->date_end ) < time() ) {
				return true;
			}

			return false;

		}

		/**
		 * Get the remote notification object
		 *
		 * @since 1.3.0
		 *
		 * @param array $notification The notification data array
		 *
		 * @return object|false
		 */
		protected function get_remote_notification( $notification ) {

			$content = get_transient( 'rn_last_notification_' . $notification['notice_id'] );

			if ( false === $content ) {
				add_option( 'rdn_fetch_' . $notification['notice_id'], 'fetch' );
			}

			return $content;

		}

		/**
		 * Get the admin notice class attribute
		 *
		 * @since 1.3.0
		 *
		 * @param string $style Notification style
		 *
		 * @return string
		 */
		protected function get_notice_class( $style ) {

			switch ( $style ) {
				case 'updated':
					$class = $style;
					break;

				case 'error':
					$class = 'updated error';
					break;

				default:
					$class = "updated rn-alert rn-alert-$style";
			}

			return $class;

		}

		/**
		 * Prepare the dismissal URL for the notice
		 *
		 * @since 1.3.0
		 *
		 * @param string $slug Notice slug
		 *
		 * @return string
		 */
		protected function get_notice_dismissal_url( $slug ) {

			$args  = array();
			$nonce = wp_create_nonce( 'rn-dismiss' );

			$args['rn'] = $nonce;
			$args['notification'] = $slug;

			foreach ( $_GET as $key => $value ) {
				$args[$key] = $value ;
			}

			return esc_url( add_query_arg( $args, '' ) );

		}

		/**
		 * Create the actual admin notice
		 *
		 * @since 1.3.0
		 *
		 * @param string $contents Notice contents
		 * @param string $class    Wrapper class
		 * @param string $dismiss  Dismissal link
		 *
		 * @return void
		 */
		protected function create_admin_notice( $contents, $class, $dismiss ) { ?>
			<div class="<?php echo $class; ?>">
				<a href="<?php echo $dismiss; ?>" id="rn-dismiss" class="rn-dismiss-btn" title="<?php _e( 'Dismiss notification', 'awesome-support' ); ?>">&times;</a>
				<p><?php echo html_entity_decode( $contents ); ?></p>
			</div>
		<?php }

		/**
		 * Dismiss notice
		 *
		 * When the user dismisses a notice, its slug
		 * is added to the _rn_dismissed entry in the DB options table.
		 * This entry is then used to check if a notie has been dismissed
		 * before displaying it on the dashboard.
		 *
		 * @since 0.1.0
		 */
		public function dismiss() {

			global $current_user;

			/* Check if we have all the vars */
			if ( ! isset( $_GET['rn'] ) || ! isset( $_GET['notification'] ) ) {
				return;
			}

			/* Validate nonce */
			if ( ! wp_verify_nonce( sanitize_key( $_GET['rn'] ), 'rn-dismiss' ) ) {
				return;
			}

			/* Get dismissed list */
			$dismissed = array_filter( (array) get_user_option( '_rn_dismissed', $current_user->ID ) );

			/* Add the current notice to the list if needed */
			if ( is_array( $dismissed ) && ! in_array( $_GET['notification'], $dismissed ) ) {
				array_push( $dismissed, $_GET['notification'] );
			}

			/* Update option */
			update_user_option( $current_user->ID, '_rn_dismissed', $dismissed );

			/* Get redirect URL */
			$args = array();

			/* Get URL args */
			foreach ( $_GET as $key => $value ) {

				if ( in_array( $key, array( 'rn', 'notification' ) ) ) {
					continue;
				}

				array_push( $args, "$key=$value" );

			}

			// Read-only redirect
			wp_safe_redirect( add_query_arg( $args, '' ) );

		}

		/**
		 * Adds the script that hooks into the Heartbeat API
		 *
		 * @since 1.3.0
		 * @return void
		 */
		public function script() {

		// Don't put script on page unless its an admin!
			if ( ! wpas_is_asadmin() ) {
				return ;
			}		

			$maybe_fetch = array();

			foreach ( $this->get_notifications() as $id => $n ) {
				$maybe_fetch[] = (string) $id;
			} ?>

			<script type="text/javascript">
				jQuery(document).ready(function ($) {

					// Hook into the heartbeat-send
					$(document).on('heartbeat-send', function (e, data) {
						data['rdn_maybe_fetch'] = <?php echo json_encode( $maybe_fetch ); ?>;
					});

					// Listen for the custom event "heartbeat-tick" on $(document).
					$(document).on('heartbeat-tick', function (e, data) {

						if (typeof(data.rdn_fetch) !== 'undefined' && data.rdn_fetch !== '') {

							ajax_data = {
								'action': 'rdn_fetch_notifications',
								'notices': data.rdn_fetch
							};

							$.post(ajaxurl, ajax_data);

						}

					});
				});
			</script>

		<?php }

		/**
		 * Hook into the Heartbeat API.
		 *
		 * @since 1.3.0
		 *
		 * @param  array $response Heartbeat tick response
		 * @param  array $data     Heartbeat tick data
		 *
		 * @return array           Updated Heartbeat tick response
		 */
		function heartbeat( $response, $data ) {

			if ( isset( $data['rdn_maybe_fetch'] ) ) {

				$notices = $data['rdn_maybe_fetch'];

				if ( ! is_array( $notices ) ) {
					$notices = array( $notices );
				}

				foreach ( $notices as $notice_id ) {

					$fetch = get_option( "rdn_fetch_$notice_id", false );

					if ( 'fetch' === $fetch ) {

						if ( ! isset( $response['rdn_fetch'] ) ) {
							$response['rdn_fetch'] = array();
						}

						$response['rdn_fetch'][] = $notice_id;

					}

				}

			}

			return $response;

		}

		/**
		 * Triggers the remote requests that fetches notices for this particular instance
		 *
		 * @since 1.3.0
		 * @return void
		 */
		public function remote_get_notice_ajax() {

			if ( isset( $_POST['notices'] ) ) {
				$notices = $_POST['notices'];
			} else {
				echo 'No notice ID';
				die();
			}

			if ( ! is_array( $notices ) ) {
				$notices = array( $notices );
			}

			foreach ( $notices as $notice_id ) {

				$notification = $this->get_notification( $notice_id );
				$rn           = $this->remote_get_notification( $notification );

				if ( is_wp_error( $rn ) ) {
					echo $rn->get_error_message();
				} else {
					echo json_encode( $rn );
				}

			}

			die();

		}

		/**
		 * Get the remote server URL
		 *
		 * @since 1.2.0
		 *
		 * @param string $url THe server URL to sanitize
		 *
		 * @return string
		 */
		protected function get_remote_url( $url ) {

			$url = explode( '?', $url );

			return esc_url( $url[0] );

		}

		/**
		 * Maybe get a notification from the remote server
		 *
		 * @since 1.2.0
		 *
		 * @param array $notification The notification data array
		 *
		 * @return string|WP_Error
		 */
		protected function remote_get_notification( $notification ) {

			/* Query the server */
			$response = wp_remote_get( $this->build_query_url( $notification['server_url'], $this->get_payload( $notification ) ), array( 'timeout' => apply_filters( 'rn_http_request_timeout', 5 ) ) );

			/* If we have a WP_Error object we abort */
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return new WP_Error( 'invalid_response', sprintf( __( 'The server response was invalid (code %s)', 'awesome-support' ), wp_remote_retrieve_response_code( $response ) ) );
			}

			$body = wp_remote_retrieve_body( $response );

			if ( empty( $body ) ) {
				return new WP_Error( 'empty_response', __( 'The server response is empty', 'awesome-support' ) );
			}

			$body = json_decode( $body );

			if ( is_null( $body ) ) {
				return new WP_Error( 'json_decode_error', __( 'Cannot decode the response content', 'awesome-support' ) );
			}

			set_transient( 'rn_last_notification_' . $notification['notice_id'], $body, $notification['cache_lifetime'] * 60 * 60 );
			delete_option( 'rdn_fetch_' . $notification['notice_id'] );

			if ( $this->is_notification_error( $body ) ) {
				return new WP_Error( 'notification_error', $this->get_notification_error_message( $body ) );
			}

			return $body;

		}

		/**
		 * Check if the notification returned by the server is an error
		 *
		 * @since 1.2.0
		 *
		 * @param object $notification Notification returned
		 *
		 * @return bool
		 */
		protected function is_notification_error( $notification ) {

			if ( false === $this->get_notification_error_message( $notification ) ) {
				return false;
			}

			return true;

		}

		/**
		 * Get the error message returned by the remote server
		 *
		 * @since 1.2.0
		 *
		 * @param object $notification Notification returned
		 *
		 * @return bool|string
		 */
		protected function get_notification_error_message( $notification ) {

			if ( ! is_object( $notification ) ) {
				return false;
			}

			if ( ! isset( $notification->error ) ) {
				return false;
			}

			return sanitize_text_field( $notification->error );

		}

		/**
		 * Get the payload required for querying the remote server
		 *
		 * @since 1.2.0
		 *
		 * @param array $notification The notification data array
		 *
		 * @return string
		 */
		protected function get_payload( $notification ) {
			return base64_encode( json_encode( array(
				'channel' => $notification['channel_id'],
				'key'     => $notification['channel_key']
			) ) );
		}

		/**
		 * Get the full URL used for the remote get
		 *
		 * @since 1.2.0
		 *
		 * @param string $url     The remote server URL
		 * @param string $payload The encoded payload
		 *
		 * @return string
		 */
		protected function build_query_url( $url, $payload ) {
			return add_query_arg( array(
				'post_type' => 'notification',
				'payload'   => $payload
			), $this->get_remote_url( $url ) );
		}

	}

}

/**
 * The main function responsible for returning the unique RDN client
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @since 1.3.0
 * @return object Remote_Dashboard_Notifications_Client
 */
function RDNC() {
	return Remote_Dashboard_Notifications_Client::instance();
}

// Get Awesome Support Running
RDNC();

/**
 * Register a new remote notification
 *
 * Helper function for registering new notifications through the Remote_Dashboard_Notifications_Client class
 *
 * @since 1.3.0
 *
 * @param int    $channel_id  Channel ID on the remote server
 * @param string $channel_key Channel key for authentication with the server
 * @param string $server      Notification server URL
 * @param int    $cache       Cache lifetime (in hours)
 *
 * @return bool|string
 */
function rdnc_add_notification( $channel_id, $channel_key, $server, $cache = 6 ) {
	return RDNC()->add_notification( $channel_id, $channel_key, $server, $cache );
}

if ( ! class_exists( 'TAV_Remote_Notification_Client' ) ) {

	/**
	 * Class TAV_Remote_Notification_Client
	 *
	 * This class, even though deprecated, is kept here for backwards compatibility. It is now just a wrapper for the new notification registration method.
	 *
	 * @deprecated @1.3.0
	 */
	class TAV_Remote_Notification_Client {

		public function __construct( $channel_id = false, $channel_key = false, $server = false ) {
			rdnc_add_notification( $channel_id, $channel_key, $server );
		}
	}

}