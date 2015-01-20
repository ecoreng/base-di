<?php

namespace Base\Test\Objects;

class TestObjectMethod extends TestObject
{

    protected $to;

    public function setIds(TestObject $to, $id)
    {
        $this->id = $id;
        $this->to = $to;
        return 'success';
    }
}
