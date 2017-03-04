<?php

/**
 * List of default channels
 * @return array
 */
function wpas_default_channels() {
	$channels = array(
		'Standard Ticket Form',
		'Email',
		'Chat',
		'WordPress.org',
		'Contact Form (Website)',
		'Gravity Forms add-on',
		'Twitter',
		'Twitter DM (direct message)',
		'Twitter Favorite',
		'Voicemail',
		'Phone call (incoming)',
		'Feedback Form',
		'Web service (API)',
		'Trigger or automation',
		'Forum topic',
		'Facebook Post',
		'Facebook Message',
		'Other'
	);
	
	return apply_filters( 'wpas_default_channels', $channels );
}

/**
 * add channel terms
 * @param boolean $reset
 * @return boolean
 */
function wpas_add_default_channel_terms($reset = false) {
	
	if (!$reset) {
		
		$added_before = boolval( get_option( 'wpas_default_channels_added', false ) );
		
		if ( true ===  $added_before) {
			return;
		}
	}
	
	$channels = wpas_default_channels();
	foreach($channels as $channel) {
		wp_insert_term($channel, 'ticket_channel');
	}
	update_option('wpas_default_channels_added', true);
	
	return true;
	
}


add_action( 'tf_admin_options_saved_wpas', 'wpas_add_default_channel_terms' );