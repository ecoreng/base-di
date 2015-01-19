<?php

namespace Base\Concrete\Di;

use Base\Interfaces\DiPoolStorage as Storage;

class ArrayPoolStorage implements Storage
{
    protected $storage = [];
    
    public function __construct(array $storage = [])
    {
        $this->storage = $storage;
    }
    
    public function get($unique, $type)
    {
        if (isset($this->storage[$unique])) {
            if (isset($this->storage[$unique][$type])) {
                return $this->storage[$unique][$type];
            }
        }
    }
    
    public function set($unique, $type, $value)
    {
        if (!isset($this->storage[$unique])) {
            $this->storage[$unique] = [];
        }
        if (!isset($this->storage[$unique][$type])) {
            $this->storage[$unique][$type] = [];
        }
        $this->storage[$unique][$type] = $value;
    }
    
}