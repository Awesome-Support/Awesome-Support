<?php
/**
 * Abstract Passthru Session Handler
 *
 * Do nothing ... just pass the request on to the next handler in the stack.
 *
 * @package Sessionz
 * @subpackage Handlers
 * @since 1.0.0
 */
namespace EAMann\Sessionz\Handlers;

use EAMann\Sessionz\Handler;

/**
 * This is not a handler meant to be invoked directly, but instead used as a template
 * for other handlers that may or may not need to explicitly implement various methods.
 * Instead, these methods will fall through to the next item in the stack directly.
 */
abstract class NoopHandler implements Handler {

    /**
     * Delete a session from storage by ID.
     *
     * @param string   $id   ID of the session to remove
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return bool
     */
    public function delete($id, $next)
    {
        return $next($id);
    }

    /**
     * Clean up all session older than the max lifetime specified.
     *
     * @param int      $maxlifetime Max number of seconds for a valid session
     * @param callable $next        Callable to invoke the next layer in the stack
     *
     * @return bool
     */
    public function clean($maxlifetime, $next)
    {
        return $next($maxlifetime);
    }

    /**
     * Create a new session store.
     *
     * @param string   $path Path where the storage lives
     * @param string   $name Name of the session store to create
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return bool
     */
    public function create($path, $name, $next)
    {
        return $next($path, $name);
    }

    /**
     * Read a specific session from storage.
     *
     * @param string   $id   ID of the session to read
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return string
     */
    public function read($id, $next)
    {
        return $next($id);
    }

    /**
     * Write session data to storage.
     *
     * @param string   $id   ID of the session to write
     * @param string   $data Data to be written
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return bool
     */
    public function write($id, $data, $next)
    {
        return $next($id, $data);
    }
}