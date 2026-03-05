<?php

/**
 * @package   Ayuda – Help Desk/Admin/Functions/Misc
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2015-2017 AwesomeSupport
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

add_filter('plugin_action_links_' . MUMEI_AYUDA_PLUGIN_BASENAME, 'mumei_ayuda_settings_page_link');
/**
 * Add a link to the settings page.
 *
 * @since  3.1.5
 *
 * @param  array $links Plugin links
 *
 * @return array        Links with the settings
 */
function mumei_ayuda_settings_page_link($links) {

	$link    = mumei_ayuda_get_settings_page_url();
	$links[] = "<a href='$link'>" . __('Settings', 'ayuda-help-desk') . "</a>";

	return $links;
}

add_filter('postbox_classes_ticket_wpas-mb-details', 'mumei_ayuda_add_metabox_details_classes');
/**
 * Add new class to the details metabox.
 *
 * @param array $classes Current metabox classes
 *
 * @return array The updated list of classes
 */
function mumei_ayuda_add_metabox_details_classes($classes) {
	array_push($classes, 'submitdiv');

	return $classes;
}

add_action('admin_notices', 'mumei_ayuda_admin_notices');
/**
 * Display custom admin notices.
 *
 * Custom admin notices are usually triggered by custom actions.
 *
 * @since  3.0.0
 * @return void
 */
function mumei_ayuda_admin_notices() {
	
	if (isset($_GET['wpas-message'])) {

		// translators: %s is the ticket id.
		$x_content1 = __('The ticket #%s has been (re)opened.', 'ayuda-help-desk');

		// translators: %s is the ticket id.
		$x_content2 = __('The ticket #%s has been closed.', 'ayuda-help-desk');

		switch ($_GET['wpas-message']) {

			case 'opened':
?>
				<div class="updated">
					<p><?php printf(esc_html($x_content1), isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0); ?></p>
				</div>
			<?php
				break;

			case 'closed':
			?>
				<div class="updated">
					<p><?php printf(esc_html($x_content2), isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0); ?></p>
				</div>
	<?php
				break;
		}
	}
}

add_filter('mumei_ayuda_toolbar_ticket_reply', 'mumei_ayuda_ticket_reply_controls', 10, 3);
/**
 * Add ticket reply controls
 *
 * @since 3.2.6
 *
 * @param array   $controls  List of existing controls
 * @param int     $ticket_id ID of the ticket current reply belongs to
 * @param WP_Post $reply     Reply post object
 *
 * @return array
 */
function mumei_ayuda_ticket_reply_controls($controls, $ticket_id, $reply) {

	if (0 !== $ticket_id) {

		// Create a delete link and then add the delete reply icon.
		if (((true === boolval(mumei_ayuda_get_option('agent_delete_own_reply', false)) && get_current_user_id() == $reply->post_author))
			|| true === mumei_ayuda_is_asadmin()
			|| true === mumei_ayuda_current_role_in_list(mumei_ayuda_get_option('roles_delete_all_replies', '')) ) {

			// Create delete link
			$_GET['del_id'] = $reply->ID;
			$url            = add_query_arg($_GET, admin_url('post.php'));
			remove_query_arg('message', $url);
			$delete         = mumei_ayuda_do_url(admin_url('post.php'), 'admin_trash_reply', array('post' => $ticket_id, 'action' => 'edit', 'reply_id' => $reply->ID));
			wp_nonce_url(add_query_arg(array(
				'post'   => $ticket_id,
				'rid'    => $reply->ID,
				'action' => 'edit_reply'
			), admin_url('post.php')), 'delete_reply_' . $reply->ID);

			/* Add delete reply icon */
			$controls['delete_reply'] = array(
				'tool_tip_text' => esc_html_x('Delete', 'Link to delete a ticket reply', 'ayuda-help-desk'),
				'type' => 'link',
				'link'	=> esc_url($delete),
				'icon'  => 'icon-delete-ticket-replies',
				'id_param' => 'css',
				'classes' => 'wpas-delete'
			);
		}

		/* Add edit reply icon */
		if (((true === boolval(mumei_ayuda_get_option('agent_edit_own_reply', false)) && get_current_user_id() == $reply->post_author))
			|| true === mumei_ayuda_is_asadmin()
			|| true === mumei_ayuda_current_role_in_list(mumei_ayuda_get_option('roles_edit_all_replies', '')) ) {

			$controls['edit_reply'] = array(
				'tool_tip_text' => esc_html_x('Edit', 'Link to edit a ticket reply', 'ayuda-help-desk'),
				'icon' => 'icon-edit-ticket-replies',
				'id_param' => 'css',
				'classes' => 'wpas-edit',
				'data' => array(
					'origin' => "#wpas-reply-{$reply->ID}",
					'replyid' => $reply->ID,
					'reply' => "wpas-editwrap-{$reply->ID}",
					'wysiwygid' => "wpas-editreply-{$reply->ID}"
				)
			);
		}

		/** Add reply history icon */
		if (get_current_user_id() == $reply->post_author || true === mumei_ayuda_is_asadmin()) {

			$controls['reply_history'] = array(
				'tool_tip_text' => esc_html_x('History', 'View ticket reply history', 'ayuda-help-desk'),
				'icon' => 'icon-due-date',
				'id_param' => 'css',
				'classes' => 'wpas-show-reply-history',
				'data' => array(
					'replyid' => $reply->ID
				)
			);
		}
	}

	if (get_current_user_id() !== $reply->post_author && 'unread' === $reply->post_status) {

		/* Add mark as read icon */
		$controls['mark_read'] = array(
			'tool_tip_text' => esc_html_x('Mark as Read', 'Mark a user reply as read', 'ayuda-help-desk'),
			'icon' => 'icon-prewriten-responses',
			'id_param' => 'css',
			'classes' => 'wpas-mark-read',
			'data' => array(
				'replyid' => $reply->ID
			)
		);
	}

	return $controls;
}

