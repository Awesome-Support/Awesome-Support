<?php
/**
 * Awesome Support Add/Remove Consent
 *
 * @package   Awesome_Support
 * @author    DevriX
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 */

// If this file is called directly, abort!
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wpas-gdpr-pre-loader">
	<div class="loader"></div><!-- .loader -->
</div>
<div class="wpas-gdpr-notice add-remove-consent"></div>

<table class="form-table">
	<thead>
		<tr class="headlines">
			<th><?php esc_html_e( 'Item', 'awesome-support' ); ?></th>
			<th><?php esc_html_e( 'Status', 'awesome-support' ); ?></th>
			<th><?php esc_html_e( 'Opt-in Date', 'awesome-support' ); ?></th>
			<th><?php esc_html_e( 'Opt-out Date', 'awesome-support' ); ?></th>
			<th><?php esc_html_e( 'Action', 'awesome-support' ); ?></th>
		</tr>
	</thead>
	<?php
	 /**
	  * For the GDPR labels, this data are stored in
	  * wpas_consent_tracking user meta in form of array.
	  * Get the option and if not empty, loop them here
	  */
	  $user_consent = get_user_option( 'wpas_consent_tracking', get_current_user_id() );
	if ( ! empty( $user_consent ) && is_array( $user_consent ) ) {
		foreach ( $user_consent as $consent ) {
			/**
			 * Determine if current loop is TOR
			 * Display TOR as label instead of content
			 * There should be no Opt buttons
			 */
			$item = isset( $consent['item'] ) ? $consent['item'] : '';
			if ( isset( $consent['is_tor'] ) && $consent['is_tor'] === true ) {
				$item = __( 'Terms and Conditions', 'awesome-support' );
			}

			/**
			 * Determine status
			 * Raw data is boolean, we convert it into string
			 */
			$status = '';
			if ( isset( $consent['status'] ) && ! empty( $consent['status'] ) ) {
				if ( $consent['status'] == 1 ) {
					$status = __( 'Opted-in', 'awesome-support' );
				} else {
					$status = $consent['status'];
				}
			}

			/**
			 * Convert Opt content into date
			 * We stored Opt data as strtotime value
			 */
			$opt_in  = isset( $consent['opt_in'] ) && ! empty( $consent['opt_in'] ) ? date( 'm/d/Y', $consent['opt_in'] ) : '';
			$opt_out = isset( $consent['opt_out'] ) && ! empty( $consent['opt_out'] ) ? date( 'm/d/Y', $consent['opt_out'] ) : '';

			/**
			 * Determine 'Action' buttons
			 * If current loop is TOR, do not give Opt options
			 */
			$opt_button       = '';
			$opt_button_label = '';
			if ( isset( $consent['is_tor'] ) && $consent['is_tor'] == false ) {
				$gdpr_id = wpas_get_gdpr_data( $item );
				/**
				 * Determine what type of buttons we should render
				 * If opt_in is not empty, display Opt out button
				 * otherwise, just vice versa
				*/
				if ( ! empty( $opt_in ) && wpas_get_option( 'gdpr_notice_opt_out_ok_0' . $gdpr_id, false ) ) {
					$opt_button       = sprintf(
						'<a href="#" class="button button-secondary wpas-button wpas-gdpr-opt-out" data-gdpr="' . $item . '" data-user="' . get_current_user_id() . '" data-optin-date="' . $opt_in . '">%s</a>',
						__( 'Opt-out', 'awesome-support' )
					);
					$opt_button_label = __( 'Opt-out', 'awesome-support' );
				} elseif ( ! empty( $opt_out ) ) {
					$opt_button       = sprintf(
						'<a href="#" class="button button-secondary wpas-button wpas-gdpr-opt-in" data-gdpr="' . $item . '" data-user="' . get_current_user_id() . '" data-optout-date="' . $opt_out . '">%s</a>',
						__( 'Opt-in', 'awesome-support' )
					);
					$opt_button_label = __( 'Opt-in', 'awesome-support' );
				} elseif ( empty( $opt_in ) && empty( $opt_out ) ) {
					$opt_button       = sprintf(
						'<a href="#" class="button button-secondary wpas-button wpas-gdpr-opt-in" data-gdpr="' . $item . '" data-user="' . get_current_user_id() . '">%s</a>',
						__( 'Opt-in', 'awesome-support' )
					);
					$opt_button_label = __( 'Opt-in', 'awesome-support' );
				}
			}

			/**
			 * Render data
			 */
			printf(
				'<tr><td data-label="%s">%s</td><td data-label="%s">%s</td><td data-label="%s">%s</td><td data-label="%s">%s</td><td data-label="%s">%s</td></tr>',
				$item,
				$item,
				$status,
				$status,
				$opt_in,
				$opt_in,
				$opt_out,
				$opt_out,
				$opt_button_label,
				$opt_button
			);
		}
	}
			?>

</table>
