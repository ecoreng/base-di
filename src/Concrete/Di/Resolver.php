<?php

namespace Base\Concrete\Di;

use Base\Interfaces\DiPoolStorage as PoolStorage;
use Interop\Container\ContainerInterface;
use Base\Concrete\Di\ArrayPoolStorage;
use Base\Interfaces\DiDefinition as Definition;

class Resolver implements \Base\Interfaces\DiResolver
{

    protected $definitions = [];
    protected $storage = null;
    protected $container = null;
    protected $tempArgs = [];

    public function __construct(ContainerInterface $container = null, PoolStorage $storage = null)
    {
        $this->storage = $storage ?: new ArrayPoolStorage;
        $this->container = $container;
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function getReflectionClass($name)
    {
        $ref = $this->getData('reflectionClass', $name);
        if ($ref !== null) {
            return $ref;
        }
        $this->definitions[$name]['reflectionClass'] = $rClass = new \ReflectionClass($name);
        return $rClass;
    }

    public function getReflectionFunction($func, $key)
    {
        $ref = $this->getData('reflectionFunction', $key);
        if ($ref !== null) {
            return $ref;
        }
        $this->definitions[$key]['reflectionMethod'] = $rFunc = new \ReflectionFunction($func);
        return $rFunc;
    }

    public function getReflectionMethod($object, $method)
    {
        $ref = $this->getData('reflectionMethod', $method);
        if ($ref !== null) {
            return $ref;
        }
        $key = $method;
        $method = explode(':', $method);
        $this->definitions[$key]['reflectionMethod'] = $rSetter = new \ReflectionMethod($object, end($method));
        return $rSetter;
    }

    public function getConstructorArgs(Definition $entry)
    {
        $ret = [];
        $name = $entry->getAlias();
        $args = $this->storage->get($name, 'ctorArguments');
        if ($args !== null) {
            return $args;
        }

        $rClass = $this->getReflectionClass($entry->getImplementation());
        $rCtor = $rClass->getConstructor();
        $ret = $this->getArgsFromReflection($rCtor);
        $this->storage->set($name, 'ctorArguments', $ret);
        return $ret;
    }

    public function getMethodArgs($alias, $object, $method)
    {
        $ret = [];
        $key = $alias . ':' . $method;
        $args = $this->storage->get($key, 'methodArguments');
        if ($args !== null) {
            return $args;
        }
        $rSetter = $this->getReflectionMethod($object, $key);
        $ret = $this->getArgsFromReflection($rSetter);
        $this->storage->set($alias, 'methodArguments', $ret);
        return $ret;
    }

    public function getFunctionArgs($func, $key)
    {
        $ret = [];
        $args = $this->storage->get($key, 'functionArguments');
        if ($args !== null) {
            return $args;
        }
        $rFunc = $this->getReflectionFunction($func, $key);
        $ret = $this->getArgsFromReflection($rFunc);
        $this->storage->set($key, 'functionArguments', $ret);
        return $ret;
    }
    
    public function getExecutableFromCallable($handlerName, callable $handler, $args)
    {
        if (is_array($handler)) {
            // resolve the object parameters
            $instance = $handler[0];
            $method = $handler[1];
            $params = $this->getMethodArgs($handlerName, $handler[0], $handler[1]);
            $ref = $this->getReflectionMethod($instance, $method);
            $argsReady = $this->prepareArgs($this->mergeArgs($params, $args));
            return function() use ($ref, $instance, $argsReady) {
                return $ref->invokeArgs($instance, $argsReady);
            };
        } else {
            // resolve the closure / function parameters
            $params = $this->getFunctionArgs($handler, $handlerName);
            $ref = $this->getReflectionFunction($handler, $handlerName);
            $argsReady = $this->prepareArgs($this->mergeArgs($params, $args));
            return function() use ($ref, $argsReady) {
                return $ref->invokeArgs($argsReady);
            };
        }
    }
    
    public function setterInjectAs($alias, $instance)
    {
        if ($entry = $this->container->hasAndReturn($alias)) {
            // prepare
            $setters = $entry->getSetters();
            if (count($setters) > 0) {
                foreach ($setters as $setter => $calls) {
                    foreach ($calls as $params) {
                        $strArgs = $this->getMethodArgs($entry->getAlias(), $instance, $setter);
                        $strArgs = $this->prepareArgs(array_merge($strArgs, $params));
                        $name = $entry->getAlias();
                        $key = $name . ':' . $setter;
                        $r = $this->getReflectionMethod($entry->getImplementation(), $key);
                        $r->invokeArgs($instance, $strArgs);
                    }
                }
            }
        }
    }

    public function setArgs(array $args)
    {
        $this->tempArgs = $args;
        return $this;
    }

    protected function tempArgs()
    {
        $args = $this->tempArgs;
        $this->tempArgs = [];
        return $args;
    }

    protected function prepareArgs(array $arguments)
    {
        $ret = [];
        foreach ($arguments as $name => $argument) {
            // argument was not defined but looks like it's typehinted
            if (is_array($argument) && (array_key_exists('argDefault', $argument) || array_key_exists('argClass', $argument))) {
                if (isset($argument['argClass'])) {
                    // is typehinted
                    if ($argument['argClass'] !== null) {
                        $ret[$name] = $this->container->get($argument['argClass']);
                        if (isset($argument['argDefault']) && $ret[$name] === null) {
                            $ret[$name] = $argument['argDefault'];
                        }
                    }
                } else {
                    $ret[$name] = $argument['argDefault'];
                }
                // argument is a string
            } elseif (is_string($argument)) {

                // argument is a reference to another object
                if (substr($argument, 0, 1) === '@') {
                    $alias = substr($argument, 1);
                    $ret[$name] = $this->container->get($alias);
                    // argument is actually a regular string
                } else {
                    $ret[$name] = $argument;
                }

                // argument is something else, better leave it alone
            } else {
                $ret[$name] = $argument;
            }
        }
        return $ret;
    }

    public function instantiate(Definition $entry)
    {
        // get params
        $ctorArgs = $this->getConstructorArgs($entry);

        // instantiate
        $implementation = $entry->getImplementation();
        if (count($ctorArgs) === 0) {
            $instance = new $implementation;
        } else {
            $ctorArgs = $this->prepareArgs(array_merge($ctorArgs, $entry->getArguments(), $this->tempArgs()));
            $r = $this->getReflectionClass($implementation);
            $instance = $r->newInstanceArgs($ctorArgs);
        }

        $this->setterInjectAs($entry->getAlias(), $instance);

        // return
        return $instance;
    }
    protected function getArgsFromReflection($method)
    {
        $ret = [];
        if ($method) {
            $rParams = $method->getParameters();
            foreach ($rParams as $param) {
                $aDef = $param->isDefaultValueAvailable() ? ['argDefault' => $param->getDefaultValue()] : [];
                $aClass = $param->getClass() !== null ? ['argClass' => $param->getClass()->getName()] : [];
                $ret[$param->getName()] = array_merge($aDef, $aClass);
            }
        }
        return $ret;
    }

    protected function getData($key, $name)
    {
        if (isset($this->definitions[$name])) {
            if (isset($this->definitions[$name][$key])) {
                return $this->definitions[$name][$key];
            }
        }
    }

    protected function mergeArgs($resolved, $passed)
    {
        $numeric = false;
        $merged = [];
        if (is_numeric(key($passed))) {
            foreach ($passed as $key => $arg) {
                $argRes = each($resolved);
                if ($arg === null) {
                    $merged[$argRes['key']] = $argRes['value'];
                    continue;
                }
                $merged[$argRes['key']] = $arg;
                unset($passed[$key]);
            }
        }
        $merged = array_merge($resolved, $merged, $passed);
        return $merged;
    }
}
