<?php
namespace EAMann\Sessionz\Handlers;

use EAMann\Sessionz\Handler;
use PHPUnit\Framework\TestCase;

/**
 * The BaseHandler doesn't actually do anything, but we need to ensure
 * it returns the base values required for "success" for each method so
 * that middleware dependent on fallthrough values behave properly.
 */
class BaseHandlerTest extends TestCase {
    public function test_delete()
    {
        $handler = new BaseHandler();

        $this->assertTrue($handler->delete('doesntmatter'));
    }

    public function test_clean()
    {
        $handler = new BaseHandler();

        $this->assertTrue($handler->clean(0));
    }

    public function test_create()
    {
        $handler = new BaseHandler();

        $this->assertTrue($handler->create('path', 'name'));
    }

    public function test_read()
    {
        $handler = new BaseHandler();

        $this->assertEquals('', $handler->read('doesntmatter'));
    }

    public function test_write()
    {
        $handler = new BaseHandler();

        $this->assertTrue($handler->write('someid', 'data'));
        $this->assertEquals('', $handler->read('someid'));
    }
}