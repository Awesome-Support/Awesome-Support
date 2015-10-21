<?php
/**
 * @package   Awesome Support/Admin/Reply
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<td colspan="3">
	<?php printf( __( 'This reply has been deleted by %s <em class="wpas-time">%s ago.</em>', 'awesome-support' ), "<strong>$user_name</strong>", human_time_diff( strtotime( $row->post_modified ), current_time( 'timestamp' ) ) ); ?>
</td>
