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

// Get subject based on settings option!
$subject = __( 'Official Request: Please Delete My Existing Data ("Right To Be Forgotten").', 'awesome-support' );
if( wpas_get_option( 'delete_existing_data_subject', false ) ) {
	$subject = wpas_get_option( 'delete_existing_data_subject', false );
}

?>
<form name="wpas-gdpr-rtbf-form" id="wpas-gdpr-rtbf-form">
	<div class="wpas-gdpr-pre-loader">
		<div class="loader"></div><!-- .loader -->
	</div>
	<div class="wpas-gdpr-notice delete-existing-data"></div>
	<input type="hidden" name="wpas-user" value="<?php echo get_current_user_id(); ?>">
	<table class="form-table wpas-gdpr-form-table">
		<thead>
			<tr class="headlines">
				<th><?php esc_html_e( 'Subject', 'awesome-support' ); ?></th>
			</tr>
		</thead>
		<tr>
			<td data-label="Subject"><input type="text" name="wpas-gdpr-ded-subject" id="wpas-gdpr-ded-subject" readonly="readonly" value='<?php echo stripslashes_deep ( htmlentities( $subject, ENT_QUOTES  ) ); ?>' /></td>
		</tr>
		<?php
		/**
		 * Check if this is enabled in the settings option
		 * before we can render the markup
		 */
		if( wpas_get_option( 'delete_existing_data_add_information', false ) ) {
		?>
		<thead>
			<tr class="headlines">
				<th><?php esc_html_e( 'Additional Information', 'awesome-support' ); ?></th>
			</tr>
		</thead>
		<tr>
			<td data-label="Additional Information"><textarea name="wpas-gdpr-ded-more-info" id="wpas-gdpr-ded-more-info" ></textarea></td>
		</tr>
		<?php
		}
		?>
		<tr>
			<td><input type="submit" name="wpas-gdpr-ded-submit" id="wpas-gdpr-ded-submit" class="button button-primary" value="Submit"></td>
		</tr>
	</table>
</form>
