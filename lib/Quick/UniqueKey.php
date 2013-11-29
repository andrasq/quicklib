<?

/**
 * Generate globally unique keys, quickly.
 * "Globally" means unique to a cluster all having distinct `hostname -s`.
 * Requires distinct process id's for every php thread on a server.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-26 - AR.
 */

class Quick_UniqueKey
{
    protected $_key;
    protected $_host;

    public static function create( ) {
        return new self;
    }

    public static function createKey( ) {
        static $instance = null;
        if (!$instance) $instance = new self();
        return $instance->makeKey();
    }

    public function makeKey( ) {
        static $last_key = 0;

        if (!($host = $this->_host)) {
            $host = $this->_host = substr(($h = php_uname('n')), 0, strcspn($h, '.'));
        }

        $pid = getmypid();

        do {
            // $u = base64_encode(pack('d', microtime(true)));
            // %d conversion much faster than %f
            $tm = microtime(true);
            $tm_s = (int)$tm;
            $tm_u = sprintf("%06d", (($tm - $tm_s) * 1000000));
        } while (($k = "$host-$pid-$tm_s.$tm_u") === $last_key);

        return $last_key = $k;

        // generates about 200k keys / sec (half due to microtime: 345k w/o, and 245k/s w/ just (string)$tm)
        // base64_encode(pack("d", $tm)) is more compact and 15% faster, but is not ordered (little-e bytes)
        // about 160k/s if host is not cached
        // uniqid() only does about 10k/sec, and .5k/sec on older kernels (limited by context switch speed?)
   }

    public function getKey( ) {
        return $this->_key ? $this->_key : $this->_key = $this->makeKey();
    }

    public function __toString( ) {
        return $this->getKey();
    }
}
