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
		<div class="loader"></div><!-- .loader -->
	</div>
	<div class="wpas-gdpr-notice"></div>
	<table class="form-table wpas-gdpr-form-table">
		<thead>
			<tr class="headlines">
				<th><?php esc_html_e( 'Subject', 'awesome-support' ); ?></th>
			</tr>
		</thead>
		<tr>
			<td data-label="Subject"><input type="text" name="wpas-gdpr-ded-subject" id="wpas-gdpr-ded-subject" readonly="readonly" value="<?php esc_html_e( 'Official Request: Please Delete My Existing Data ("Right To Be Forgotten")', 'awesome-support' ); ?>" /></td>
		</tr>
		<thead>
			<tr class="headlines">
				<th><?php esc_html_e( 'Additional Information', 'awesome-support' ); ?></th>
			</tr>
		</thead>
		<tr>
			<td data-label="Additional Information"><textarea name="wpas-gdpr-ded-more-info" id="wpas-gdpr-ded-more-info" ></textarea></td>
		</tr>
		<tr>
			<td><input type="submit" name="wpas-gdpr-ded-submit" id="wpas-gdpr-ded-submit" class="button button-primary" value="Submit"></td>
		</tr>
	</table>
</form>
