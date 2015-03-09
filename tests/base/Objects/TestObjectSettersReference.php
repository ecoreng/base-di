<?php

namespace Base\Test\Objects;

class TestObjectSettersReference extends TestObject
{

    protected $St1;

    public function getSt1()
    {
        return $this->St1;
    }

    public function setSt1(\Base\Test\Objects\TestObject $St1)
    {
        $this->St1 = $St1;
        return $this;
    }
}
