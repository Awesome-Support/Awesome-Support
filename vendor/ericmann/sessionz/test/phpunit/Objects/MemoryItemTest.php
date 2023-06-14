<?php
namespace EAMann\Sessionz\Objects;

use PHPUnit\Framework\TestCase;

class MemoryItemTest extends TestCase {
    public function test_field_read()
    {
        $item = new MemoryItem("data", 12345);

        $this->assertEquals("data", $item->data);
        $this->assertEquals(12345, $item->time);
    }

    public function test_data_readonly()
    {
        $this->expectException(\InvalidArgumentException::class);

        $item = new MemoryItem("data", 12345);

        // Writing data should throw the exception expected above
        $item->data = "modified";
    }

    public function test_time_readonly()
    {
        $this->expectException(\InvalidArgumentException::class);

        $item = new MemoryItem("data", 12345);

        // Writing data should throw the exception expected above
        $item->time = 23456;
    }

    public function test_validity() {
        $item = new MemoryItem("data", 12345);

        // Issued now
        $this->assertTrue($item->is_valid(100, 12345));

        // Issued within expiration
        $this->assertTrue($item->is_valid(100, 12350));

        // Issued too long ago
        $this->assertFalse($item->is_valid(100, 15000));
    }
}