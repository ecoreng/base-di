<?php

namespace Base\Interfaces;

use \Base\Interfaces\DiPoolStorage as PoolStorage;
use \Interop\Container\ContainerInterface;
use \Base\Interfaces\DiDefinition as Definition;

interface DiResolver
{
    public function __construct(ContainerInterface $container, PoolStorage $storage = null);
    public function getReflectionClass($name);
    public function getReflectionFunction($func, $key);
    public function getReflectionMethod($object, $method);
    public function getConstructorArgs(Definition $entry);
    public function getMethodArgs($alias, $object, $setter);
    public function setContainer(ContainerInterface $container);
    public function getFunctionArgs($func, $key);
    public function getExecutableFromCallable($handlerName, callable $handler, $args);
    public function setterInjectAs($alias, $instance);
    public function instantiate(Definition $entry);
}
