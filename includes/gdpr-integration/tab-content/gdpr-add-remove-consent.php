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
	<tr>
		<td><?php esc_html_e( 'Terms and Conditions', 'awesome-support' ); ?></td>
		<td><?php esc_html_e( '', 'awesome-support' ); ?></td>
		<td><?php esc_html_e( '', 'awesome-support' ); ?></td>
		<td><?php esc_html_e( '', 'awesome-support' ); ?></td>
		<td></td>
	</tr>
	<?php
	 /**
	  * Get GDPR labels from WPAS option. The content from the backend
	  * reflects in this tab. We do not modify it or save in new option yet
	  */
	  $gdpr_one   = wpas_get_option( 'gdpr_notice_short_desc_01' );
	  $gdpr_two   = wpas_get_option( 'gdpr_notice_short_desc_02' );
	  $gdpr_three = wpas_get_option( 'gdpr_notice_short_desc_03' );

	if ( ! empty( $gdpr_one ) ) {
		?>
		<tr>
			<td data-label="<?php esc_html_e( 'Item', 'awesome-support' ); ?>"><?php echo $gdpr_one; ?></td>
			<td data-label="<?php esc_html_e( 'Status', 'awesome-support' ); ?>"><?php esc_html_e( '', 'awesome-support' ); ?></td>
			<td data-label="<?php esc_html_e( 'Opt-in Date', 'awesome-support' ); ?>"><?php esc_html_e( '', 'awesome-support' ); ?></td>
			<td data-label="<?php esc_html_e( 'Action', 'awesome-support' ); ?>"><?php esc_html_e( '', 'awesome-support' ); ?></td>
			<td>
				<button><?php esc_html_e( 'Opt In', 'awesome-support' ); ?></button>
				<?php
				if ( wpas_get_option( 'gdpr_notice_opt_out_ok_01' ) ) {
					?>
					  <button><?php esc_html_e( 'Opt Out', 'awesome-support' ); ?></button>
					<?php
				}
				?>
			</td>
		</tr>
		<?php
	}

	if ( ! empty( $gdpr_two ) ) {
		?>
		<tr>
			<td data-label="<?php esc_html_e( 'Item', 'awesome-support' ); ?>"><?php echo $gdpr_two; ?></td>
			<td data-label="<?php esc_html_e( 'Status', 'awesome-support' ); ?>"><?php esc_html_e( '', 'awesome-support' ); ?></td>
			<td data-label="<?php esc_html_e( 'Opt-in Date', 'awesome-support' ); ?>"><?php esc_html_e( '', 'awesome-support' ); ?></td>
			<td data-label="<?php esc_html_e( 'Action', 'awesome-support' ); ?>"><?php esc_html_e( '', 'awesome-support' ); ?></td>
			<td>
				<button><?php esc_html_e( 'Opt In', 'awesome-support' ); ?></button>
				<?php
				if ( wpas_get_option( 'gdpr_notice_opt_out_ok_02' ) ) {
					?>
						<button><?php esc_html_e( 'Opt Out', 'awesome-support' ); ?></button>
					<?php
				}
				?>
			</td>
		</tr>
		<?php
	}

	if ( ! empty( $gdpr_three ) ) {
		?>
		<tr>
			<td data-label="<?php esc_html_e( 'Item', 'awesome-support' ); ?>"><?php echo $gdpr_three; ?></td>
			<td data-label="<?php esc_html_e( 'Status', 'awesome-support' ); ?>"><?php esc_html_e( '', 'awesome-support' ); ?></td>
			<td data-label="<?php esc_html_e( 'Opt-in Date', 'awesome-support' ); ?>"><?php esc_html_e( '', 'awesome-support' ); ?></td>
			<td data-label="<?php esc_html_e( 'Action', 'awesome-support' ); ?>"><?php esc_html_e( '', 'awesome-support' ); ?></td>
			<td>
				<button><?php esc_html_e( 'Opt In', 'awesome-support' ); ?></button>
				<?php
				if ( wpas_get_option( 'gdpr_notice_opt_out_ok_03' ) ) {
					?>
					<button><?php esc_html_e( 'Opt Out', 'awesome-support' ); ?></button>
					<?php
				}
				?>
			</td>
		</tr>
		<?php
	}
	?>
</table>
