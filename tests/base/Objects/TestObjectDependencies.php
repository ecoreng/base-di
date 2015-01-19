<?php

namespace Base\Test\Objects;

class TestObjectDependencies
{
    public $foo;
    
    public function __construct(TestObject $foo)
    {
        $this->foo = $foo;
    }
}