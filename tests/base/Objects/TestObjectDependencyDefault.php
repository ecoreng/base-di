<?php

namespace Base\Test\Objects;

class TestObjectDependencyDefault
{
    public $foo2;
    
    public function __construct($foo2 = null)
    {
        $this->foo2 = $foo2;
    }
}