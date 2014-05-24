<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Store_Array
    implements Quick_StoreAtomic
{
    protected $_hash = array();

    public function __construct( Array & $hash = null, $timeout = 600 ) {
        if ($hash !== null) $this->_hash = & $hash;
        // timeout is not implemented
    }

    public function withTtl( $ttl ) {
        $copy = clone $this;
        return $copy;
    }

    public function get( $name ) {
        return isset($this->_hash[$name]) ? $this->_hash[$name] : false;
    }

    public function set( $name, $value ) {
        $this->_hash[$name] = $value;
        return true;
    }

    public function add( $name, $value ) {
        if (isset($this->_hash[$name])) return false;
        $this->_hash[$name] = $value;
        return true;
    }

    public function delete( $name ) {
        if (!isset($this->_hash[$name])) return false;
        unset($this->_hash[$name]);
        return true;
    }

    public function exists( $name ) {
        return array_key_exists($name, $this->_hash);
    }
}
