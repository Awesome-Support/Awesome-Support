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
} ?>

<div class="wrap about-wrap">
	<h1><?php echo esc_attr( 'My Addons' ); ?></h1>
	<div class="about-text"><?php echo esc_attr( 'Here is the list of all the addons you have purchased through getawesomesupport.com. You can install those addons in one click from this screen.' ); ?></div>
	<?php if ( false === wpas_edd_api_credentials_set() ) : ?>
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
			<?php foreach ( wpas_get_user_purchased_addons() as $download ) : ?>
				<tr>
					<td>
						<a href="<?php echo esc_url( $download->product_link ); ?>" target="_blank"><?php echo esc_html( $download->name ); ?></a>
					</td>
					<td>
						<a href="#"><?php esc_html_e( 'Install', 'awesome-support' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
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
