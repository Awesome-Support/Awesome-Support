<?php
/**
 * Native PHP Session Handler
 *
 * Fall back to the native, filesystem-backed session implementation that ships
 * with PHP by instantiating a \SessionHandler object and passing data to/from
 * that object in the stack.
 *
 * @package Sessionz
 * @subpackage Handlers
 * @since 1.0.0
 */

namespace EAMann\Sessionz\Handlers;

use EAMann\Sessionz\Handler;

/**
 * Provides a default implementation of native PHP sessions, falling back
 * on the SessionHandler class provided by PHP itself. By default, session
 * data will be written to disk in a location specified by settings in the
 * `php.ini` file.
 */
class DefaultHandler implements Handler {

    /**
     * @var \SessionHandler
     */
    protected $handler;

    public function __construct()
    {
        $this->handler = new \SessionHandler();
    }

    public function __destruct()
    {
        @$this->handler->close();
    }

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
        $status = $this->handler->destroy($id);
        return $next($id) && $status;
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
        $status = $this->handler->gc($maxlifetime);
        return $next($maxlifetime) && $status;
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
        $status = $this->handler->open($path, $name);
        return $next($path, $name) && $status;
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
        return empty($this->handler->read($id)) ? $next($id) : $this->handler->read($id);
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
        $this->handler->write($id, $data);die;
        return $next($id, $data);
    }
}