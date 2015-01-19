<?php

namespace Base\Test;

class ArrayPoolStorageTest extends \PHPUnit_Framework_TestCase
{

    protected $storage;

    public function setUp()
    {
        $this->storage = new \Base\Concrete\Di\ArrayPoolStorage([
            'foo' => [
                'ctor' => [
                    'one' => 'bar'
                    ]
                ],
                'setter' => [
                    'two' => 'moo'
                ]
            ]
        );
    }

    public function testGet()
    {
        $i = $this->storage->get('foo', 'ctor');
        $this->assertEquals(true, is_array($i));
        $this->assertEquals(true, array_key_exists('one', $i));
        $this->assertEquals('bar', $i['one']);
    }
    
    public function testSet()
    {
        $this->storage->set('foo2', 'ctor', ['three' => 'woo']);
        $i = $this->storage->get('foo2', 'ctor');
        $this->assertEquals(true, is_array($i));
        $this->assertEquals(true, array_key_exists('three', $i));
        $this->assertEquals('woo', $i['three']);
    }

}
