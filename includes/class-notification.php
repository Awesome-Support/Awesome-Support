<?php
/**
 * Awesome Support User Notifications.
 *
 * This class is a helper that will generate a notification
 * message for the user, including the message and markup.
 *
 * The notification message can be passed in 3 different ways:
 * using the pre-defined messages, by passing the message directly to this class,
 * or by using a base64 encoded message (useful for passing it as a URL var).
 *
 * @package   Awesome_Support
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */
class WPAS_Notification {

	/**
	 * Notification "case".
	 *
	 * The case defines the type of notification ot be displayed.
	 * A notification can be a success, a failure, etc.
	 * 
	 * @var string
	 */
	public $case = false;

	/**
	 * Notification message.
	 *
	 * The message to display in the notification.
	 * The message can be of various formats, including base64 encoded,
	 * and can contain HTML.
	 * 
	 * @var string
	 */
	public $message = null;

	public function __construct( $case = false, $message = false ) {

		if ( empty( $message ) && isset( $_REQUEST['message'] ) ) {
			$message = $_REQUEST['message'];
		}

		/**
		 * If the case is set, we just need to get the message.
		 */
		if ( $case ) {

			/**
			 * If the case is decode, it means the message has been passed base64 encoded.
			 * We need to decode and sanitize it before displaying the notice.
			 */
			if ( 'decode' === $case && false !== $decoded = base64_decode( (string)$message ) ) {
				$this->message = false !== $message ? $message : $_REQUEST['message'];
				$this->message = esc_attr( json_decode( $decoded ) );
				$this->case    = 'failure'; // Set the case as a failure by default
			} else {

				/**
				 * If the message is passed to the class we try to figure out
				 * if it is the actual message or just a reference to a predefined one.
				 */
				if ( $message ) {

					/**
					 * This is the case where the message is a reference to a pre-defined one.
					 * We can then get the message and the case from here.
					 */
					if ( is_numeric( $message ) && $this->predefined_exists( $message ) ) {
						$predefined    = $this->get_predefined_messages();
						$this->case    = esc_attr( $predefined[$message]['case'] );
						$this->message = esc_attr( $predefined[$message]['message'] );
					}

					/**
					 * If the $message var is a string we assume it is the actual message.
					 * In this case, we just need to get the $case and $message vars to generate
					 * the notice.
					 */
					elseif ( is_string( $message ) ) {
						$this->case    = esc_attr( $case );
						$this->message = esc_attr( $message );
					}

				}

			}

		}

		/**
		 * This can only mean that we have a predefined message
		 * where the case can be retrieved from within the class.
		 */
		elseif ( false === $case && $message ) {

			if ( $this->predefined_exists( $message ) ) {
				$predefined    = $this->get_predefined_messages();
				$this->case    = esc_attr( $predefined[$message]['case'] );
				$this->message = esc_attr( $predefined[$message]['message'] );
			} elseif( false !== $decoded = base64_decode( (string)$message ) ) {
				$this->message = esc_attr( json_decode( $decoded ) );
				$this->case    = 'failure'; // Set the case as a failure by default
			}
		}

	}

	/**
	 * Output the notification
	 */
	public function notify() {

		if ( is_null( $this->message ) || false === $this->case ) {
			return false;
		}

		ob_start();
		$this->template();
		$notification = ob_get_clean();
		
		return $notification;

	}

	public function predefined_exists( $id ) {
		if ( array_key_exists( $id, $this->get_predefined_messages() ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * List of predefined messages
	 */
	public function get_predefined_messages() {

		$messages = array(
			'not_found' => array( 'case' => 'failure', 'message' => __( 'The ticket you requested could not be found.', 'wpas' ) ),
			'0' 		=> array( 'case' => 'success', 'message' => __( 'Your account has been successfully created. You can now post tickets.', 'wpas' ) ),
			'1' 		=> array( 'case' => 'success', 'message' => __( 'Your ticket has been successfully submitted. One of our agents will get in touch with you soon.', 'wpas' ) ),
			'2' 		=> array( 'case' => 'success', 'message' => __( 'Your reply has been sent. Our agent will review it ASAP!', 'wpas' ) ),
			'3' 		=> array( 'case' => 'failure', 'message' => __( 'It is mandatory to provide a title for your issue.', 'wpas' ) ),
			'4' 		=> array( 'case' => 'failure', 'message' => __( 'The authenticity of your submission could not be validated. If this ticket is legitimate please try submitting again.', 'wpas' ) ),
			'5' 		=> array( 'case' => 'failure', 'message' => __( 'Only registered accounts can submit a ticket. Please register first.', 'wpas' ) ),
			'6' 		=> array( 'case' => 'failure', 'message' => __( 'The ticket couldn\'t be submitted for an unknown reason.', 'wpas' ) ),
			'7' 		=> array( 'case' => 'failure', 'message' => __( 'Your reply could not be submitted for an unknown reason.', 'wpas' ) ),
			'8' 		=> array( 'case' => 'success', 'message' => __( 'Your reply has been submitted. Your agent will reply ASAP.', 'wpas' ) ),
			'9' 		=> array( 'case' => 'success', 'message' => __( 'The ticket has been successfully re-opened.', 'wpas' ) ),
			'10' 		=> array( 'case' => 'failure', 'message' => __( 'It is mandatory to provide a description for your issue.', 'wpas' ) ),
			'11' 		=> array( 'case' => 'failure', 'message' => __( 'You do not have the capacity to open a new ticket.', 'wpas' ) ),
			'12' 		=> array( 'case' => 'failure', 'message' => __( 'Registrations are currently not allowed.', 'wpas' ) ),
			'13' 		=> array( 'case' => 'failure', 'message' => __( 'You are not allowed to view this ticket.', 'wpas' ) ),
		);

		return apply_filters( 'wpas_predefined_notifications', $messages );

	}

	/**
	 * Available notification templates
	 */
	public function template() {

		$case    = $this->case;
		$message = wp_kses_post( htmlspecialchars_decode( $this->message ) );

		switch( $case ):

			case 'success':

				if( $message ) {

					?>
					<div class="wpas-alert wpas-alert-success">
						<?php echo $message; ?>
					</div>
					<?php

				}

			break;

			case 'failure':

				if( $message ) {

					?>
					<div class="wpas-alert wpas-alert-danger">
						<?php echo $message; ?>
					</div>
					<?php

				}

			break;

			case 'info':

				if( $message ) {

					?>
					<div class="wpas-alert wpas-alert-info">
						<?php echo $message; ?>
					</div>
					<?php

				}

			break;

		endswitch;

		/**
		 * wpas_notification_markup hook
		 */
		do_action( 'wpas_notification_markup', $case, $message );

	}

}

/**
 * Display notification.
 *
 * This function returns a notification either
 * predefined or customized by the user.
 *
 * @param  string         $case    Type of notification
 * @param  boolean|string $message Message to display
 * @param  boolean        $echo    Whether to echo or return the notification
 *
 * @return string           Notification (with markup)
 * @see    WPAS_Notification
 * @since  3.0.0
 */
function wpas_notification( $case, $message = '', $echo = true ) {

	$notification = new WPAS_Notification( $case, $message );

	if ( true === $echo ) {
		echo $notification->notify();
	}

	return $notification->notify();

}

/**
 * Create custom notification.
 *
 * Takes a custom message and encodes it so that it can be
 * passed safely as a URL parameter.
 *
 * @since  3.0.0
 * @param  string $message Custom message
 * @return string          Encoded message
 */
function wpas_create_notification( $message ) {
	$encoded = urlencode( base64_encode( json_encode( $message ) ) );
	return $encoded;
}