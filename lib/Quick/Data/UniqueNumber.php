<?

/**
 * Return numbers that are guaranteed unique on this system.
 * Relies on a sub-microsecond resolution timestamp, 5-digit process ids,
 * php having separate pids per thread/process, and unix process ids
 * not being reused until the entire pid pool has been cycled.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * Note that there is still a small race condition:  if the process table
 * is full and the current process exists, the next process will get the same
 * process id.  If this all happens within the same timestamp (1 ns),
 * the previous number will be generated.
 */

class Quick_Data_UniqueNumber
    implements Quick_Db_Fetchable // Quick_Data_Fetchable
{
    protected $_fetcher = 'fetchDecimal';

    public function fetch( ) {
        $m = $this->_fetcher;
        return $this->$m();
    }

    public function reset( ) {
        return true;
    }

    public function asDecimal( ) {
        $copy = new Quick_Data_UniqueNumber();
        $copy->_fetcher = 'fetchDecimal';
        return $copy;
    }

    public function asHex( ) {
        $copy = new Quick_Data_UniqueNumber();
        $copy->_fetcher = 'fetchHex';
        return $copy;
    }

    public function asBase64( ) {
        $copy = new Quick_Data_UniqueNumber();
        $copy->_fetcher = 'fetchBase64';
        return $copy;
    }

    public function fetchDecimal( ) {
        static $last;
        do {
            $tm = microtime(true);

            // sec will stay 10 digits for the next 270 years, no need to pad
            $sec = (int)$tm;

            // faster to just leave the extra '1' between sec and usec
            // adding 1e9 at the front zero-fills at the left;
            // muliplying by 1e9 zero-fills on the right;
            // adding 1e9 instead of adding 1. avoids the '.' in the string
            // note that for some reason it`s much faster to use 100000 than 1e5
            $nsec = 1000000000 + (int)(($tm - $sec) * 1000000000);

            $num = (100000 + getmypid()) . $sec . $nsec;

        } while ($num === $last);
        return $last = $num;
    }

    protected function fetchDecimalAlternate( ) {
        static $last;
        do {
            $tm = microtime(true);
            $nsec = ($tm - ($sec = (int)$tm)) * 1000000000;
            $num = sprintf("%05d%010d%09d", getmypid(), $sec, $nsec);
        } while ($num === $last);
        return $last = $num;
    }

    public function fetchHex( ) {
        static $last;
        do {
            $tm = microtime(true);
            $nsec = ($tm - ($sec = (int)$tm)) * 1000000000;
            $num = sprintf("%04x%08x%08x", getmypid(), $sec, $nsec);
        } while ($num === $last);
        return $last = $num;
    }

    public function fetchBase64( ) {
        static $last;
        do {
            $tm = microtime(true);
            $nsec = ($tm - ($sec = (int)$tm)) * 1000000000;
            // first two digits are always "AA" from the 0000 high bits of the pid, skip them
            $num = substr(base64_encode(pack("NNN", getmypid(), $sec, $nsec)), 2);
            //$num = base64_encode(pack("NNN", getmypid(), $sec, $nsec));
        } while ($num === $last);
        return $last = $num;
        // WARNING: base64 encoding uses '/' as one of its symbols, cannot use in filenames
    }

    public function __toString( ) {
        return $this->fetch();
    }
}