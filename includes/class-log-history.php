<?php
/**
 * Awesome Support Log History
 *
 * @package   Awesome Support
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2018 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WPAS_Log_History
 *
 * This class is used to log changes to custom fields to the ticket_history CPT.
 * It can log a single string or it can log a series of before and after changes 
 * inside the custom fields array object.
 *
 * The structure of this is interesting in that the 2nd parameter of the constructor
 * function can be a string or an array.  When its an string, its the contents 
 * that should be written directly to the CPT.  But when it is an array,
 * it is NOT the contents that need to be logged - instead when an array is passed, 
 * changes to the custom fields object is logged and this array parm is used as 
 * additional information to be added to the log.
 *
 * @since 3.3
 */
class WPAS_Log_History {

	/**
	 * ID of the post to log history for.
	 * 
	 * @var integer
	 */
	private $post_id = null;

	/**
	 * Content to log.
	 * 
	 * @var string
	 */
	private $contents = '';

	
	/**
	 * WPAS_Log_History Constructor 
	 *
	 * This function can handle EITHER a string or 
	 * an array which will be used when logging custom
	 * fields changes.
	 *
	 * @since 3.3
	 *
	 * @param int 			Postid related to the item being logged (usually a ticket id)
	 * @param string|array	A simple string or an array with information to be included when logging custom field changes.
	 *
	 * @return void
	 */		
	public function __construct( $post_id = null, $contents = '' ) {

		if ( is_null( $post_id ) || empty( $contents ) ) {
			return false;
		}

		$this->post_id  = $post_id;
		$this->contents = $contents;

		$this->log();

	}

	/**
	 * Create a log string out of a custom fields array
	 *
	 * @since 3.3
	 *
	 * @param void
	 *
	 * @return string
	 */
	public function create_log() {

		$content = '';

		if( !empty( $this->contents ) ) {

			/* Get custom fields */
			$fields = WPAS()->custom_fields->get_custom_fields();

			$content .= '<ul class="wpas-log-list">';

			foreach ( $this->contents as $key => $update ) {

				/* Make sure we have the minimum information required to log the action */
				if ( !isset( $update['action'] ) || !isset( $update['label'] ) || !isset( $update['field_id'] ) ) {
					continue;
				}

				/* Verify that the current field is actually registered */
				if ( !array_key_exists( $update['field_id'], $fields ) ) {
					continue;
				}

				/* For custom fields, check if log function is enabled */
				if ( false === $fields[$update['field_id']]['args']['log'] ) {
					continue;
				}


				$action = $update['action'];
				$label  = $update['label'];
				$value  = isset( $update['value'] ) ? $update['value'] : '';

				/**
				 * Assignee is a specific case. We transform its value from ID to username
				 */
				if( 'assignee' == $update['field_id'] ) {

					$assignee = get_user_by( 'id', $value );
					$value    = $assignee->display_name;

				}

				$content .= '<li>';

				switch( $action ):

					case 'updated':
						$content .= sprintf( _x( 'updated %s to %s', 'Custom field value was updated', 'awesome-support' ), $label, $value );
						break;

					case 'deleted':
						$content .= sprintf( _x( 'deleted %s', 'Custom field value was deleted', 'awesome-support' ), $label );
						break;

					case 'added':
						$content .= sprintf( _x( 'added %s to %s', 'Custom field value was added', 'awesome-support' ), $value, $label );
						break;

				endswitch;

				$content .= "</li>";

			}

			$content .= '</ul>';

		}

		/**
		 * In case the $args was not empty but none of the fields were to be logged
		 */
		if( '<ul></ul>' == $content )
			$content = '';

		return $content;

	}

	/**
	 * Create a history log entry.
	 *
	 * This function can handle either a string or 
	 * the custom fields object (by calling the 
	 * $this->create_log() function above)
	 *
	 * @since 3.3
	 *
	 * @param void
	 *
	 * @return boolean|int  ID of log entry in ticket_history CPT or false if unsucessful
	 */	
	public function log() {

		/**
		 * Get user info
		 */
		global $current_user;

		$user_id = $current_user->ID;

		if ( is_array( $this->contents ) ) {
			
			/**
			 * If the content is an array we need to build a complex
			 * content based on custom fields.
			 */
			$content = $this->create_log();
			
		} else {
			
			$content = wp_kses( $this->contents, wp_kses_allowed_html( 'post' ) );
			
		}

		if( '' === $content ) {
			return false;
		}

		$post = array(
			'post_content'   => $content,
			'post_status'    => 'publish',
			'post_type'      => 'ticket_history',
			'post_author'    => $user_id,
			'ping_status'    => 'closed',
			'post_parent'    => $this->post_id,
			'comment_status' => 'closed',
		);

		/**
		 * Remove the save_post hook now as we're going to trigger
		 * a new one by inserting the reply (and logging the history later).
		 */
		if( is_admin() ) {
			remove_action( 'save_post', 'wpas_save_ticket' );
		}

		$log = wp_insert_post( $post, true );

		return $log;

	}

}

/**
 * Helper function to create a log history entry.
 *
 * This function can be called from anywhere inside
 * Awesome Support.
 *
 * @since 3.3
 *
 * @param int 			Postid related to the item being logged (usually a ticket id)
 * @param string|array	A simple string or an array with information to be included when logging custom field changes.
 *
 * @return boolean|int  ID of log entry in ticket_history CPT or False if unsucessful
 */	

function wpas_log( $post_id = null, $content = '' ) {

	if ( is_null( $post_id ) || empty( $content ) ) {
		return false;
	}
	
	$log = new WPAS_Log_History( $post_id, $content );

	return $log;
}