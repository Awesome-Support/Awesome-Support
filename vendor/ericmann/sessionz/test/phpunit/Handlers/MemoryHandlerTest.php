<?php
namespace EAMann\Sessionz\Handlers;

use PHPUnit\Framework\TestCase;


/**
 * Verify persistence within the MemoryHandler
 */
class MemoryHandlerTest extends TestCase {
    public function test_delete()
    {
        $handler = new MemoryHandler();
        $handler->write('exists', 'data', function($id, $data) { return true; });

        $this->assertEquals('data', $handler->read('exists', function($id) { return ''; }));

        $this->assertTrue($handler->delete('exists', function($id) { return true; }));

        $this->assertEquals('', $handler->read('exists', function($id) { return ''; }));
    }

    public function test_create()
    {
        $handler = new MemoryHandler();

        $this->assertTrue($handler->create('path', 'name', function($path, $name) { return true; }));
    }

    public function test_read_write()
    {
        $handler = new MemoryHandler();
        $handler->write('exists', 'data', function($id, $data) { return true; });

        $this->assertEquals('data', $handler->read('exists', function($id) {return ''; }));
        $this->assertEquals('', $handler->read('doesntexist', function($id) {return ''; }));
    }
}