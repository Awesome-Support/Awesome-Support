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

/**
 * wpas_backend_history_content_before hook
 *
 * @since  3.0.0
 */
do_action( 'wpas_backend_history_content_before', $row->ID );

/* Filter the content before we display it */
$content = apply_filters( 'the_content', $row->post_content );

/**
 * wpas_backend_history_content_after hook
 *
 * @since  3.0.0
 */
do_action( 'wpas_backend_history_content_after', $row->ID ); ?>

<td colspan="3">
	<span class="wpas-action-author"><?php echo $user_name; ?>, <em class='wpas-time'><?php printf( __( '%s ago', 'awesome-support' ), $date ); ?></em></span>
	<div class="wpas-action-details"><?php echo $content; ?></div>
</td>