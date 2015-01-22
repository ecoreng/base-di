<?php

namespace Base\Concrete;

use Interop\Container\ContainerInterface;
use Base\Exception\DependencyNotFoundException as NotFound;
use Base\Exception\ContainerException;
use Base\Interfaces\DiResolver as ResolverInterface;
use Base\Concrete\Di\Resolver;
use Base\ServiceRegisterer as Services;

class Container implements ContainerInterface
{

    protected $resolver;
    protected $definitions = [];
    protected $instances = [];
    protected $ignore = ['array', 'callable'];
    protected $tempArgs = [];

    public function __construct(ResolverInterface $resolver = null)
    {
        $this->resolver = $resolver === null ? new Resolver : $resolver;
    }

    public function register(Services $registerer)
    {
        $registerer->register($this);
    }

    public function set($alias, $implementation = null, array $arguments = [], $singleton = true)
    {
        $implementation = $implementation !== null ? $implementation : $alias;
        $this->definitions[$alias] = new Di\Definition([
            'alias' => $alias,
            'arguments' => $arguments,
            'implementation' => $implementation,
            'singleton' => $singleton,
            'setters' => []
        ]);

        return $this->definitions[$alias];
    }

    public function raw(\Closure $closure)
    {
        return function () use ($closure) {
            return $closure;
        };
    }

    public function has($name)
    {
        return isset($this->definitions[$name]) ? true : false;
    }

    protected function hasAndReturn($name)
    {
        return $this->has($name) ? $this->definitions[$name] : false;
    }

    public function getDefinition($name)
    {
        if ($this->has($name)) {
            return $this->definitions[$name];
        }
    }

    public function setArgs(array $args)
    {
        $this->tempArgs = $args;
        return $this;
    }
    
    public function get($name)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        $entry = $this->hasAndReturn($name);
        if ($entry) {
            $instance = $this->resolve($entry);

            if ($entry->isSingleton() && !isset($this->instances[$name])) {
                $this->instances[$name] = $instance;

                $implementation = $entry->getImplementation();
                if ($implementation != $name && is_string($implementation)) {
                    $this->instances[$implementation] = $instance;
                }
            }
            return $instance;
        }
        if (class_exists($name)) {
            $this->set($name);
            return $this->get($name);
        }
        throw new NotFound($name . ' is not defined');
    }

    public function getExecutableFromCallable($handlerName, $handler, $args)
    {
        if (is_array($handler)) {
            // resolve the controller parameters
            $instance = $handler[0];
            $method = $handler[1];
            $params = $this->resolver->getMethodArgs($handlerName, $handler[0], $handler[1]);
            $ref = $this->resolver->getReflectionMethod($instance, $method);
            $argsReady = $this->prepareArgs($this->mergeArgs($params, $args));
            return function() use ($ref, $instance, $argsReady) {
                return $ref->invokeArgs($instance, $argsReady);
            };
        } else {
            // resolve the closure / function parameters
            $params = $this->resolver->getFunctionArgs($handler, $handlerName);
            $ref = $this->resolver->getReflectionFunction($handler, $handlerName);
            $argsReady = $this->prepareArgs($this->mergeArgs($params, $args));
            return function() use ($ref, $argsReady) {
                return $ref->invokeArgs($argsReady);
            };
        }
    }

    public function setterInjectAs($alias, $instance)
    {
        $entry = $this->hasAndReturn($alias);
        $implementation = $entry->getImplementation();
        if ($entry) {
            // prepare
            $setters = $entry->getSetters();
            if (count($setters) > 0) {
                foreach ($setters as $setter => $params) {
                    $strArgs = $this->resolver->getMethodArgs($entry->getAlias(), $instance, $setter);
                    $strArgs = $this->prepareArgs(array_merge($strArgs, $params));
                    $name = $entry->getAlias();
                    $key = $name . ':' . $setter;
                    $r = $this->resolver->getReflectionMethod($implementation, $key);
                    $r->invokeArgs($instance, $strArgs);
                }
            }
        }
    }

    protected function tempArgs()
    {
        $args = $this->tempArgs;
        $this->tempArgs = [];
        return $args;
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

    protected function resolve(Di\Definition $entry)
    {
        $implementation = $entry->getImplementation();
        if (is_string($implementation)) {
            if ($entry->getAlias() !== $implementation) {
                $impEntry = $this->hasAndReturn($implementation);
                if ($impEntry) {
                    return $this->resolve($impEntry);
                }
            }

            if (class_exists($implementation)) {
                return $this->instantiateProperly($entry);
            }
        }
        if (is_object($implementation) && !($implementation instanceof \Closure)) {
            return $implementation;
        }

        if (is_callable($implementation)) {
            return $implementation();
        }

        $implementation = $entry->getImplementation();
        if (is_object($implementation)) {
            $implementation = get_class($implementation);
        }
        throw new ContainerException($implementation . ' is unresolvable @ ' . $entry->getAlias());
    }

    protected function instantiateProperly(Di\Definition $entry)
    {
        // get params from resolver
        $ctorArgs = $this->resolver->getConstructorArgs($entry);

        // instantiate
        $implementation = $entry->getImplementation();
        if (count($ctorArgs) === 0) {
            $instance = new $implementation;
        } else {
            $ctorArgs = $this->prepareArgs(array_merge($ctorArgs, $entry->getArguments(), $this->tempArgs()));
            $r = $this->resolver->getReflectionClass($implementation);
            $instance = $r->newInstanceArgs($ctorArgs);
        }

        $this->setterInjectAs($entry->getAlias(), $instance);

        // return
        return $instance;
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

                        // create a chain where we can check if this is cyclic =========================================
                        $ret[$name] = $this->get($argument['argClass']);

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
                    $ret[$name] = $this->get($alias);
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

}
