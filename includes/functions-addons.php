<?php
add_action( 'plugins_loaded', 'wpas_load_addons', 20, 0 );
/**
 * Load all registered addon.
 *
 * @since  3.1.5
 * @return void
 */
function wpas_load_addons() {

	/**
	 * Stored the ordered addons.
	 * 
	 * @var array
	 */
	$ordered = array();

	/**
	 * Iterate through all addons to get their priority.
	 */
	foreach ( WPAS()->addons as $id => $addon ) {
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
		if ( is_array( WPAS()->addons[$id]['callback'] ) ) {

			if ( isset( WPAS()->addons[$id]['callback'][0] ) && isset( WPAS()->addons[$id]['callback'][1] ) ) {

				if ( is_object( WPAS()->addons[$id]['callback'][0] ) && method_exists( WPAS()->addons[$id]['callback'][0], WPAS()->addons[$id]['callback'][1] ) ) {
					$wpas_addon_{$id} = call_user_func( array( WPAS()->addons[$id]['callback'][0], WPAS()->addons[$id]['callback'][1] ) );
				}
				
				elseif ( class_exists( WPAS()->addons[$id]['callback'][0] ) ) {
					$wpas_addon_{$id} = call_user_func( WPAS()->addons[$id]['callback'][0], WPAS()->addons[$id]['callback'][1] );
				}

			}

		} else {
			if ( function_exists( WPAS()->addons[$id]['callback'] ) ) {
				$wpas_addon_{$id} = call_user_func( WPAS()->addons[$id]['callback'] );
			}
		}

		WPAS()->addons[$id]['status'] = false === $wpas_addon_{$id} ? 'error' : 'loaded';

	}

}

function wpas_register_addon( $id, $callback, $priority = 10 ) {

	if ( array_key_exists( $id, WPAS()->addons ) ) {
		wpas_debug_display( sprintf( __( 'An addon with the ID %s is already registered', 'awesome-support' ), $id ) );
		return false;
	}

	WPAS()->addons[$id] = array( 'status' => 'registered', 'priority' => $priority, 'callback' => $callback );

}

function wpas_deregister_addon( $addon_id ) {

	if ( array_key_exists( $addon_id, WPAS()->addons ) ) {
		unset( WPAS()->addons[$addon_id] );
	}

}

function wpas_get_registered_addons() {
	return WPAS()->addons;
}