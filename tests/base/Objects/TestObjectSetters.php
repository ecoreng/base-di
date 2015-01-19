<?php

namespace Base\Test\Objects;

class TestObjectSetters extends TestObject
{

    protected $id2 = 8;

    public function getId2()
    {
        return $this->id2;
    }

    public function setId2($id2)
    {
        $this->id2 = $id2;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setIds($id2, $id)
    {
        $this->id = $id;
        $this->id2 = $id2;
    }
}
