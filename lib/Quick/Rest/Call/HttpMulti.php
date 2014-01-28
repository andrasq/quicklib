<?php

/**
 * Http protocol REST caller that runs calls in parallel.
 *
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Call_HttpMulti
    extends Quick_Rest_Call_Http        // extends Call_Http to access its protected _curlConfigure method
{
    protected $_calls = array();
    protected $_profiler;
    protected $_multi;

    public function setProfiling( Quick_Data_Datalogger $profiler = null ) {
        $this->_profiler = $profiler;
        return $this;
    }

    public function addCall( Quick_Rest_Call_Http $call ) {
        $this->_calls[] = $call;
        return $this;
    }

    public function getCalls( ) {
        return $this->_calls;
    }

    public function clearCalls( ) {
        $this->_calls = array();
        return $this;
    }

    public function call( ) {
        if (!$this->_calls) return true;

        // configure each ch
        foreach ($this->_calls as $call) {
            $ch = $handles[] = $call->_curlConfigure();
            curl_setopt_array($ch, array(
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
            ));
        }

        // run the calls as would curl_exec, but run them in parallel
        // the runner adds and removes the curl handles from the multi handle
        $multi = $this->_multi ? $this->_multi : $this->_multi = new Quick_Rest_Call_CurlMulti();
        $multi->addHandles($handles);
        $multi->setTimeout(0);
        while (!$multi->exec()) {
            usleep(10);
        }

        // save the results as from _curlRun
        foreach ($handles as $idx => $ch) {
            $call = $this->_calls[$idx];
            $this->_calls[$idx]->_reply = curl_multi_getcontent($ch);
            $this->_calls[$idx]->_curlinfo = $info = curl_getinfo($ch);
            if ($this->_profiler) $this->_logCallProfile($info);
        }
    }
}
