<?

/**
 * Store files in a directory with get/set semantics.
 * Unlike caching stores, directory stores never time out the files.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * The implementation guarantees serializability (ie, that the state
 * seen by each of a set of concurrent accesses will be the same as if
 * the calls had executed serially in some order.  Basically, that no
 * half-and-half content will be saved or returned.)
 */

class Quick_Store_FileDirectory
    implements Quick_StoreAtomic
{
    protected $_dir = '';
    protected $_prefix = '';
    protected $_createMode = 0644;

    public function __construct( $dirname, $prefix = '' ) {
        $this->_dir = $dirname;
        $this->_prefix = $prefix;
    }

    public function createFile( ) {
        $filename = tempnam($this->_dir, $this->_prefix);
        return substr($filename, strlen($this->_dir) + 1 + strlen($this->_prefix));
    }

    public function set( $name, $value ) {
        $nb = file_put_contents($this->getFilename($name), (string)$value, LOCK_EX);
        return $nb !== false;
    }

    public function add( $name, $value ) {
        $fp = @fopen($this->getFilename($name), "x", $this->_createMode);
        if ($fp === false) return false;
        $nb = @fwrite($fp, (string)$value);
        flock($fp, LOCK_UN);
        return ($nb !== false);
    }

    public function get( $name ) {
        return $this->_readfile($this->getFilename($name));
    }

    public function delete( $name ) {
        return @unlink($this->getFilename($name));
    }

    public function exists( $name ) {
        return file_exists($this->getFilename($name));
    }

    public function getNames( ) {
        $prefixlen = strlen($this->_prefix);
        $dp = opendir($this->_dir);
        while (($name = readdir($dp)) > '') {
            if ($name === '.' || $name === '..') continue;
            if (strncmp($name, $this->_prefix, $prefixlen) === 0)
                $ret[] = substr($name, $prefixlen);
        }
        return isset($ret) ? $ret : array();
    }

    public function getFile( $name, $filename = null ) {
        if ($filename !== null)
            return copy("$this->_dir/$this->_prefix$name", $filename);
        else
            return "$this->_dir/$this->_prefix$name";
    }

    public function getFilename( $name ) {
        return "$this->_dir/$this->_prefix$name";
    }

    protected function _readfile( $filename ) {
        if ($fp = @fopen($filename, "r")) {
            flock($fp, LOCK_EX);
            $ret = file_get_contents($filename);
            flock($fp, LOCK_UN);
            return $ret;
        }
        else return false;
    }
}
