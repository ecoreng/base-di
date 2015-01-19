<?php

namespace Base\Test\Objects;

class TestObjectDependenciesSpecial
{
    protected $foo;
    protected $bar;
    protected $test;
    
    public function __construct(TestObject $foo, array $bar, callable $test = null)
    {
        $this->foo = $foo;
        $this->bar = $bar;
        $this->test = $test;
    }
}