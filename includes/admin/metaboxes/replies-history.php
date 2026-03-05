<?php
/**
 * @package   Ayuda – Help Desk/Admin/Reply
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
 * mumei_ayuda_backend_history_content_before hook
 *
 * @since  3.0.0
 */
do_action( 'mumei_ayuda_backend_history_content_before', $row->ID );

/* Filter the content before we display it */
$content = apply_filters( 'the_content', $row->post_content );

/**
 * mumei_ayuda_backend_history_content_after hook
 *
 * @since  3.0.0
 */
do_action( 'mumei_ayuda_backend_history_content_after', $row->ID ); ?>

<td colspan="3">
	<?php 
		// translators: %s is the date ago.
		$x_content = __( '%s ago', 'ayuda-help-desk' );
	?>
	<span class="wpas-action-author"><?php echo esc_html( $user_name ); ?>, <em class='wpas-time'><?php printf( esc_html( $x_content ), esc_attr( $date ) ); ?></em></span>
	<div class="wpas-action-details"><?php echo wp_kses_post( $content ); ?></div>
</td>