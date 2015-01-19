<?php

namespace Base\Concrete\Di;

class Definition implements \Base\Interfaces\DiDefinition
{

    protected $definition;
    
    public function __construct(array $definition)
    {
        $this->definition = $definition;
    }

    public function getAlias()
    {
        return $this->definition['alias'];
    }

    public function setSingleton($trueOrFalse = true)
    {
        $this->definition['singleton'] = (bool) $trueOrFalse;
        return $this;
    }

    public function getImplementation()
    {
        return $this->definition['implementation'];
    }

    public function isSingleton()
    {
        return (bool) $this->definition['singleton'] === true;
    }

    public function getArguments()
    {
        return $this->definition['arguments'];
    }

    public function getArgument($arg)
    {
        if (isset($this->definition['arguments'][$arg])) {
            return $this->definition['arguments'][$arg];
        }
    }

    public function getSetter($setter)
    {
        if (isset($this->definition['setters'][$setter])) {
            return $this->definition['setters'][$setter];
        }
    }

    public function getSetters()
    {
        return $this->definition['setters'];
    }

    public function withArgument($name, $arg)
    {
        $this->definition['arguments'][$name] = $arg;
        return $this;
    }

    public function withArguments(array $arguments)
    {
        foreach ($arguments as $name => $argument) {
            $this->withArgument($name, $argument);
        }
        return $this;
    }

    public function withSetter($setter, array $arguments)
    {
        foreach ($arguments as $name => $argument) {
            $this->addSetterArgument($setter, $name, $argument);
        }
        return $this;
    }

    protected function addSetterArgument($setter, $name, $argument)
    {
        if (!isset($this->definition['setters'][$setter])) {
            $this->definition['setters'][$setter] = [];
        }
        $this->definition['setters'][$setter][$name] = $argument;
        return $this;
    }

}
