<?php

namespace Base\Interfaces;

interface DiDefinition
{
    public function getAlias();
    public function getImplementation();
    public function isSingleton();
    public function getArguments();
    public function getArgument($arg);
    public function getSetter($setter);
    public function getSetters();
    public function setSingleton($trueOrFalse = true);
    public function withArgument($name, $arg);
    public function withArguments(array $arguments);
    public function withSetter($setter, array $arguments);
}
