<?php
/**
 * Awesome Support Delete Existing Data
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
<form name="wpas-gdpr-rtbf-form" id="wpas-gdpr-rtbf-form">
	<div class="wpas-gdpr-pre-loader">
		<img id="loader-img" alt="" src="<?php echo WPAS_URL . 'assets/public/images/loading.gif'; ?>" width="50" align="center" />
	</div>
	<div class="wpas-gdpr-notice"></div>
	<table class="form-table wpas-gdpr-form-table">
		<tr>
			<th><?php esc_html_e( 'Subject', 'awesome-support' ); ?></th>
		</tr>
		<tr>
			<td><input type="text" name="wpas-gdpr-ded-subject" id="wpas-gdpr-ded-subject" readonly="readonly" value="<?php esc_html_e( 'Official Request: Please Delete My Existing Data ("Right To Be Forgotten")', 'awesome-support' ); ?>" /></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Additional Information', 'awesome-support' ); ?></th>
			</tr>
		<tr>
			<td><textarea name="wpas-gdpr-ded-more-info" id="wpas-gdpr-ded-more-info" ></textarea></td>
		</tr>
		<tr>
			<td><?php submit_button( __( 'Submit', 'awesome-support' ), 'primary', 'wpas-gdpr-ded-submit' ); ?></td>
		</tr>
	</table>
</form>
