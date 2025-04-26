<?php
/**
 * Handler Interface
 *
 * Define the contract to which all handlers must subscribe.
 *
 * @package Sessionz
 * @since 1.0.0
 */
namespace EAMann\Sessionz;

interface Handler {
    /**
     * Delete a session from storage by ID.
     *
     * @param string   $id   ID of the session to remove
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return bool
     */
    function delete($id, $next);

    /**
     * Clean up all session older than the max lifetime specified.
     *
     * @param int      $maxlifetime Max number of seconds for a valid session
     * @param callable $next        Callable to invoke the next layer in the stack
     *
     * @return bool
     */
    function clean($maxlifetime, $next);

    /**
     * Create a new session store.
     *
     * @param string   $path Path where the storage lives
     * @param string   $name Name of the session store to create
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return bool
     */
    function create($path, $name, $next);

    /**
     * Read a specific session from storage.
     *
     * @param string   $id   ID of the session to read
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return string
     */
    function read($id, $next);

    /**
     * Write session data to storage.
     *
     * @param string   $id   ID of the session to write
     * @param string   $data Data to be written
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return bool
     */
    function write($id, $data, $next);
}