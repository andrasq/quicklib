<?php

/**
 * Key-value store in files with TTL embedded as the file modification time.
 * When filemtime() <= time(), the TTL has expired.
 *
 * Expired values persist until garbage collected, eg. with $this->gc()
 * or `find $STORE -mmin +0 -a \! -name '.*' -a \! -name '[_]*' -exec rm '{}' \;`
 * Keys must not contain "/" or "\x00".  Values are saved in files named
 * for the keys, hashed into sub-directories to limit directory size.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Store_FileHash
    implements Quick_Store
{
    protected $_dir;
    protected $_prefix;
    protected $_ttl;
    protected $_mutex;

    public function __construct( $dir, $prefix, $ttl = 600 ) {
        $this->_dir = $dir;
        $this->_prefix = $prefix;
        $this->_ttl = $ttl;
        $this->_mutex = fopen("$dir/__mutex__", "a");
    }

    public function withTtl( $ttl ) {
        $copy = clone $this;
        $copy->_ttl = $ttl;
        return $copy;
    }

    public function set( $name, $value ) {
        $filename = $this->_filename($name, true);
        flock($this->_mutex, LOCK_EX);
        if (file_put_contents($filename, (string)$value) < strlen($value))
            throw new Quick_Store_Exception("write error: unable to save to ``$filename''");
        touch($filename, time()+$this->_ttl);
        flock($this->_mutex, LOCK_UN);
        return true;
    }

    public function get( $name ) {
        $filename = $this->_filename($name);
        flock($this->_mutex, LOCK_EX);
        clearstatcache();
        $ret =  file_exists($filename) ? file_get_contents($filename) : false;
        flock($this->_mutex, LOCK_UN);
        return $ret;
    }

    // insert into store unless already there
    public function add( $name, $value ) {
        $filename = $this->_filename($name, true);
        flock($this->_mutex, LOCK_EX);
        clearstatcache();
        if (file_exists($filename) && filemtime($filename) >= time()) {
            flock($this->_mutex, LOCK_UN);
            return false;
        }
        else {
            if (file_put_contents($filename, (string)$value) < strlen($value))
                throw new Quick_Store_Exception("write error: unable to save to ``$filename''");
            touch($filename, time()+$this->_ttl);
            flock($this->_mutex, LOCK_UN);
            return true;
        }
    }

    public function delete( $name ) {
        $filename = $this->_filename($name);
        flock($this->_mutex, LOCK_EX);
        clearstatcache();
        if (file_exists($filename)) unlink($filename);
        flock($this->_mutex, LOCK_UN);
        return true;
    }

    public function gc( ) {
        clearstatcache();
        $limit = 0;
        $this->_gcSubdir($this->_dir, time(), $limit);
    }

    protected function _gcSubdir( $dir, $now, & $limit ) {
        $dp = opendir($dir);
        while ($fn = readdir($dp)) {
            if ($fn === '.' || $fn === '..' || $fn[0] === '_') continue;
            $filename = "$dir/$fn";
            if (is_dir($filename)) {
                $this->_gcSubdir($filename, $now, $limit);
            }
            elseif (file_exists($filename) && (filemtime($filename) <= $now)) {
                // to prevent set/delete race conditions re-test file under mutex before removing
                flock($this->_mutex, LOCK_EX);
                clearstatcache();
                if (file_exists($filename) && filemtime($filename) <= $now) {
                    unlink($filename);
                    if ($limit > 0) --$limit;
                }
                flock($this->_mutex, LOCK_UN);
            }
        }
    }

    protected function _filename( $name, $create = false ) {
        //$dirname = $this->_dir . "/" . substr(md5($name), 0, 2);
        $name = "{$this->_prefix}{$name}";
        // if (strpos($name, '/') !== false) $name = str_replace('/', '%2F', $name);
        // if (strpos($name, "\0") !== false) $name = str_replace("\0", '%00', $name);
        $hash = md5($name);
        $dirname = "{$this->_dir}/{$hash[0]}{$hash[1]}/{$hash[2]}";
        if ($create && !is_dir($dirname)) {
            flock($this->_mutex, LOCK_EX);
            mkdir($dirname, 0777, true);
            flock($this->_mutex, LOCK_UN);
        }
        return "$dirname/$name";
    }
}
