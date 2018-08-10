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
 * This class is used to log changes to custom fields to the ticket_history CPT 
 * and/or the ticket_log CPT.
 *
 * The ticket_history CPT is used to store simple strings "eg: agent changed to xyz".
 * The ticket_log CPT is used to to store the original value of a reply or ticket
 * before it was changed by an agent/admin.
 * 
 * It can log a single string or it can log a series of before and after changes 
 * inside the custom fields array object.
 *
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
	 * Orignal Content to log when logging contents.
	 * 
	 * @var string
	 */
	private $orignal_contents = '';
	
	/**
	 * WPAS_Log_History Constructor 
	 *
	 * @since 3.3
	 *
	 * @return void
	 */		
	public function __construct(  ) {

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
	public function create_log_cf_string() {

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
	 * The structure of this is interesting in that the 2nd parameter  can be a string or an array.  
	 * When its an string, its the contents that should be written directly to the CPT.  
	 * But when it is an array, it is NOT the contents that need to be logged.
	 * Instead when an array is passed, changes to the custom fields object is logged and 
	 * this array parm is used as additional information to be added to the log.
	 *
	 * @since 3.3
	 *
	 * @param int 			Postid related to the item being logged (usually a ticket id)
	 * @param string|array	A simple string or an array with information to be included when logging custom field changes.
	 *
	 * @return boolean|int  ID of log entry in ticket_history CPT or false if unsucessful
	 */	
	public function log_history( $post_id = null, $contents = '' ) {
		
		/* If parms are blank, return */
		if ( is_null( $post_id ) || empty( $contents ) ) {
			return false;
		}

		/**
		 * Set class instance variables to parameters passed in
		 */		
		$this->post_id  = $post_id;
		$this->contents = $contents;		

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
			$content = $this->create_log_cf_string();
			
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

	/**
	 * Create a a log of edits that will be entered into the ticket_log CPT
	 * Used to log edits to ticket replies and the opening ticket post.
	 *
	 * @since 5.2.0
	 *
	 * @param int 			$postid - The postid related to the item being logged (usually a ticket id or a reply id)
	 * @param string|array	$content - A simple string with a summary line that goes at the top of the edit history.
	 *									eg: "This item was edited. Below is the original contents.".
	 * @param string		$original - A string that contains the original text being edited.  This will go into the ticket_log post type
	 *
	 * @return boolean|int  ID of log entry in ticket_history CPT or false if unsucessful
	 */		
	public function log_edits( $post_id = null, $contents = '', $original = false ) {
		
		/* Make sure we have data to log otherwise just return false...*/
		if ( ( ! $post_id ) || ( is_null( $post_id ) ) || ( ! $contents ) ) {
			return false ;
		}
		
		/**
		 * Set class instance variables to parameters passed in
		 */		
		$this->post_id  = $post_id;
		$this->contents = $contents;		
		$this->orignal_contents = $original;

		/**
		 * Get user info
		 */
		global $current_user;

		$user_id = $current_user->ID;
		$edit_content = "";
		
		/* Put stuff into ticket_log post type if $original was provided */
		if ( $original && ( ! empty( $original ) ) ) {
			$edit_content = $contents . "<br /> <br />" . $original;
		} else  {
			$edit_contents = $contents ;
		}

		$post = array(
			'post_title'	 => $this->contents,
			'post_content'   => $edit_content,
			'post_status'    => 'publish',
			'post_type'      => 'ticket_log',
			'post_author'    => $user_id,
			'ping_status'    => 'closed',
			'post_parent'    => $this->post_id,
			'comment_status' => 'closed',
		);
		
		$log = wp_insert_post( $post, true );			
		
		return $log ;
		
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
 * @return boolean|int  ID of log entry in ticket_history CPT or false if unsucessful
 */	

function wpas_log( $post_id = null, $content = '' ) {

	if ( is_null( $post_id ) || empty( $content ) ) {
		return false;
	}
	
	$logger = new WPAS_Log_History();
	
	$log = $logger->log_history( $post_id, $content );

	return $log;
}

/**
 * Alias function for wpas_log().  Once all the occurences of wpas_log 
 * has been changed in core and all addons to wpas_log_history() 
 * we will delete this function and renam wpas_log() to wpas_log_history().
 *
 * wpas_log() as a function name is just too generic given the different
 * types of logs that exist inside of Awesome Support.
 */
function wpas_log_history( $post_id = null, $content = '' ) {
	return wpas_log( $post_id , $content );
}

/**
 * Helper function to create a history of edits to a reply or ticket
 *
 * This function can be called from anywhere inside
 * Awesome Support.
 *
 * @since 5.2.0
 *
 * @param int 			$postid - The postid related to the item being logged (usually a ticket id)
 * @param string|array	$content - A simple string that will be added to the ticket_history post type.  This will show up under the replies.
 * @param string		$original - A string that contains the original text being edited.  This will go into the ticket_log post type
 *
 * @return boolean|int  ID of log entry in ticket_history CPT or false if unsucessful
 */	

function wpas_log_edits( $post_id, $content, $original ) {

	if ( is_null( $post_id ) || empty( $content ) ) {
		return false;
	}
	
	$logger = new WPAS_Log_History();
	
	$log = $logger->log_edits( $post_id, $content, $original );

	return $log;
}