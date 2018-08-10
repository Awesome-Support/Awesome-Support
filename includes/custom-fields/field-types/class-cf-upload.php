<?php
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPAS_CF_Upload extends WPAS_Custom_Field {

	public function __construct( $field_id, $field ) {

		/* Call the parent constructor */
		parent::__construct( $field_id, $field );
		
		// Get default value for this field
		$defaults = $this->get_field_defaults();		

		/* Set the additional parameters */
		if ( ! isset( $this->field_args['multiple'] ) ) {
			$this->field_args['multiple'] = false;
		}

		if ( ! isset( $this->field_args['use_ajax_uploader'] ) ) {
			$this->field_args['use_ajax_uploader'] = $defaults['use_ajax_uploader'];			
		}
		
		/* Force ajax upload if option to enable in settings is turned on... */
		if ( boolval( wpas_get_option( 'ajax_upload_all', false ) ) ) {
			$this->field_args['use_ajax_uploader'] = true;
		}
		
		/* Paste option */
		if ( ! isset( $this->field_args['enable_paste'] ) ) {			
			$this->field_args['enable_paste'] = $defaults['enable_paste'];
		}
		
		/* Force paste if option to enable in settings is turned on */
		if ( boolval( wpas_get_option( 'ajax_upload_paste_image_all', false ) ) ) {
			$this->field_args['enable_paste'] = true;
		}
		

		/* Change the field name if multiple upload is enabled */
		add_filter( 'wpas_cf_field_atts', array( $this, 'edit_field_atts' ), 10, 3 );

	}

	/**
	 * Add the brackets to field name if multiple upload is enabled
	 *
	 * @since  3.2
	 *
	 * @param array  $atts   Field attributes
	 * @param string $field  Field markup
	 * @param array  $option The custom field attributes
	 *
	 * @return mixed
	 */
	public function edit_field_atts( $atts, $field, $option ) {

		if ( $option['name'] !== $this->field_id ) {
			return $atts;
		}

		if ( false === $this->field_args['multiple'] ) {
			return $atts;
		}

		foreach ( $atts as $key => $att ) {
			if ( 'name' === substr( $att, 0, 4 ) ) {
				$att = substr( $att, 0, - 1 ); // Get rid of the last char (closing backtick)
				$att .= '[]\''; // Add the brackets for handling multiple files
				$atts[ $key ] = $att; // Update the array of attributes
			}
		}

		return $atts;

	}

	/**
	 * Return the field markup for the front-end.
	 *
	 * @return string Field markup
	 */
	public function display() {

		// Ajax uploader?
		$ajax = ( $this->field_args['use_ajax_uploader'] === true ) ? true : false;

		if ( $ajax ) {
			return '<label {{label_atts}}>{{label}}</label><div class="wpas-uploader-dropzone dropzone" id="dropzone-' . $this->field_id . '" data-ticket-id="' . get_the_ID() . '" data-enable-paste="' . boolval( $this->field_args['enable_paste'] ). '"><div class="dz-message" data-dz-message><span>' . __( 'Drop files here to upload', 'awesome-support' ). '</span></div></div>';
		}

		// Non ajax uploader
		$multiple  = true === $this->field_args['multiple'] ? 'multiple' : '';
		$filetypes = explode( ',', apply_filters( 'wpas_attachments_filetypes', wpas_get_option( 'attachments_filetypes' ) ) );
		$accept    = array();

		foreach ( $filetypes as $key => $type ) {
			$filetypes[ $key ] = "<code>.$type</code>";
			array_push( $accept, ".$type" );
		}

		$accept = implode( ',', $accept );

		return sprintf( '<label {{label_atts}}>{{label}}</label><input style="height:auto;" type="file" value="%s" {{atts}} accept="%s" %s>', $this->populate(), $accept, $multiple );
	}

	/**
	 * Return the field markup for the admin.
	 *
	 * This method is only used if the current user
	 * has the capability to edit the field.
	 */
	public function display_admin() {
		return $this->display();
	}

	/**
	 * Return the field markup for the admin.
	 *
	 * This method is only used if the current user
	 * doesn't have the capability to edit the field.
	 */
	public function display_no_edit() {
		return sprintf( '<div class="wpas-cf-noedit-wrapper"><div id="%s-label" class="wpas-cf-label">%s</div><div id="%s-value" class="wpas-cf-value">%s</div></div>', $this->get_field_id(), $this->get_field_title(), $this->get_field_id(), $this->get_field_value() );
	}

}