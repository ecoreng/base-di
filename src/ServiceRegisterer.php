<?php

namespace Base;

use Interop\Container\ContainerInterface as Container;

interface ServiceRegisterer
{

    public function register(Container $container);
}
