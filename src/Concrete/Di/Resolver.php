<?php

namespace Base\Concrete\Di;

use Base\Interfaces\DiPoolStorage as PoolStorage;

class Resolver implements \Base\Interfaces\DiResolver
{

    protected $definitions = [];
    protected $storage = null;

    public function __construct(PoolStorage $storage = null)
    {
        if ($storage !== null) {
            $this->storage = $storage;
        } else {
            $this->storage = new \Base\Concrete\Di\ArrayPoolStorage;
        }
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

    public function getReflectionMethod($instance, $method)
    {
        $ref = $this->getData('reflectionMethod', $method);
        if ($ref !== null) {
            return $ref;
        }
        $key = $method;
        $method = explode(':', $method);
        $this->definitions[$key]['reflectionMethod'] = $rSetter = new \ReflectionMethod($instance, end($method));
        return $rSetter;
    }

    public function getConstructorArgs(\Base\Concrete\Di\Definition $entry)
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

    public function getSetterArgs($alias, $instance, $setter)
    {
        $ret = [];
        $key = $alias . ':' . $setter;
        $args = $this->storage->get($key, 'setterArguments');
        if ($args !== null) {
            return $args;
        }
        $rSetter = $this->getReflectionMethod($instance, $key);
        $ret = $this->getArgsFromReflection($rSetter);
        $this->storage->set($alias, 'setterArguments', $ret);
        return $ret;
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

}
