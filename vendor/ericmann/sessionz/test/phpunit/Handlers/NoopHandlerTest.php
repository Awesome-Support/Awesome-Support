<?php
namespace EAMann\Sessionz\Handlers;

use PHPUnit\Framework\TestCase;

/**
 * @codeCoverageIgnore
 */
class ConcreteHandler extends NoopHandler {}

/**
 * The BaseHandler doesn't actually do anything, but we need to ensure
 * it passes method calls down to the next item in the stack.
 */
class NoopHandlerTest extends TestCase {

    public function test_delete()
    {
        $handler = new ConcreteHandler();
        $called = false;

        $callback = function($id) use (&$called) {
            $this->assertEquals('doesntmatter', $id);
            $called = true;

            return true;
        };

        $this->assertTrue($handler->delete('doesntmatter', $callback));

        $this->assertTrue($called);
    }

    public function test_clean()
    {
        $handler = new ConcreteHandler();
        $called = false;

        $callback = function($lifetime) use (&$called) {
            $this->assertEquals(50, $lifetime);
            $called = true;

            return true;
        };

        $this->assertTrue($handler->clean(50, $callback));

        $this->assertTrue($called);
    }

    public function test_create()
    {
        $handler = new ConcreteHandler();
        $called = false;

        $callback = function($path, $name) use (&$called) {
            $this->assertEquals('path', $path);
            $this->assertEquals('name', $name);
            $called = true;

            return true;
        };

        $this->assertTrue($handler->create('path', 'name', $callback));

        $this->assertTrue($called);
    }

    public function test_read()
    {
        $handler = new ConcreteHandler();
        $called = false;

        $callback = function($id) use (&$called) {
            $this->assertEquals('doesntmatter', $id);
            $called = true;

            return 'data';
        };

        $this->assertEquals('data', $handler->read('doesntmatter', $callback));

        $this->assertTrue($called);
    }

    public function test_write()
    {
        $handler = new ConcreteHandler();
        $called = false;

        $callback = function($id, $data) use (&$called) {
            $this->assertEquals('someid', $id);
            $this->assertEquals('data', $data);
            $called = true;

            return true;
        };

        $this->assertTrue($handler->write('someid', 'data', $callback));

        $this->assertTrue($called);
    }
}