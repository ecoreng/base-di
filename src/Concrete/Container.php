<?php

namespace Base\Concrete;

use Interop\Container\ContainerInterface;
use Base\Exception\DependencyNotFoundException as NotFound;
use Base\Exception\ContainerException;

class Container implements ContainerInterface
{

    protected $resolver;
    protected $definitions = [];
    protected $instances = [];

    public function __construct(\Base\Interfaces\DiResolver $resolver = null)
    {
        $this->resolver = $resolver === null ? new \Base\Concrete\Di\Resolver : $resolver;
    }
    
    public function register(\Base\ServiceRegisterer $registerer)
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

    protected function hasReturn($name)
    {
        return $this->has($name) ? $this->definitions[$name] : false;
    }

    public function getDefinition($name)
    {
        if ($this->has($name)) {
            return $this->definitions[$name];
        }
    }

    public function get($name)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        $entry = $this->hasReturn($name);
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

    protected function resolve(Di\Definition $entry)
    {

        $implementation = $entry->getImplementation();
        if (is_string($implementation)) {
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
        throw new ContainerException($implementation . ' is unresolvable');
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
            $ctorArgs = $this->prepareArgs(array_merge($ctorArgs, $entry->getArguments()));
            $r = $this->resolver->getReflectionClass($implementation);
            $instance = $r->newInstanceArgs($ctorArgs);
        }

        // prepare
        $setters = $entry->getSetters();
        if (count($setters) > 0) {
            foreach ($setters as $setter => $params) {
                $strArgs = $this->resolver->getSetterArgs($entry->getAlias(), $instance, $setter);
                $strArgs = $this->prepareArgs(array_merge($strArgs, $params));
                $name = $entry->getAlias();
                $key = $name . ':' . $setter;
                $r = $this->resolver->getReflectionMethod($implementation, $key);
                $r->invokeArgs($instance, $strArgs);
            }
        }

        // return
        return $instance;
    }

    protected function prepareArgs(array $arguments)
    {
        $ret = [];
        foreach ($arguments as $name => $argument) {

            // argument was not defined but looks like it's typehinted
            if (is_array($argument)) {
                if (isset($argument['argClass'])) {

                    // is typehinted
                    if ($argument['argClass'] !== null) {

                        // create a chain where we can check if this is cyclic =========================================

                        $ret[$name] = $this->get($argument['argClass']);

                        // check if there is a default value and use it
                    } else {
                        if (isset($argument['argDefault'])) {
                            $ret[$name] = $argument['argDefault'];
                        }
                    }
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
