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

// translators: %1$s is the person who deleted the reply, %2$s is the time ago.
$x_content = __( 'This reply has been deleted by %1$s <em class="wpas-time">%2$s ago.</em>', 'awesome-support' );

?>
<td colspan="3">
	<?php printf( wp_kses_post( $x_content ), '<strong>' . esc_html( $user_name ) . '</strong>', esc_html( human_time_diff( strtotime( $row->post_modified ), current_time( 'timestamp' ) ) ) ); ?>
</td>
