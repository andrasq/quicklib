<?

/**
 * FileCache with locking semantics to hold strings.
 * Values are kept in separate files in a flat directory,
 * expiration times are stored as the file modification time.
 * Works on localhost filesystem only, not via NFS.
 *
 * About 30% slower than the lock-free version, but has no race conditions
 * and supports arbitrarily large data strings.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-03-07 - AR.
 */

class Quick_Store_FileCache
    implements Quick_Store
{
    protected $_dirname, $_prefix, $_ttl;
    protected $_nextStatcacheClear_tm = 0, $_statcacheClearInterval = .05;

    public function __construct( $dirname, $prefix, $ttl ) {
        $this->_dirname = $dirname;
        $this->_prefix = $prefix;
        $this->_ttl = $ttl;
        $this->_mutex = fopen("$dirname/__mutex__", "w");
        if (!$this->_mutex) throw new Quick_Store_Exception("$dirname: unable to create/open mutex file");
    }

    public function withTtl( $ttl ) {
        $copy = clone $this;
        $copy->_ttl = $ttl;
        return $copy;
    }

    public function set( $name, $value ) {
        flock($this->_mutex, LOCK_EX);
        if (file_put_contents($filename = $this->_getFilename($name), $value) !== false) {
            touch($filename, time() + $this->_ttl);
            flock($this->_mutex, LOCK_UN);
            return true;
        }
        else {
            flock($this->_mutex, LOCK_UN);
            throw new Quick_Store_Exception($this->_getFilename($name).": unable to write file store file");
        }
    }

    public function get( $name ) {
        if (($now_tm = microtime(true)) > $this->_nextStatcacheClear_tm) {
            // we accept not seeing newly updated values for up to .05 sec
            clearstatcache();
            $this->_nextStatcacheClear_tm = $now_tm + $this->_statcacheClearInterval;
        }

        flock($this->_mutex, LOCK_EX);
        if (filemtime($filename = $this->_getFilename($name)) < $now_tm) {
            flock($this->_mutex, LOCK_UN);
            $this->_expireFilename($filename, $now_tm);
            return false;
        }
        $ret = file_get_contents($filename);
        flock($this->_mutex, LOCK_UN);

        if ($ret === false)
            throw new Quick_Store_Exception($this->_getFilename($name).": unable to read file store file");
        return $ret;
    }

    public function delete( $name ) {
        flock($this->_mutex, LOCK_EX);
        $ok = unlink($this->_getFilename($name));
        if (!$ok) $ok = !file_exists($name);
        flock($this->_mutex, LOCK_UN);
        if ($ok === false)
            throw new Quick_Store_Exception($this->_getFilename($name).": unable to delete file store file");
        return true;
    }

    public function expireContents( ) {
        $dp = opendir("$this->_dirname");
        clearstatcache();
        $now_tm = time();
        while ($name = readdir($dp)) {
            if ($name === '.' || $name === '..' || $name === '__mutex__') continue;
            $filename = "$this->_dirname/$name";
            if (filemtime($filename) < $now_tm)
                $this->_expireFilename($filename, $now_tm);
        }
    }

    public function _getFilename( $name ) {
        // md5() adds 5% overhead
        return "$this->_dirname/$this->_prefix$name";
    }

    protected function _expireFilename( $filename, $now_tm ) {
        clearstatcache();
        flock($this->_mutex, LOCK_EX);
        if (filemtime($filename) < $now_tm) {
            $ok = unlink($filename);
            flock($this->_mutex, LOCK_UN);
            // found and removed expired file
            return true;
        }
        flock($this->_mutex, LOCK_UN);
        // file was not expired
        return false;
    }
}
