<?php
if( ! function_exists( 'boolval' ) ) {
    /**
     * Get the boolean value of a variable
     *
     * @param mixed The scalar value being converted to a boolean.
     * @return boolean The boolean value of var.
     */
    function boolval( $var ) {
        return !! $var;
    }
}

if ( ! function_exists( 'wp_roles' ) ) {
    /**
     * Retrieves the global WP_Roles instance and instantiates it if necessary.
     *
     * @since 4.3.0
     *
     * @global WP_Roles $wp_roles WP_Roles global instance.
     *
     * @return WP_Roles WP_Roles global instance if not already instantiated.
     */
    function wp_roles() {
        global $wp_roles;
        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }

        return $wp_roles;
    }
}