<?php
/**
 * @package   Awesome Support/Admin/Views
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2017 Awesome Support
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$wpas_addons = new WPAS_Addons_Installer(); ?>

<div class="wrap about-wrap">
	<h1><?php echo esc_attr( 'My Addons' ); ?></h1>
	<div class="about-text"><?php echo esc_attr( 'Here is the list of all the addons you have purchased through getawesomesupport.com. You can install those addons in one click from this screen.' ); ?></div>
	<?php if ( false === $wpas_addons->load_api_credentials() ) : ?>
		<div>Before you can install your purchased addons from this screen, you need to connect your getawesomesupport.com account. Please <a href="<?php echo esc_url( get_admin_url( null, 'edit.php?post_type=ticket&page=wpas-settings&tab=addons' ) ); ?>">head over to the settings screen for that.</a></div>
	<?php else : ?>
		<table class="widefat">
			<thead>
				<tr>
					<th class="row-title"><?php esc_attr_e( 'Addon', 'awesome-support' ); ?></th>
					<th><?php esc_attr_e( 'Install', 'awesome-support' ); ?></th>
				</tr>
			</thead>
			<tbody>
			
			</tbody>
			<tfoot>
				<tr>
					<th class="row-title"><?php esc_attr_e( 'Addon', 'awesome-support' ); ?></th>
					<th><?php esc_attr_e( 'Install', 'awesome-support' ); ?></th>
				</tr>
			</tfoot>
		</table>
	<?php endif; ?>
</div>
