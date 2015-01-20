<?php

namespace Base\Interfaces;

use \Base\Interfaces\DiPoolStorage as PoolStorage;

interface DiResolver
{
    public function __construct(PoolStorage $storage);
    public function getReflectionClass($name);
    public function getReflectionMethod($instance, $method);
    public function getConstructorArgs(\Base\Concrete\Di\Definition $entry);
    public function getMethodArgs($alias, $instance, $setter);
    //public function getFunctionArgs();
}
