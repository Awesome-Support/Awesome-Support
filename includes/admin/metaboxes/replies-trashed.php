<?php
/**
 * @package   Awesome Support/Admin/Reply
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<td colspan="3">
	<?php printf( wp_kses_post( __( 'This reply has been deleted by %s <em class="wpas-time">%s ago.</em>', 'awesome-support' ) ), '<strong>' . esc_html( $user_name ) . '</strong>', esc_html( human_time_diff( strtotime( $row->post_modified ), current_time( 'timestamp' ) ) ) ); ?>
</td>
