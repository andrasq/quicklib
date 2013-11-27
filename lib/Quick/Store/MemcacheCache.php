<?

/**
 * Quick_Store interface to Memcache.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-05-20 - AR.
 */

class Quick_Store_MemcacheCache
    implements Quick_Store
{
    protected $_memcache;
    protected $_prefix;
    protected $_ttl = 10;

    public function __construct( Memcache $memcache, $prefix, $ttl = 10 ) {
        $this->_memcache = $memcache;
        $this->_prefix = $prefix;
        $this->_ttl = $ttl;
        if ($ttl > 30 * 24 * 3600)
            throw new Quick_Store_Exception("$ttl: invalid Memcache TTL, value too large");
    }

    public function withTtl( $ttl ) {
        $copy = clone $this;
        $copy->_ttl = $ttl;
        return $copy;
    }

    public function set( $name, $value ) {
        return $this->_memcache->set("$this->_prefix$name", $value, 0, $this->_ttl);
    }

    public function get( $name ) {
        return $this->_memcache->get("$this->_prefix$name");
    }

    public function delete( $name ) {
        return $this->_memcache->delete("$this->_prefix$name");
    }

    public function add( $name, $value ) {
        return $this->_memcache->add("$this->_prefix$name", $value, 0, $this->_ttl);
    }

    public function getNames( ) {
        // $info = $this->_memcache->getExtendedStats("cachedump");
        // no longer supported!
        throw new Quick_Store_Exception("memcache no longer supports 'cachedump'");
    }

    public function exists( $name ) {
        return $this->_memcache->get("$this->_prefix$name") !== false;
    }

    public function clear( ) {
        $this->_memcache->flush();
    }
}
