<?php

namespace Base\Interfaces;

interface DiPoolStorage
{

    public function get($unique, $type);

    public function set($unique, $type, $value);
}