/**
 * Check if the ticket is old.
 *
 * A simple check based on the value of the "Ticket old" option.
 * If the last reply (or the ticket itself if no reply) is older
 * than the post date + the allowed delay, then it is considered old.
 *
 * @since  3.0.0
 *
 * @param  integer       $post_id The ID of the ticket to check
 * @param  WP_Query|null $replies The object containing the ticket replies. If the object was previously generated we
 *                                pass it directly in order to avoid re-querying
 *
 * @return boolean          True if the ticket is old, false otherwise
 */
function mumei_ayuda_is_ticket_old($post_id, $replies = null) {

	if ('closed' === mumei_ayuda_get_ticket_status($post_id)) {
		return false;
	}

	// Prepare the new object
	if (is_null($replies) || is_object($replies) && !is_a($replies, 'WP_Query')) {
		$replies = MUMEI_AYUDA_Tickets_List::get_instance()->get_replies_query($post_id);
	}

	/**
	 * We check when was the last reply (if there was a reply).
	 * Then, we compute the ticket age and if it is considered as
	 * old, we display an informational tag.
	 */
	if (empty($replies->posts)) {

		$post = get_post($post_id);

		/* We get the post date */
		$date_created = $post->post_date;
	} else {

		$last = $replies->post_count - 1;

		/* We get the post date */
		$date_created = $replies->posts[$last]->post_date;
	}

	$old_after           = (int) mumei_ayuda_get_option('old_ticket');
	$post_date_timestamp = mysql2date('U', $date_created);

	if ($post_date_timestamp + ($old_after * 86400) < strtotime('now')) {
		return true;
	}

	return false;
}

/**
 * Check if a reply is needed.
 *
 * Takes a ticket ID and checks if a reply is needed. The check is based
 * on who replied last. If a client was the last to reply, or if the ticket
 * was just transferred from one agent to another, then it is considered
 * as "awaiting reply".
 *
 * @since  3.0.0
 *
 * @param  integer       $post_id The ID of the ticket to check
 * @param  WP_Query|null $replies The object containing the ticket replies. If the object was previously generated we
 *                                pass it directly in order to avoid re-querying
 *
 * @return boolean          True if a reply is needed, false otherwise
 */
function mumei_ayuda_is_reply_needed($post_id, $replies = null) {

	if ('closed' === mumei_ayuda_get_ticket_status($post_id)) {
		return false;
	}

	/* Prepare the new object */
	if (is_null($replies) || is_object($replies) && !is_a($replies, 'WP_Query')) {
		$replies = MUMEI_AYUDA_Tickets_List::get_instance()->get_replies_query($post_id);
	}

	/* No reply yet. */
	if (empty($replies->posts)) {

		$post = get_post($post_id);

		/* Make sure the ticket wan not created by an agent on behalf of the client. */
		if (!user_can($post->post_author, 'edit_ticket')) {
			return true;
		}
	} else {

		$last = $replies->post_count - 1;

		// If the last reply was from an agent then return false - the ticket is waiting for the customer to reply.
		if (user_can($replies->posts[$last]->post_author, 'edit_ticket')) {

			return false;
		} else {

			// Or the ticket has just been transferred, in which case we want to show the awaiting reply tag
			return true;
		}

		// If the last reply is not from an agent return true since ticket is waiting for a reply from an agent...
		if (!user_can($replies->posts[$last]->post_author, 'edit_ticket')) {
			return true;
		}
	}

	return false;
}

