<?php
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

	public function __construct( $post_id = null, $contents = '' ) {

		if ( is_null( $post_id ) || empty( $contents ) ) {
			return false;
		}

		$this->post_id  = $post_id;
		$this->contents = $contents;

		$this->log();

	}

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

function wpas_log( $post_id = null, $content = '' ) {

	if ( is_null( $post_id ) || empty( $content ) ) {
		return false;
	}
	
	$log = new WPAS_Log_History( $post_id, $content );

	return $log;
}