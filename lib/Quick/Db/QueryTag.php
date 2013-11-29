<?

/**
 * Capture the program trace at the moment the object is constructed.
 *
 * 2013-02-18 - AR
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_QueryTag
{
    protected $_trace;
    protected $_uplevels;

    /**
     * The QueryTag
     */
    public function __construct( $uplevels = 0 ) {
        $this->_uplevels = $uplevels;
        $this->_trace = debug_backtrace(false);  // php 5.2.5 provide_object

        // $this->_trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);   // php 5.3.6
        // $this->_trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $uplevels + 1);   // php 5.4
    }

    public function getTrace( ) {
        // $this->_trace is an array of stack frame summaries.
        // Each frame has the {$file} and {$line} of the caller
        // and the {$class}{$type}{$function} of the function being called.
        // {$object} is the full object being called, and {$args} is the func_get_args() array that was passed.
        // The frame may contain as little as file/line/function/args.

        // debug_backtrace() omits from the stack frames the current function that actually called it.
        // the first entry in the array is the caller of __construct

        return $this->_trace;
    }

    public function toString( $uplevels = null ) {
        if ($uplevels === null)
            return $this->_toString();
        else {
            $ob = clone $this;
            $ob->_uplevels = $uplevels;
            return $ob->__toString();
        }
    }

    public function __toString( ) {
        $uplevels = $this->_uplevels;

        // the trace frame with the current file and line
        $t1 = @$this->_trace[$uplevels];
        return $t1['file'] . "(" . $t1['line'] . ")";

        // one up is the frame with the current class and method name
        // $t2 = @$this->_trace[$uplevels + 1];
        // return $t1['file'] . "(" . $t1['line'] . ") " . $t2['class'] . $t2['type'] . $t2['function'] . "()";
    }

    public function getCaller( $uplevels = null ) {
        if ($uplevels === null) $uplevels = $this->_uplevels;
        return (object) array(
            'file' => $this->_trace[$uplevels]['file'],
            'line' => $this->_trace[$uplevels]['line'],
            'class' => $this->_trace[$uplevels + 1]['class'],
            'type' => $this->_trace[$uplevels + 1]['type'],
            'function' => $this->_trace[$uplevels + 1]['function'],
        );
    }

    static public function getCallerFromTraceAsString( Array & $trace, $uplevels = 0 ) {
        $t1 = @$trace[$uplevels];
        return $t1['file'] . "(" . $t1['line'] . ")";
    }
}