/**
 * Check if the ticket is a ticket template.
 *
 *
 * @since  5.9.0
 *
 * @param  integer       $post_id The ID of the ticket to check
 *
 * @return boolean       True if the ticket is a ticket template, false otherwise
 */
function mumei_ayuda_is_ticket_template($post_id) {
	return boolval(get_post_meta($post_id, '_mumei_ayuda_is_ticket_template', true));
}

/**
 * Returns the close date of the ticket based on the ticket/post id passed
 *
 * @since  4.0.4
 *
 * @param  integer       $post_id The ID of the ticket to check
 *
 * @return date|string  Close date of ticket, an empty string otherwise
 */
function mumei_ayuda_get_close_date($post_id) {

	$close_date = get_post_meta($post_id, '_ticket_closed_on', true);

	if (!empty($close_date)) {
		return $close_date;
	} else {
		return '';
	}
}

/**
 * Returns the close date in GMT of the ticket based on the ticket/post id passed
 *
 * @since  4.0.5
 *
 * @param  integer       $post_id The ID of the ticket to check
 *
 * @return date|string  Close date of ticket, an empty string otherwise
 */
function mumei_ayuda_get_close_date_gmt($post_id) {

	$close_date = get_post_meta($post_id, '_ticket_closed_on_gmt', true);

	if (!empty($close_date)) {
		return $close_date;
	} else {
		return '';
	}
}

/**
 * Returns the open date of the ticket based on the ticket/post id passed
 *
 * @since  4.0.5
 *
 * @param  integer       $post_id The ID of the ticket to check
 *
 * @return date|string  open date of ticket, an empty string otherwise
 */
function mumei_ayuda_get_open_date($post_id) {

	// Return if not a ticket...
	if ('ticket' <> get_post_type($post_id)) {
		return '';
	}

	$the_ticket = get_post($post_id);

	$open_date = $the_ticket->post_date;

	if (!empty($open_date)) {
		return $open_date;
	} else {
		return '';
	}
}

/**
 * Returns the open date in GMT of the ticket based on the ticket/post id passed
 *
 * @since  4.0.5
 *
 * @param  integer       $post_id The ID of the ticket to check
 *
 * @return date|string  open date of ticket, an empty string otherwise
 */
function mumei_ayuda_get_open_date_gmt($post_id) {

	// Return if not a ticket...
	if ('ticket' <> get_post_type($post_id)) {
		return '';
	}

	$the_ticket = get_post($post_id);

	$open_date = $the_ticket->post_date_gmt;

	if (!empty($open_date)) {
		return $open_date;
	} else {
		return '';
	}
}

/**
 * Returns difference between two dates in string format to help with debugging.
 * Formatted string will look like this sample : 0 day(s) 14 hour(s) 33 minute(s)
 *
 * @since  4.0.5
 *
 * @param  date $firstdate 	First date in the format you get when using post->post_date to get a date from a post
 * @param  date $seconddate Second date in the format you get when using post->post_date to get a date from a post
 *
 * @return string  difference between two dates, an empty string otherwise
 */
function mumei_ayuda_get_date_diff_string($firstdate, $seconddate) {

	// Calculate difference object...
	$date1 = new DateTime($firstdate);
	$date2 = new DateTime($seconddate);
	$diff_dates = $date2->diff($date1);

	$date_string = '';
	$date_string .= ' ' . $diff_dates->format('%d') .  __(' day(s)', 'ayuda-help-desk');
	$date_string .=  ' ' . $diff_dates->format('%h') .  __(' hour(s)', 'ayuda-help-desk');
	$date_string .=  ' ' . $diff_dates->format('%i') .  __(' minute(s)', 'ayuda-help-desk');

	return $date_string;
}

add_filter('admin_footer_text', 'mumei_ayuda_admin_footer_text', 999, 1);
/**
 * Add a custom admin footer text
 *
 * @since 3.2.8
 *
 * @param string $text Footer text
 *
 * @return string
 */
