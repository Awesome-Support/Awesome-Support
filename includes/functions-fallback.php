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