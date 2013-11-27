<?

/**
 * The APC cache is a very very fast local store, shared by httpd php processes.
 * About 10x faster than memcache or SSD filecache.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-05-02 - AR.
 */

class Quick_Store_ApcCache
    implements Quick_Store
{
    protected $_prefix;
    protected $_ttl = 10;

    public function __construct( $prefix, $ttl = 10 ) {
        if (!function_exists('apc_store'))
            throw new Quick_Store_Exception("APC caching not enabled in php.ini, use Quick_Store_Null");
        $this->_prefix = $prefix;
        $this->_ttl = $ttl;
    }

    public function withTtl( $ttl ) {
        $copy = clone $this;
        $copy->_ttl = $ttl;
        return $copy;
    }

    public function set( $name, $value ) {
        return apc_store("$this->_prefix$name", $value, $this->_ttl);
    }

    public function get( $name ) {
        return apc_fetch("$this->_prefix$name");
    }

    public function delete( $name ) {
        return apc_delete("$this->_prefix$name");
    }

    public function add( $name, $value ) {
        return apc_add("$this->_prefix$name", $value, $this->_ttl);
    }

    public function getNames( ) {
        $prefix = $this->_prefix;
        $len = strlen($prefix);
        $ret = array();
        $info = apc_cache_info("user");
        if (!empty($info['cache_list'])) {
            $key = isset($info['cache_list'][0]['key']) ? 'key' : 'info';
            foreach (($info = $info['cache_list']) as $item) 
                if (strncmp($item[$key], $prefix, $len) === 0)
                    $ret[] = substr($item[$key], $len);
        }
        return $ret;
    }

    public function exists( $name ) {
        return apc_exists("$this->_prefix$name");
    }

    public function clear( ) {
        apc_clear_cache("user");
    }
}
