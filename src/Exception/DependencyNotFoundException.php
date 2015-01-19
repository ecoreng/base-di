<?php

namespace Base\Exception;

use Interop\Container\Exception\NotFoundException;

class DependencyNotFoundException extends \Exception implements NotFoundException
{
    
}