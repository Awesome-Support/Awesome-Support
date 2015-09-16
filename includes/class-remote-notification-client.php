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
 * @package   Remote Dashboard Notifications
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @link      http://wordpress.org/plugins/remote-dashboard-notifications/
 * @link 	  https://github.com/ThemeAvenue/Remote-Dashboard-Notifications
 * @copyright 2014 ThemeAvenue
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class TAV_Remote_Notification_Client {

	/**
	 * Channel ID
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * Channel identification key
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Notice unique identifier
	 *
	 * @var string
	 */
	protected $notice_id;

	/**
	 * Notification server URL
	 *
	 * @var string
	 */
	protected $server;

	/**
	 * Notification caching delay
	 *
	 * @var int
	 */
	protected $cache;

	/**
	 * Error message
	 *
	 * @var string
	 */
	protected $error;

	/**
	 * Notification
	 *
	 * @var string|object
	 */
	protected $notice;

	/**
	 * Class version.
	 *
	 * @since    0.1.0
	 *
	 * @var      string
	 */
	protected static $version = '0.2.0';

	public function __construct( $channel_id = false, $channel_key = false, $server = false ) {

		/* Don't continue during Ajax process */
		if ( ! is_admin() || defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$this->id        = (int) $channel_id;
		$this->key       = sanitize_key( $channel_key );
		$this->server    = esc_url( $server );
		$this->notice_id = $this->id . substr( $this->key, 0, 5 );
		$this->cache     = apply_filters( 'rn_notice_caching_time', 6 );
		$this->error     = null;

		/* The plugin can't work without those 2 parameters */
		if ( false === ( $this->id || $this->key || $this->server ) ) {
			return;
		}

		$this->init();

	}

	/**
	 * Instantiate the plugin
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function init() {

		/* Call the dismiss method before testing for Ajax */
		if ( isset( $_GET['rn'] ) && isset( $_GET['notification'] ) ) {
			add_action( 'init', array( $this, 'dismiss' ) );
		}

		add_action( 'admin_print_styles', array( $this, 'style' ), 100 );
		add_action( 'admin_notices', array( $this, 'show_notice' ) );

	}

	/**
	 * Get the notification message
	 *
	 * @since 1.2.0
	 * @return string
	 */
	public function get_notice() {

		if ( is_null( $this->notice ) ) {
			$this->notice = $this->fetch_notice();
		}

		return $this->notice;

	}

	/**
	 * Retrieve the notice from the transient or from the remote server
	 *
	 * @since 1.2.0
	 * @return mixed
	 */
	protected function fetch_notice() {

		$content = get_transient( "rn_last_notification_$this->notice_id" );

		if ( false === $content ) {
			$content = $this->remote_get_notice();
		}

		return $content;

	}

	/**
	 * Get the remote server URL
	 *
	 * @since 1.2.0
	 * @return string
	 */
	protected function get_remote_url() {

		$url = explode( '?', $this->server );

		return esc_url( $url[0] );

	}

	/**
	 * Maybe get a notification from the remote server
	 *
	 * @since 1.2.0
	 * @return string|WP_Error
	 */
	protected function remote_get_notice() {

		/* Query the server */
		$response = wp_remote_get( $this->build_query_url(), array( 'timeout' => apply_filters( 'rn_http_request_timeout', 5 ) ) );

		/* If we have a WP_Error object we abort */
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'invalid_response', sprintf( __( 'The server response was invalid (code %s)', 'remote-notifications' ), wp_remote_retrieve_response_code( $response ) ) );
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return new WP_Error( 'empty_response', __( 'The server response is empty', 'remote-notifications' ) );
		}

		$body = json_decode( $body );

		if ( is_null( $body ) ) {
			return new WP_Error( 'json_decode_error', __( 'Cannot decode the response content', 'remote-notifications' ) );
		}

		set_transient( "rn_last_notification_$this->notice_id", $body, $this->cache*60*60 );

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
	 * @return string
	 */
	protected function get_payload() {
		return base64_encode( json_encode( array( 'channel' => $this->id, 'key' => $this->key ) ) );
	}

	/**
	 * Get the full URL used for the remote get
	 *
	 * @since 1.2.0
	 * @return string
	 */
	protected function build_query_url() {
		return add_query_arg( array( 'post_type' => 'notification', 'payload' => $this->get_payload() ), $this->get_remote_url() );
	}

	/**
	 * Check if the notification has been dismissed
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	protected function is_notification_dismissed() {

		if ( is_wp_error( $this->get_notice() ) || $this->is_notification_error( $this->get_notice() ) ) {
			return false;
		}

		global $current_user;
		
		$dismissed = array_filter( (array) get_user_meta( $current_user->ID, '_rn_dismissed', true ) );

		if ( is_array( $dismissed ) && in_array( $this->get_notice()->slug, $dismissed ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Check if the notification can be displayed for the current post type
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	protected function is_post_type_restricted() {

		/* If the type array isn't empty we have a limitation */
		if ( isset( $this->get_notice()->type ) && is_array( $this->get_notice()->type ) && ! empty( $this->get_notice()->type ) ) {

			/* Get current post type */
			$pt = get_post_type();

			/**
			 * If the current post type can't be retrieved
			 * or if it's not in the allowed post types,
			 * then we don't display the admin notice.
			 */
			if ( false === $pt || ! in_array( $pt, $this->get_notice()->type ) ) {
				return true;
			}

		}

		return false;

	}

	/**
	 * Check if the notification has started yet
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	protected function is_notification_started() {

		if ( isset( $this->get_notice()->date_start ) && ! empty( $this->get_notice()->date_start ) && strtotime( $this->get_notice()->date_start ) < time() ) {
			return true;
		}

		return false;

	}

	/**
	 * Check if the notification has expired
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	protected function has_notification_ended() {

		if ( isset( $this->get_notice()->date_end ) && ! empty( $this->get_notice()->date_end ) && strtotime( $this->get_notice()->date_end ) < time() ) {
			return true;
		}

		return false;

	}

	/**
	 * Display the admin notice
	 *
	 * The function will do some checks to verify if
	 * the notice can be displayed on the current page.
	 * If all the checks are passed, the notice
	 * is added to the page.
	 * 
	 * @since 0.1.0
	 */
	public function show_notice() {

		/**
		 * @var object $content
		 */
		$content = $this->get_notice();

		if ( empty( $content ) || is_wp_error( $content ) ) {
			return;
		}

		if ( $this->is_notification_dismissed() ) {
			return;
		}

		if ( $this->is_post_type_restricted() ) {
			return;
		}

		if ( ! $this->is_notification_started() ) {
			return;
		}

		if ( $this->has_notification_ended() ) {
			return;
		}

		/* Prepare alert class */
		$style = isset( $content->style ) ? $content->style : 'updated';

		if ( 'updated' == $style ) {
			$class = $style;
		}

		elseif ( 'error' == $style ) {
			$class = 'updated error';
		}

		else {
			$class = "updated rn-alert rn-alert-$style";
		}

		/**
		 * Prepare the dismiss URL
		 * 
		 * @var (string) URL
		 * @todo get a more accurate URL of the current page
		 */
		$args  = array();
		$nonce = wp_create_nonce( 'rn-dismiss' );
		$slug  = $content->slug;

		array_push( $args, "rn=$nonce" );
		array_push( $args, "notification=$slug" );

		foreach( $_GET as $key => $value ) {

			array_push( $args, "$key=$value" );

		}

		$args = implode( '&', $args );
		$url  = "?$args";
		?>

		<div class="<?php echo $class; ?>">
			<?php if ( !in_array( $style, array( 'updated', 'error' ) ) ): ?><a href="<?php echo $url; ?>" id="rn-dismiss" class="rn-dismiss-btn" title="<?php _e( 'Dismiss notification', 'remote-notifications' ); ?>">&times;</a><?php endif; ?>
			<p><?php echo html_entity_decode( $content->message ); ?></p>
			<?php if ( in_array( $style, array( 'updated', 'error' ) ) ): ?><p><a href="<?php echo $url; ?>" id="rn-dismiss" class="rn-dismiss-button button-secondary"><?php _e( 'Dismiss', 'remote-notifications' ); ?></a></p><?php endif; ?>
		</div>
		<?php

	}

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
		if ( !isset( $_GET['rn'] ) || !isset( $_GET['notification'] ) ) {
			return;
		}

		/* Validate nonce */
		if ( !wp_verify_nonce( sanitize_key( $_GET['rn'] ), 'rn-dismiss' ) ) {
			return;
		}

		/* Get dismissed list */
		$dismissed = array_filter( (array) get_user_meta( $current_user->ID, '_rn_dismissed', true ) );

		/* Add the current notice to the list if needed */
		if ( is_array( $dismissed ) && !in_array( $_GET['notification'], $dismissed ) ) {
			array_push( $dismissed, $_GET['notification'] );
		}

		/* Update option */
		update_user_meta( $current_user->ID, '_rn_dismissed', $dismissed );

		/* Get redirect URL */
		$args = array();

		/* Get URL args */
		foreach( $_GET as $key => $value ) {

			if ( in_array( $key, array( 'rn', 'notification' ) ) )
				continue;

			array_push( $args, "$key=$value" );

		}

		$args = implode( '&', $args );
		$url  = "?$args";

		/* Redirect */
		wp_redirect( $url );

	}

	/**
	 * Adds inline style for non standard notices
	 *
	 * This function will only be called if the notice style is not standard.
	 *
	 * @since 0.1.0
	 */
	public function style() { ?>

		<style type="text/css">div.rn-alert{padding:15px;padding-right:35px;margin-bottom:20px;border:1px solid transparent;-webkit-box-shadow:none;box-shadow:none}div.rn-alert p:empty{display:none}div.rn-alert ul,div.rn-alert ul li,div.rn-alert ol,div.rn-alert ol li{list-style:inherit !important}div.rn-alert ul,div.rn-alert ol{padding-left:30px}div.rn-alert hr{-moz-box-sizing:content-box;box-sizing:content-box;height:0;margin-top:20px;margin-bottom:20px;border:0;border-top:1px solid #eee}div.rn-alert h1,h2,h3,h4,h5,h6{margin-top:0;color:inherit}div.rn-alert a{font-weight:700}div.rn-alert a:hover{text-decoration:underline}div.rn-alert>p{margin:0;padding:0;line-height:1}div.rn-alert>p,div.rn-alert>ul{margin-bottom:0}div.rn-alert>p+p{margin-top:5px}div.rn-alert .rn-dismiss-btn{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;position:relative;top:-2px;right:-21px;padding:0;cursor:pointer;background:0;border:0;-webkit-appearance:none;float:right;font-size:21px;font-weight:700;line-height:1;color:#000;text-shadow:0 1px 0 #fff;opacity:.2;filter:alpha(opacity=20);text-decoration:none}div.rn-alert-success{background-color:#dff0d8;border-color:#d6e9c6;color:#3c763d}div.rn-alert-success hr{border-top-color:#c9e2b3}div.rn-alert-success a{color:#2b542c}div.rn-alert-info{background-color:#d9edf7;border-color:#bce8f1;color:#31708f}div.rn-alert-info hr{border-top-color:#a6e1ec}div.rn-alert-info a{color:#245269}div.rn-alert-warning{background-color:#fcf8e3;border-color:#faebcc;color:#8a6d3b}div.rn-alert-warning hr{border-top-color:#f7e1b5}div.rn-alert-warning a{color:#66512c}div.rn-alert-danger{background-color:#f2dede;border-color:#ebccd1;color:#a94442}div.rn-alert-danger hr{border-top-color:#e4b9c0}div.rn-alert-danger a{color:#843534}</style>

	<?php }

	/**
	 * Dismiss notice using Ajax
	 *
	 * This function is NOT used. Testing only.
	 */
	public function script() {

		$url = admin_url();
		?>

		<script type="text/javascript">
		jQuery(document).ready(function($) {

			var prout = 'prout';

			$('#rn-dismiss').on('click', function(event) {
				event.preventDefault();
				$.ajax({
					type: "GET",
					url: <?php echo $url; ?>,
					data: prout
				});
				console.log('clicked');
			});

			return false;

		});
		</script>

		<?php

	}

	/**
	 * Debug info.
	 *
	 * Display an error message commented in the admin footer.
	 *
	 * @since  0.1.2
	 */
	public function debug_info() {

		$error = $this->error;

		echo "<!-- RDN Debug Info: $error -->";

	}

}