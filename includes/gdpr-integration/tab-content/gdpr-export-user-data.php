<?php
/**
 * Awesome Support Export Tickets Template
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
<div class="wpas-gdpr-notice export-data"></div>

<input type="submit" name="wpas-gdpr-export-data-submit" id="wpas-gdpr-export-data-submit" data-user="<?php echo get_current_user_id(); ?>" class="button button-primary" value="<?php _e( 'Export my tickets', 'awesome-suppot' ); ?>">