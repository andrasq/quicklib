<?

/**
 * Write-through cache, caches all values going to and from master.
 * Reads will fetch from the cache, else will retrieve from master
 * and keep a copy in the cache.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-14 - AR.
 */

class Quick_Store_Cache
    implements Quick_Store
{
    public function __construct( Quick_Store $master, Quick_Store $cache ) {
        $this->_master = $master;
        $this->_cache = $cache;
    }

    public function set( $name, $value ) {
        if ($this->_master->set($name, $value)) {
            $this->_cache->set($name, $value);
            return true;
        }
        return false;
    }

    public function get( $name ) {
        if (($item = $this->_cache->get($name)) !== false)
            return $item;
        if (($item = $this->_master->get($name)) !== false)
            // automatically cache values from master
            $this->_cache->set($name, $item);
        return $item;
    }

    public function delete( $name ) {
        $this->_cache->delete($name);
        return $this->_master->delete($name);
    }
}
