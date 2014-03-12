<?

/**
 * Log to file, but write data in bunches to speed throughput.
 * Writes atomically with a mutex, since system appends are
 * atomic only to the filesystem block size or so.  Locking
 * adds minimal overhead, just 1%.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     quicklib
 *
 * 2013-02-14 - AR.
 */

class Quick_Logger_FileAtomicBuffered
    extends Quick_Logger_FileAtomic
{
    protected $_buffer = '';
    protected $_bufferedCount = 0;
    protected $_flushInterval = .005;
    protected $_nextFlush_tm = 0;

    public function __destruct( ) {
        $this->_flushBuffer(microtime(true));
        if (is_callable(array('parent', '__destruct'))) parent::__destruct();
    }

    public function debug( $msg ) {
        if ($this->_loglevel >= self::INFO) {
            $this->_bufferit($this->_filters ? $this->_filterit($msg, __FUNCTION__) : $msg, __FUNCTION__);
        }
    }

    public function info( $msg ) {
        if ($this->_loglevel >= self::INFO) {
            $this->_bufferit($this->_filters ? $this->_filterit($msg, __FUNCTION__) : $msg, __FUNCTION__);
        }
    }

    public function err( $msg ) {
        if ($this->_loglevel >= self::ERR) {
            $this->_bufferit($this->_filters ? $this->_filterit($msg, __FUNCTION__) : $msg, __FUNCTION);
        }
    }

    protected function _bufferit( $msg, $method ) {
        $now_tm = microtime(true);
        // buffering 50 lines captures 95% of the speedup available
        if (strlen($msg) < 10000 && $this->_bufferedCount < 50 && $now_tm < $this->_nextFlush_tm) {
            $len = strlen($msg);
            if ($msg[$len-1] !== "\n") $msg .= "\n";
            $this->_buffer .= $msg;
            ++$this->_bufferedCount;
        }
        else {
            $this->_flushBuffer($now_tm);
            $this->_logit($msg);
        }
    }

    protected function _flushBuffer( $now_tm ) {
        if ($this->_buffer !== '') {
            $this->_logit($this->_buffer);
            $this->_buffer = '';
            $this->_bufferedCount = 0;
        }
        $this->_nextFlush_tm = $now_tm + $this->_flushInterval;
    }
}
