<?php

/**
 * Class to safely run large number of calls with curl_multi by
 * keeping a small window of connections busy with back-to-back calls.
 * Curl_multi ties up all httpd's and makes apache non-responsive.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Call_CurlMulti
{
    protected $_mh;
    protected $_ch = array();
    protected $_windowSize = 5;
    protected $_timeout = 120.0;
    protected $_chDone = array();
    protected $_runningCount = 0;

    public function __construct( $mh = null ) {
        if ($mh === null) $mh = $this->_curl_multi_init();
        // keep the multi handle, it caches and reuses the connections
        $this->_mh = $mh;
    }

    public function setTimeout( $timeout ) {
        $this->_timeout = $timeout;
        return $this;
    }

    public function setWindowSize( $windowSize ) {
        $this->_windowSize = $windowSize;
        return $this;
    }

    public function setHandles( Array $handles ) {
        // multi can not capture output sent to stdout, caller must buffer $ch
        $this->_ch = $handles;
        return $this;
    }

    public function addHandles( Array $handles ) {
        foreach ($handles as $ch)
            $this->_ch[] = $ch;
        return $this;
    }

    public function getDoneHandles( ) {
        $ret = $this->_chDone;
        $this->_chDone = array();
        return $ret;
    }

    public function getDoneContent( $ch ) {
        return $this->_curl_multi_getcontent($ch);
    }

    /**
     * Exec() iterates the calls and returns true if all calls have finished.
     * It spends up to setTimeout() seconds before returning.
     *
     * BEWARE:  curl_multi opens a new connection for each handle added.
     * Windowing must be implemented manually, but it works well.  Without windowing,
     * the keepalive connections tie up all apache 2.2.22 httpds and apache becomes
     * non-responsive. (?curl_multi tries to start all, deadlocks vs self?)
     * There is an upper limit on the window size, eg only 1021 when set to 5000.
     */
    public function exec( $timeout = null ) {
        if ($timeout !== null || $timeout = $this->_timeout) $timeout = microtime(true) + $timeout;
        $mh = $this->_mh;

        // load the run window to capacity
        for ($i = $this->_runningCount; $i < $this->_windowSize && ($ch = array_pop($this->_ch)); ++$i) {
            $this->_curl_multi_add_handle($mh, $ch);
        }
        $this->_runningCount = $i;

        do {
            // iterate the exec engine:  start calls, receive replies, check if done.
            // $running gets set to the number of calls not yet finished, 0 if all done
            while (($rv = $this->_curl_multi_exec($mh, $running)) === CURLM_CALL_MULTI_PERFORM)
                ;
            if ($rv !== CURLM_OK) throw new Quick_Rest_Exception("curl_multi_exec error");

            if ($info = $this->_curl_multi_info_read($mh)) {
                // as soon as the window has an open slot, start another call.
                // A rolling window yields as much as 34% higher throughput than a paged window.
                do {
                    $ch = $this->_chDone[] = $info['handle'];
                    $this->_curl_multi_remove_handle($mh, $ch);
                    if ($ch = array_pop($this->_ch))
                        $this->_curl_multi_add_handle($mh, $ch);
                    else
                        --$this->_runningCount;
                    $info = $this->_curl_multi_info_read($mh);
                } while ($info);

                // another exec here raises throughput 7% (fewer context switches)
                $rv = $this->_curl_multi_exec($mh, $running);
                if ($rv !== CURLM_OK && $rv !== CURLM_CALL_MULTI_PERFORM)
                    throw new Quick_Rest_Exception("curl_multi_exec error");
            }
            elseif (!$running) {
                // a ch added to two multis will never run, exec returns CURLM_OK and $running = 0
                $done = true;
                break;
            }
            else {
                // wait for some calls to finish to be able to start more
                // nb: while blocked linux may migrate the process to another core,
                // losing up to 15% of overall performance (cold cache on new cpu)
                // sleeping for 50us not 10us yields +15% throughput
                if ($timeout)
                    usleep(50);
            }

            $done = (!$this->_ch && !$this->_runningCount);

        } while (!$done && $timeout && microtime(true) < $timeout);

        return $done;
    }


    protected function _curl_multi_init( ) {
        return curl_multi_init();
    }

    protected function _curl_multi_add_handle( $mh, $ch ) {
        curl_multi_add_handle($mh, $ch);
    }

    protected function _curl_multi_remove_handle( $mh, $ch ) {
        curl_multi_remove_handle($mh, $ch);
    }

    protected function _curl_multi_exec( $mh, & $running ) {
        return curl_multi_exec($mh, $running);
    }

    protected function _curl_multi_info_read( $mh ) {
        return curl_multi_info_read($mh);
    }

    protected function _curl_multi_getcontent( $ch ) {
        return curl_multi_getcontent($ch);
    }
}
