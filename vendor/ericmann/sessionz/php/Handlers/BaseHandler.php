<?php
/**
 * Base handler for reliable session returns
 *
 * Define a basic handler implementation that returns standard data types,
 * assuming success of the underlying mechanism. These returns are the defaults
 * that should be present if no other handlers are invoked. This handler should
 * always be added to the management stack.
 *
 * @package Sessionz
 * @subpackage Handlers
 * @since 1.0.0
 */

namespace EAMann\Sessionz\Handlers;

use EAMann\Sessionz\Handler;

/**
 * This handler is the root used in all handler stacks. The purpose is to
 * always return the base, default type/data for every call such that PHP's
 * internal session expectations are actually met. This will make sessions
 * appear to work even if no other handlers are applied.
 *
 * Note: If no other handlers are applied, data will not be persistent
 * between (or even within) requests as no session data is actually being
 * stored.
 */
class BaseHandler implements Handler {

    /**
     * Delete a session from storage by ID.
     *
     * @param string   $id   ID of the session to remove
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return bool
     */
    public function delete($id, $next = null)
    {
        return true;
    }

    /**
     * Clean up all session older than the max lifetime specified.
     *
     * @param int      $maxlifetime Max number of seconds for a valid session
     * @param callable $next        Callable to invoke the next layer in the stack
     *
     * @return bool
     */
    public function clean($maxlifetime, $next = null)
    {
        return true;
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
    public function create($path, $name, $next = null)
    {
        return true;
    }

    /**
     * Read a specific session from storage.
     *
     * @param string   $id   ID of the session to read
     * @param callable $next Callable to invoke the next layer in the stack
     *
     * @return string
     */
    public function read($id, $next = null)
    {
        return '';
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
    public function write($id, $data, $next = null)
    {
        return true;
    }
}