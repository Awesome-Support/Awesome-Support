<?php
/**
 * In-memory Session Handler
 *
 * Rather than storing session data in an external storage system, keep
 * track of things in an in-memory array within the application itself.
 *
 * @package Sessionz
 * @subpackage Handlers
 * @since 1.0.0
 */
namespace EAMann\Sessionz\Handlers;

use EAMann\Sessionz\Handler;
use EAMann\Sessionz\Objects\MemoryItem;

/**
 * Use an associative array to store session data so we can cut down on
 * round trips to an external storage mechanism (or just leverage an in-
 * memory cache for read performance).
 */
class MemoryHandler implements Handler {

    /**
     * @var array
     */
    protected $cache;

    /**
     * Get an item from the cache if it's still valid, otherwise exire it
     * and return false.
     *
     * @param string $id
     *
     * @return bool|string
     */
    protected function _read($id)
    {
        if (isset($this->cache[$id])) {
            /** @var MemoryItem $item */
            $item = $this->cache[$id];
            if (!$item->is_valid()) {
                unset($this->cache[$id]);
                return false;
            }

            return $item->data;
        }

        return false;
    }

    public function __construct()
    {
        $this->cache = [];
    }

    /**
     * Purge an item from the cache immediately.
     *
     * @param string   $id
     * @param callable $next
     *
     * @return mixed
     */
    public function delete($id, $next)
    {
        unset($this->cache[$id]);
        return $next($id);
    }

    /**
     * Update the internal cache by filtering out any items that are no longer valid.
     *
     * @param int      $maxlifetime
     * @param callable $next
     *
     * @codeCoverageIgnore Due to timestamp issues, this is currently untestable ...
     *
     * @return mixed
     */
    public function clean($maxlifetime, $next)
    {
        $this->cache = array_filter($this->cache, function($item) use ($maxlifetime) {
            /** @var MemoryItem $item */
            return $item->is_valid($maxlifetime);
        });

        return $next($maxlifetime);
    }

    /**
     * Pass things through to the next middleare. This function is a no-op.
     *
     * @param string   $path
     * @param string   $name
     * @param callable $next
     *
     * @return mixed
     */
    public function create($path, $name, $next)
    {
        return $next($path, $name);
    }

    /**
     * Grab the item from the cache if it exists, otherwise delve deeper
     * into the stack and retrieve from another underlying middlware.
     *
     * @param string $id
     * @param callable $next
     *
     * @return string
     */
    public function read($id, $next)
    {
        $data = $this->_read($id);
        if ( false === $data ) {
            $data = $next($id);
            if (false !== $data) {
                $item = new MemoryItem($data);
                $this->cache[$id] = $item;
            }
        }

        return $data;
    }

    /**
     * Store the item in the cache and then pass the data, unchanged, down
     * the middleware stack.
     *
     * @param string   $id
     * @param string   $data
     * @param callable $next
     *
     * @return mixed
     */
    public function write($id, $data, $next)
    {
        $item = new MemoryItem($data);
        $this->cache[$id] = $item;

        return $next($id, $data);
    }
}