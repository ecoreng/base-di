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
    protected $lookupContainer;
    protected $chain = [];

    public function __construct(ResolverInterface $resolver = null)
    {
        $this->resolver = $resolver !== null ?: new Resolver($this);
        $this->lookupContainer = $this;
    }

    public function setDelegateLookupContainer(ContainerInterface $container)
    {
        $this->lookupContainer = $container;
        $this->resolver->setContainer($container);
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
        // remove previous cached instances of this object
        if (isset($this->instances[$alias])) {
            unset($this->instances[$alias]);
        }
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
        if ($this->lookupContainer === $this) {
            return isset($this->definitions[$name]);
        } else {
            return $this->lookupContainer->has($name);
        }
    }

    public function hasAndReturn($name)
    {
        return $this->has($name) ? $this->getDefinition($name) : false;
    }

    public function getDefinition($name)
    {
        if ($this->has($name)) {
            return $this->definitions[$name];
        }
    }

    public function setArgs(array $args)
    {
        $this->resolver->setArgs($args);
        return $this;
    }

    public function get($name)
    {
        if ($this->lookupContainer !== $this) {
            return $this->lookupContainer->get($name);
        }
        
        if ($this->checkChain($name)) {
            throw new \Exception('Recursive dependencies');
        }
        $this->pushChain($name);
        if (isset($this->instances[$name])) {
            $this->popChain();
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
            $this->popChain();
            return $instance;
        }
        if (class_exists($name)) {
            $this->set($name);
            $this->popChain();
            return $this->get($name);
        }
        $this->popChain();
        throw new NotFound($name . ' is not defined');
    }

    public function getExecutableFromCallable($handlerName, callable $handler, $args)
    {
        return $this->resolver->getExecutableFromCallable($handlerName, $handler, $args);
    }

    public function setterInjectAs($alias, $instance)
    {
        $this->resolver->setterInjectAs($alias, $instance);
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
                return $this->resolver->instantiate($entry);
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
    
    protected function popChain()
    {
        array_pop($this->chain);
    }
    
    protected function pushChain($element)
    {
        $this->chain[] = $element;
    }
    
    protected function checkChain($element)
    {
        return in_array($element, $this->chain);
    }
}
