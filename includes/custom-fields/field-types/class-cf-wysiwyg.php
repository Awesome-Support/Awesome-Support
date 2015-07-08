<?php
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPAS_CF_Wysiwyg extends WPAS_Custom_Field {

	public $default_field_class = 'wpas-wysiwyg';

	/**
	 * Return the field markup for the front-end.
	 *
	 * @return string Field markup
	 */
	public function display() {

		$editor = '';

		/**
		 * Check if the description field should use the WYSIWYG editor
		 *
		 * @var string
		 */
		$wysiwyg = boolval( wpas_get_option( 'frontend_wysiwyg_editor' ) );

		if ( true === $wysiwyg || is_admin() ) {

			$editor_defaults = array(
				'media_buttons' => false,
				'textarea_name' => $this->get_field_id(),
				'textarea_rows' => 10,
				'tabindex'      => 2,
				'editor_class'  => $this->get_field_class(),
				'quicktags'     => false,
				'tinymce'       => array(
					'toolbar1' => 'bold,italic,underline,strikethrough,hr,|,bullist,numlist,|,link,unlink',
					'toolbar2' => ''
				),
			);

			/* Merge custom editor settings if any */
			$args       = isset( $this->field_args['editor'] ) && is_array( $this->field_args['editor'] ) ? wp_parse_args( $this->field_args['editor'], $editor_defaults ) : $editor_defaults;
			$wysiwyg_id = str_replace( '_', '-', $this->get_field_id() ); // The codex says the opposite, but underscores causes issues while hyphens don't. Weird...

			ob_start();

			wp_editor( $this->populate(), $wysiwyg_id, $args );

			/* Get the buffered content into a var */
			$editor = ob_get_contents();

			/* Clean buffer */
			ob_end_clean();

			$editor = "<label {{label_atts}}>{{label}}</label><div class='wpas-submit-ticket-wysiwyg'>$editor</div>";

		} else {

			$path = WPAS_PATH . "includes/custom-fields/field-types/class-cf-textarea.php";

			if ( file_exists( $path ) ) {

				include_once( $path );

				$textarea = new WPAS_CF_Textarea( $this->field_id, $this->field );
				$editor   = $textarea->display();

			}

		}

		return $editor;

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