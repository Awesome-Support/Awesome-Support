<?php
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPAS_CF_Colorpicker extends WPAS_Custom_Field {

	/**
	 * Return the field markup for the front-end.
	 *
	 * @return string Field markup
	 */
	public function display() {
		echo
		'<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/jquery.spectrum/1.3.3/spectrum.css">
		<script type="text/javascript" src="//cdn.jsdelivr.net/jquery.spectrum/1.3.3/spectrum.min.js"></script>
		<script type="text/javascript">
		jQuery(document).ready(function ($) {
			$("#'.$this->get_field_id().'").spectrum({
				change: function (color) {
					console.log(color.toHexString());
				}
			});
		});
		</script>';
		return sprintf( '<label {{label_atts}}>{{label}}</label><br><input type="color" value="%s" {{atts}}>', $this->populate() );
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