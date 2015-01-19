<?php

namespace Base\Concrete\Di;

use Base\Interfaces\DiPoolStorage as Storage;

class ApcPoolStorage extends ArrayPoolStorage
{

    const CACHE_PREFIX = 'base-di:';

    private $ttl = 1800;

    public function __construct(array $storage = [])
    {
        $this->storage = $storage;
    }

    public function getTtl()
    {
        return $this->ttl;
    }

    public function setTtl($ttl)
    {
        $this->ttl = (int) $ttl > 0 ? $ttl : 1800;
        return $this;
    }

    public function get($unique, $type)
    {
        $value = parent::get($unique, $type);
        if ($value) {
            return $value;
        }
        
        $key = $this->generateKey($unique, $type);
        $value = apc_exists($key) ? apc_fetch($key) : null;
        
        return $value;
    }

    public function set($unique, $type, $value)
    {
        parent::set($unique, $type, $value);
        $key = $this->generateKey($unique, $type);
        apc_store($key, $value, $this->ttl);
    }
    
    protected function generateKey($unique, $type)
    {
        return self::CACHE_PREFIX . ':' . $type . ':' . $unique;
    }

}
