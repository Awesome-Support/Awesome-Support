<?php
/**
 * Ticket Status.
 *
 * This metabox is used to display the ticket current status
 * and change it in one click.
 *
 * For more details on how the ticket status is changed,
 * @see Awesome_Support_Admin::custom_actions()
 *
 * @since 3.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} ?>

<div class="wpas-custom-fields">
	<?php
	/**
	 * Get all custom fields and display them
	 */
	global $wpas_cf;
	$options = $wpas_cf->get_custom_fields();

	if( !empty( $options ) ) {

		/**
		 * wpas_mb_details_before_cfs hook
		 */
		do_action( 'wpas_mb_details_before_cfs' );

		foreach( $options as $option ) {

			$core = isset( $option['args']['core'] ) ? $option['args']['core'] : false;

			/**
			 * Don't display core fields
			 */
			if( $core )
				continue;

			/**
			 * In case we have a custom taxonomy that is handled the usual way
			 */
			if( 'taxonomy' == $option['args']['callback'] && true === $option['args']['taxo_std'] )
				continue;

			/**
			 * Output the field
			 */
			if( method_exists( 'WPAS_Custom_Fields_Display', $option['args']['callback'] ) )
				WPAS_Custom_Fields_Display::$option['args']['callback']( $option );

			/**
			 * wpas_display_custom_fields hook
			 */
			do_action( 'wpas_display_custom_fields', $option );

		}

		/**
		 * wpas_mb_details_after_cfs hook
		 */
		do_action( 'wpas_mb_details_after_cfs' );

	}
	?>
</div>