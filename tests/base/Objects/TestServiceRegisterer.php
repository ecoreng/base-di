<?php

namespace Base\Test\Objects;

use Interop\Container\ContainerInterface as Container;


class TestServiceRegisterer implements \Base\ServiceRegisterer
{
    public function register(Container $container)
    {
        $container->set('Foo\Bar', function() {return 'woo';});
    }
}