function mumei_ayuda_admin_footer_text($text) {

	if (!is_admin() || !mumei_ayuda_is_plugin_page()) {
		return $text;
	}

	// Do not show message if installed in an SAAS environment
	if (defined('MUMEI_AYUDA_SAAS') && true === MUMEI_AYUDA_SAAS) {
		return;
	}

	if (!boolval(mumei_ayuda_get_option('remove_admin_ratings_request', false))) {
		// translators: %1$s is the HTML attribute for the link, %2$s is the rating (e.g., star) the user is asked to leave.
		$x_content = __('If you like Ayuda – Help Desk <a %1$s>please leave us a %2$s rating</a>. Many thanks from the Ayuda – Help Desk team in advance :)', 'ayuda-help-desk');
		return sprintf($x_content, 'href="https://wordpress.org/support/view/plugin-reviews/awesome-support?rate=5#postform" target="_blank"', '&#9733&#9733&#9733&#9733&#9733 ');
	}
}

/**
 * Check if the free addon page has been dismissed or not
 *
 * @since 3.3.3
 * @return bool
 */
function mumei_ayuda_is_free_addon_page_dismissed() {
	return (bool) get_option('mumei_ayuda_dismiss_free_addon_page', false);
}

add_action('admin_notices', 'mumei_ayuda_free_addon_notice');
/**
 * Add free addon notice
 *
 * After the plugin has been activated, we display a notice to admins telling them that they can get a free addon for
 * Ayuda – Help Desk.
 *
 * @since 3.3.3
 * @return void
 */
function mumei_ayuda_free_addon_notice() {

	// Do not show message if installed in an SAAS environment
	if (defined('MUMEI_AYUDA_SAAS') && true === MUMEI_AYUDA_SAAS) {
		return;
	}

	// Only show this message to admins
	if (!current_user_can('administrator')) {
		return;
	}

	// Don't show the notice if user already claimed the addon
	if (mumei_ayuda_is_free_addon_page_dismissed()) {
		return;
	}

	// Only show the notice on the plugin pages
	if (!mumei_ayuda_is_plugin_page()) {
		return;
	}

	// No need to show the notice on the free addon page itself
	if (isset($_GET['page']) && 'wpas-optin' === $_GET['page']) {
		return;
	}

	// translators: %1$s is the URL for more information about the free add-on.
	$x_content = __('Hey! Did you know you can get a <strong>free add-on for unlimited sites</strong> (a $61.00 USD value) for Ayuda – Help Desk? <a href="%1$s">Click here to read more</a>.', 'ayuda-help-desk');

	WPAS()->admin_notices->add_notice('updated', 'mumei_ayuda_get_free_addon', wp_kses(sprintf($x_content, add_query_arg(array(
		'post_type' => 'ticket',
		'page'      => 'wpas-optin',
	), admin_url('edit.php'))), array('strong' => array(), 'a' => array('href' => array()))));
}

add_action('admin_notices', 'mumei_ayuda_request_first_5star_rating');
/**
 * Request 5 star rating after 25 closed tickets.
 *
 * After 25 closed tickets we ask the admin for a 5 star rating
 *
 * @since 4.0.0
 * @return void
 */
function mumei_ayuda_request_first_5star_rating() {

	// Do not show message if installed in an SAAS environment
	if (defined('MUMEI_AYUDA_SAAS') && true === MUMEI_AYUDA_SAAS) {
		return;
	}

	// Only show this message to admins
	if (!current_user_can('administrator')) {
		return;
	}

	// Only show the notice on the plugin pages
	if (!mumei_ayuda_is_plugin_page()) {
		return;
	}

	// If notice has been dismissed, return since everything else after this is expensive operations!
	if (mumei_ayuda_is_notice_dismissed('mumei_ayuda_request_first_5star_rating')) {
		return;
	}

	// How many tickets have been closed?
	$closed_tickets = mumei_ayuda_get_tickets('closed', array('posts_per_page' => 1), 'any', true, true);

	// Show notice if number of closed tickets greater than 25.
	if ($closed_tickets >= 25) {

		// translators: %1$s is the URL where users can leave a rating.
		$x_content = __('Wow! It looks like you have closed a lot of tickets which is pretty awesome! We guess you must really like Ayuda – Help Desk, huh? Could you please do us a favor and leave a 5 star rating on WordPress? It will only take a minute and helps to motivate our developers and volunteers. <a href="%1$s">Yes, you deserve it!</a>.', 'ayuda-help-desk');

		WPAS()->admin_notices->add_notice('updated', 'mumei_ayuda_request_first_5star_rating', wp_kses(
			sprintf( $x_content, 'https://wordpress.org/support/plugin/ayuda-help-desk/reviews/'),
			array('strong' => array(), 'a' => array('href' => array()))
		));
	}
}

