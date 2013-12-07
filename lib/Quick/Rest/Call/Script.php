<?

/**
 * REST call to a php script.
 * Instead of forking a copy, runs the script inline, saving startup overhead
 * and allowing the called script to benefit from the webserver apc cache.
 * The called script must deliver its results to stdout.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Call_Script
    implements Quick_Rest_Call
{
    protected $_method, $_methodArg, $_switches = array();
    protected $_url, $_params = array();
    protected $_profiler;

    public function setProfiling( Quick_Data_Datalogger $profiler = null ) {
        $this->_profiler = $profiler;
        return $this;
    }

    // the method is the php script to run and its first argument
    public function setMethod( $method, $methodArg = null /* , $switches */ ) {
        $this->_method = $method;
        $this->_methodArg = $methodArg;
        if (count($args = func_get_args()) > 2 && is_array($args[2]))
            $this->_switches = $args[2];
        return $this;
    }

    public function setHeader( $name, $value ) {
        return $this;
    }

    public function setUrl( $url ) {
        $this->_url = $url;
        return $this;
    }

    public function setParam( $name, $value ) {
        $this->_params[$name] = $value;
        return $this;
    }

    public function setParams( Array $params ) {
        $this->_params = $params;
        return $this;
    }

    public function call( ) {
        $args = array_merge(
            array($this->_method),
            $this->_switches,
            array($this->_methodArg, $this->_url, http_build_query($this->_params))
        );
        $this->_reply = & $this->_runScript($args);
        return $this;
    }

    public function getReply( ) {
        return $this->_reply;
    }


    // run php script by including it, without forking
    protected function & _runScript( Array $argv ) {
        $x = $this->_saveGlobals();
        $this->_prepareScriptArgv($argv);
        ob_start();
        include dirname(__FILE__) . '/ScriptRunner.php';
        $output = ob_get_clean();
        $this->_restoreGlobals($x);
        return $output;
    }

    protected function _prepareScriptArgv( Array $args ) {
        global $argv, $argc;
        $_SERVER['argv'] = $argv = $args;
        $_SERVER['argc'] = $argc = count($args);
    }

    protected function _saveGlobals( ) {
        $x = array();
        foreach ($GLOBALS as $k => & $v)
            if ($k !== 'GLOBALS')
                $x[$k] = $v;
        return $x;
    }

    protected function _restoreGlobals( Array & $x ) {
        // $GLOBALS['GLOBALS'] is a "magic" self-reference and loses its properties if overwritten
        foreach ($GLOBALS as $k => & $v) {
            if ($k !== 'GLOBALS')
                unset($GLOBALS[$k]);
        }
        // the 'GLOBALS' self-reference moves to be first on the list
        foreach ($x as $k => & $v) {
            if ($k !== 'GLOBALS')
                $GLOBALS[$k] = & $v;
        }
    }
}
