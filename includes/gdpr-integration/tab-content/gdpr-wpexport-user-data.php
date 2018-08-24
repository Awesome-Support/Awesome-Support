<?php
/**
 * Awesome Support Export Existing Data
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

// Get subject based on settings option!
$subject = __( 'Official Request: Please Export My Existing Data.', 'awesome-support' );
if( wpas_get_option( 'export_existing_data_subject', false ) ) {
	$subject = wpas_get_option( 'export_existing_data_subject', false );
}
?>
<form name="wpas-gdpr-rted-form" id="wpas-gdpr-rted-form">
	<div class="wpas-gdpr-pre-loader">
		<div class="loader"></div><!-- .loader -->
	</div>
	<div class="wpas-gdpr-notice export-existing-data"></div>
	<input type="hidden" name="wpas-user" value="<?php echo get_current_user_id(); ?>">
	<table class="form-table wpas-gdpr-form-table">
		<thead>
			<tr class="headlines">
				<th><?php esc_html_e( 'Subject', 'awesome-support' ); ?></th>
			</tr>
		</thead>
		<tr>
			<td data-label="Subject"><input type="text" name="wpas-gdpr-ded-subject" id="wpas-gdpr-ded-subject" readonly="readonly" value='<?php echo stripslashes_deep( $subject ); ?>' /></td>
		</tr>
		<?php
		/**
		 * Check if this is enabled in the settings option
		 * before we can render the markup
		 */
		if( wpas_get_option( 'export_existing_data_add_information', false ) ) {
		?>
		<thead>
			<tr class="headlines">
				<th><?php esc_html_e( 'Additional Information', 'awesome-support' ); ?></th>
			</tr>
		</thead>
		<tr>
			<td data-label="Additional Information"><textarea name="wpas-gdpr-export-more-info" id="wpas-gdpr-export-more-info" ></textarea></td>
		</tr>
		<?php
		}
		?>
		<tr>
			<td><input type="submit" name="wpas-gdpr-export-submit" id="wpas-gdpr-export-submit" class="button button-primary" value="Submit"></td>
		</tr>
	</table>
</form>
