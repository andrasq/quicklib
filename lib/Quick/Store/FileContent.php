<?

/**
 * File content store, caches contents indexed by the filename+filemtime.
 * Uses the timestamp to notice if files change and not return stale data.
 * Decorates an actual store by adding filename/filemtime naming.
 *
 * Note that the contents are explicitly set, so the cached contents
 * do not have to be identical to the file contents.  Specifically, the
 * cache can hold the parsed ini file instead of just raw data.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Store_FileContent
    implements Quick_Store
{
    protected $_store;

    public function __construct( $store ) {
        $this->_store = $store;
    }

    public function get( $name ) {
        if (!($key = $this->_createStorageKey($name)))
            return false;
        return $this->_store->get($key);
    }

    public function set( $name, $value ) {
        if (!($key = $this->_createStorageKey($name)))
            return false;
        return $this->_store->set($key, $value);
    }

    public function add( $name, $value ) {
        if (! (method_exists($this->_store, 'add') && ($key = $this->_createStorageKey($name))))
            return false;
        return $this->_store->add($key, $value);
    }

    public function delete( $name ) {
        if (!($key = $this->_createStorageKey($name)))
            return false;
        return $this->_store->delete($key);
    }


    protected function _createStorageKey( $name ) {
        clearstatcache();
        $mtime = @filemtime($name);
        if (!$mtime) return false;
        return $name . '/' . $mtime;
    }
}
