<?php

namespace Base\Test\Objects;

class TestNonDefinedObjectDependencies
{
    public $foo;
    
    public function __construct(TestObject $foo)
    {
        $this->foo = $foo;
    }
}