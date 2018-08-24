<?php
/** 
 * Render one or more tabs on the privacy popup
 *
 * Maybe render the Add/Remove Existing Consent tab
 * Maybe render the Export tickets and user data tab
 * Maybe render the Delete my existing data tab
 *
 * This file is meant to be included in another file 
 * and should not be called directly.
 *
 * @package   Awesome_Support
 * @author    Awesome Support
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 */

// If this file is called directly, abort!
if ( ! defined( 'WPINC' ) ) {
	die;
}
		
if ( true === boolval( wpas_get_option( 'privacy_show_consent_tab', true) ) ) {
	?>
	<button class="tablinks wpas-gdpr-tablinks" onclick="wpas_gdpr_open_tab( event, 'add-remove-consent' )" id="wpas-gdpr-tab-default" data-id="add-remove"><?php esc_html_e( 'Add/Remove Existing Consent', 'awesome-support' ); ?></button>
	<?php			
}

if ( true === boolval( wpas_get_option( 'privacy_show_delete_data_tab', true) ) ) {
	?>
	<button class="tablinks wpas-gdpr-tablinks" onclick="wpas_gdpr_open_tab( event, 'delete-existing-data' )" data-id="delete-existing"><?php esc_html_e( 'Delete my existing data', 'awesome-support' ); ?></button>
	<?php			
}

if ( true === boolval( wpas_get_option( 'privacy_show_export_tab', true) ) ) {
	?>		
	<button class="tablinks wpas-gdpr-tablinks" onclick="wpas_gdpr_open_tab( event, 'export-user-data' )" data-id="export"><?php esc_html_e( 'Export tickets', 'awesome-support' ); ?></button>
	<?php
}

if ( true === boolval( wpas_get_option( 'privacy_show_export_data_tab', true) ) ) {
	?>
	<button class="tablinks wpas-gdpr-tablinks" onclick="wpas_gdpr_open_tab( event, 'export-existing-data' )" data-id="export-existing"><?php esc_html_e( 'Export All Data', 'awesome-support' ); ?></button>
	<?php			
}
