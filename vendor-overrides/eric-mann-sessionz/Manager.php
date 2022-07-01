<?php
/**
 * Session Manager
 *
 * Configure the global mechanism that will manage all handler stacks
 * and route requests to/from the storage implementations as needed.
 *
 * @package Sessionz
 * @since 1.0.0
 */
namespace EAMann\Sessionz;

use EAMann\Sessionz\Handlers\BaseHandler;

/**
 * Implement PHP's native session handling in such a way as to pass requests
 * for information through a multi-layered stack of potential handlers in
 * a fashion similar to request middleware.
 */
class Manager implements \SessionHandlerInterface {
    protected static $manager;

    /**
     * @var array
     */
    protected $handlers;

    /**
     * @var array
     */
    protected $stacks;

    /**
     * Handler stack lock
     *
     * @var bool
     */
    protected $handlerLock;

    public function __construct()
    {

    }

    /**
     * Add a handler to the stack.
     *
     * @param Handler $handler
     *
     * @return static
     */
    public function addHandler($handler)
    {
        if ($this->handlerLock) {
            throw new \RuntimeException('Session handlers can’t be added once the stack is dequeuing');
        }
        if (is_null($this->handlers)) {
            $this->seedHandlerStack();
        }

        // DELETE
        $next_delete = $this->stacks['delete']->top();
        $this->stacks['delete'][] = function($session_id) use ($handler, $next_delete) {
            return call_user_func(array( $handler, 'delete'), $session_id, $next_delete);
        };

        // CLEAN
        $next_clean = $this->stacks['clean']->top();
        $this->stacks['clean'][] = function($lifetime) use ($handler, $next_clean) {
            return call_user_func(array( $handler, 'clean'), $lifetime, $next_clean);
        };

        // CREATE
        $next_create = $this->stacks['create']->top();
        $this->stacks['create'][] = function($path, $name) use ($handler, $next_create) {
            return call_user_func(array( $handler, 'create'), $path, $name, $next_create);
        };

        // READ
        $next_read = $this->stacks['read']->top();
        $this->stacks['read'][] = function($session_id) use ($handler, $next_read) {
            return call_user_func(array( $handler, 'read'), $session_id, $next_read);
        };

        // WRITE
        $next_write = $this->stacks['write']->top();
        $this->stacks['write'][] = function($session_id, $session_data) use ($handler, $next_write) {
            return call_user_func(array( $handler, 'write'), $session_id, $session_data, $next_write);
        };

        return $this;
    }

    /**
     * Seed handler stack with first callable
     *
     * @throws \RuntimeException if the stack is seeded more than once
     */
    protected function seedHandlerStack()
    {
        if (!is_null($this->handlers)) {
            throw new \RuntimeException('Handler stacks can only be seeded once.');
        }
        $this->stacks = [];
        $base = new BaseHandler();
        $this->handlers = [$base];

        $this->stacks['delete'] = new \SplStack();
        $this->stacks['clean'] = new \SplStack();
        $this->stacks['create'] = new \SplStack();
        $this->stacks['read'] = new \SplStack();
        $this->stacks['write'] = new \SplStack();

        foreach($this->stacks as $id => $stack) {
            $stack->setIteratorMode(\SplDoublyLinkedList::IT_MODE_LIFO | \SplDoublyLinkedList::IT_MODE_KEEP);
            $stack[] = array( $base, $id );
        }
    }

    /**
     * Initialize the session manager.
     *
     * Invoking this function multiple times will reset the manager itself
     * and purge any handlers already registered with the system.
     *
     * @return Manager
     */
    public static function initialize()
    {
        $manager = self::$manager = new self();
        $manager->seedHandlerStack();

        if( ! headers_sent() ){
            session_set_save_handler($manager);
        }

        return $manager;
    }

    /**
     * Close the current session.
     *
     * Will iterate through all handlers registered to the manager and
     * remove them from the stack. This has the effect of removing the
     * objects from scope and triggering their destructors. Any cleanup
     * should happen there.
     *
     * @return true
     */
    public function close()
    {
        $this->handlerLock = true;

        while (count($this->handlers) > 0) {
            array_pop($this->handlers);
            $this->stacks['delete']->pop();
            $this->stacks['clean']->pop();
            $this->stacks['create']->pop();
            $this->stacks['read']->pop();
            $this->stacks['write']->pop();
        }

        $this->handlerLock = false;
        return true;
    }

    /**
     * Destroy a session by either invalidating it or forcibly removing
     * it from session storage.
     *
     * @param string $session_id ID of the session to destroy.
     *
     * @return bool
     */
    public function destroy($session_id)
    {
        if (is_null($this->handlers)) {
            $this->seedHandlerStack();
        }

        /** @var callable $start */
        $start = $this->stacks['delete']->top();
        $this->handlerLock = true;
        $data = $start($session_id);
        $this->handlerLock = false;
        return $data;
    }

    /**
     * Clean up any potentially expired sessions (sessions with an age
     * greater than the specified maximum-allowed lifetime).
     *
     * @param int $maxlifetime Max number of seconds for which a session is valid.
     *
     * @return bool
     */
    public function gc($maxlifetime)
    {
        if (is_null($this->handlers)) {
            $this->seedHandlerStack();
        }

        /** @var callable $start */
        $start = $this->stacks['clean']->top();
        $this->handlerLock = true;
        $data = $start($maxlifetime);
        $this->handlerLock = false;
        return $data;
    }

    /**
     * Create a new session storage.
     *
     * @param string $save_path File location/path where sessions should be written.
     * @param string $name      Unique name of the storage instance.
     *
     * @return bool
     */
    public function open($save_path, $name)
    {
        if (is_null($this->handlers)) {
            $this->seedHandlerStack();
        }

        /** @var callable $start */
        $start = $this->stacks['create']->top();
        $this->handlerLock = true;
        $data = $start($save_path, $name);
        $this->handlerLock = false;
        return $data;
    }

    /**
     * Read data from the specified session.
     *
     * @param string $session_id ID of the session to read.
     *
     * @return string
     */
    public function read($session_id)
    {
        if (is_null($this->handlers)) {
            $this->seedHandlerStack();
        }

        /** @var callable $start */
        $start = $this->stacks['read']->top();
        $this->handlerLock = true;
        $data = $start($session_id);
        $this->handlerLock = false;
        return $data;
    }

    /**
     * Write data to a specific session.
     *
     * @param string $session_id   ID of the session to write.
     * @param string $session_data Serialized string of session data.
     *
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        if (is_null($this->handlers)) {
            $this->seedHandlerStack();
        }

        /** @var callable $start */
        $start = $this->stacks['write']->top();
        $this->handlerLock = true;
        $data = $start($session_id, $session_data);
        $this->handlerLock = false;
        return $data;
    }
}