/**
 * Generate admin tabs html
 *
 * @param string $type
 * @param array $tabs
 *
 * @return string
 */
function mumei_ayuda_admin_tabs($type, $tabs = array()) {

	// Unique tabs widget id
	$id = "mumei_ayuda_admin_tabs_{$type}";


	$tabs = apply_filters($id, $tabs);

	// Stop processing if no tab exist
	if (empty($tabs)) {
		return;
	}


	$tab_order = 1;
	$tab_content_items_ar = array();
	$tab_content_ar = array();

	foreach ($tabs as $tab_id => $tab_name) {
		$_id = "{$id}_{$tab_id}";

		$tab_content = apply_filters("{$_id}_content", "");

		if ($tab_content) {
			$tab_content_items_ar[] = sprintf('<li data-tab-order="%s" rel="%s" class="mumei_ayuda_tab_name">%s</li>', $tab_order, $_id, $tab_name);
			$tab_content_ar[] = '<div class="mumei_ayuda_admin_tab_content" id="' . $_id . '">' . $tab_content . '</div>';
			$tab_order++;
		}
	}


	// Stop processing if no tab's data exist
	if (empty($tab_content_items_ar)) {
		return;
	}

	ob_start();

	?>


	<div class="mumei_ayuda_admin_tabs" id="<?php echo esc_attr($id); ?>">
		<div class="mumei_ayuda_admin_tabs_names_wrapper">
			<ul>
				<?php echo wp_kses(implode('', $tab_content_items_ar), get_allowed_html_wp_notifications()); ?>
				<li class="moreTab">
					<ul class="dropdown-menu tabs_collapsed"></ul>
				</li>
				<li class="clear clearfix"></li>

			</ul>
		</div>
		<?php echo wp_kses(implode('', $tab_content_ar), get_allowed_html_wp_notifications()); ?>
	</div>
<?php


	$output = ob_get_contents();
	ob_end_clean();
	return apply_filters('mumei_ayuda_admin_tabs', $output, $tab_content_items_ar, $tab_content_ar, $id, $tabs);
}


add_action('mumei_ayuda_admin_after_wysiwyg', 'reply_tabs', 8, 0);

/**
 * Add tabs under reply wysiwyg editor
 */
function reply_tabs() {

	$tabs_content = mumei_ayuda_admin_tabs('after_reply_wysiwyg');
	echo wp_kses($tabs_content, get_allowed_html_wp_notifications());
}

/**
 * If a session has already been started by some external system, end one!
 * Added this functionality due to new site health check system added by WordPress.org.
 */
function mumei_ayuda_end_session() {
	if (session_status() === PHP_SESSION_ACTIVE) {
		session_write_close();
	}
}

// End session, if we're not in the CLI.
if (session_status() !== PHP_SESSION_DISABLED && (!defined('WP_CLI') || false === WP_CLI)) {
	// If we're not in a cron, end the session
	if (!defined('DOING_CRON') || false === DOING_CRON) {
		add_action('wp_loaded', 'mumei_ayuda_end_session', 10, 0);
	}
}

if (is_admin()) {
	function wp_default_custom_scripts($scripts)
	{
		$scripts->add('wp-color-picker', "/wp-admin/js/color-picker.js", array('iris'), false, 1);
		did_action('init') && $scripts->localize(
			'wp-color-picker',
			'wpColorPickerL10n',
			array(
				'clear'            => __('Clear', 'ayuda-help-desk' ),
				'clearAriaLabel'   => __('Clear color', 'ayuda-help-desk' ),
				'defaultString'    => __('Default', 'ayuda-help-desk' ),
				'defaultAriaLabel' => __('Select default color', 'ayuda-help-desk' ),
				'pick'             => __('Select Color', 'ayuda-help-desk' ),
				'defaultLabel'     => __('Color value', 'ayuda-help-desk' ),
			)
		);
	}
	add_action('wp_default_scripts', 'wp_default_custom_scripts');
}
