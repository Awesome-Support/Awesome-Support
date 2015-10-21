<?php
add_action( 'plugins_loaded', 'wpas_load_addons', 20, 0 );
/**
 * Load all registered addon.
 *
 * @since  3.1.5
 * @return void
 */
function wpas_load_addons() {

	global $wpas_addons;

	/**
	 * Stored the ordered addons.
	 * 
	 * @var array
	 */
	$ordered = array();

	/**
	 * Iterate through all addons to get their priority.
	 */
	foreach ( $wpas_addons as $id => $addon ) {
		$ordered[$id] = $addon['priority'];
	}

	/**
	 * Reorder the addons by priority ASC
	 */
	asort( $ordered );

	/**
	 * Iterate through the ordered addons and load them.
	 */
	foreach ( $ordered as $id => $priority ) {

		/**
		 * Define the addon's instance.
		 */
		$wpas_addon_{$id} = false;

		/* We assume it's a class */
		if ( is_array( $wpas_addons[$id]['callback'] ) ) {

			if ( isset( $wpas_addons[$id]['callback'][0] ) && isset( $wpas_addons[$id]['callback'][1] ) ) {

				if ( is_object( $wpas_addons[$id]['callback'][0] ) && method_exists( $wpas_addons[$id]['callback'][0], $wpas_addons[$id]['callback'][1] ) ) {
					$wpas_addon_{$id} = call_user_func( array( $wpas_addons[$id]['callback'][0], $wpas_addons[$id]['callback'][1] ) );
				}
				
				elseif ( class_exists( $wpas_addons[$id]['callback'][0] ) ) {
					$wpas_addon_{$id} = call_user_func( $wpas_addons[$id]['callback'][0], $wpas_addons[$id]['callback'][1] );
				}

			}

		} else {
			if ( function_exists( $wpas_addons[$id]['callback'] ) ) {
				$wpas_addon_{$id} = call_user_func( $wpas_addons[$id]['callback'] );
			}
		}

		$wpas_addons[$id]['status'] = false === $wpas_addon_{$id} ? 'error' : 'loaded';

	}

}

function wpas_register_addon( $id, $callback, $priority = 10 ) {

	global $wpas_addons;

	if ( array_key_exists( $id, $wpas_addons ) ) {
		wpas_debug_display( sprintf( __( 'An addon with the ID %s is already registered', 'awesome-support' ), $id ) );
		return false;
	}

	$wpas_addons[$id] = array( 'status' => 'registered', 'priority' => $priority, 'callback' => $callback );

}

function wpas_deregister_addon( $addon_id ) {

	global $wpas_addons;

	if ( array_key_exists( $addon_id, $wpas_addons ) ) {
		unset( $wpas_addons[$addon_id] );
	}

}

function wpas_get_registered_addons() {
	global $wpas_addons;
	return $wpas_addons;
}