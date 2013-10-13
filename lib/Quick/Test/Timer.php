<?

/**
 * Timer class to time functions and methods.  Allows for calibration to
 * subtract out the timer harness overhead.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Test_Timer
{
    protected $_overhead = 0;

    public function _noop( ) {
	// this method left intentionally blank
    }

    // add up how much time it takes just to invoke the test method, so the timing reflects the work done
    // store it normalized to per million loops to allow nloops to vary
    public function calibrate( $nloops, $callback, $args ) {
	if ($callback === null) $callback = array($this, '_noop');
	$nloops *= 20;
	$tm = microtime(true);
	if (is_array($callback)) {
	    $this->_loopMethodCall($nloops, $callback, $args);
	}
	else {
	    $this->_loopFunctionCall($nloops, $callback, $args);
	}
	$tm = microtime(true) - $tm;
	$this->_overhead = $tm * (1000000/$nloops);
	$tm /= 20;
	$nloops /= 20;
	return sprintf("%s: %.6f sec, %.3f / sec\n", "timer overhead", $tm, $nloops/$tm);
    }

    public function timeit( $nloops, $description, $callback, $args = array() ) {
	$tm = microtime(true);
        if (!is_numeric($nloops) || !is_string($description))
            throw new InvalidArgumentException("invalid nloops/description");
	if (is_array($callback)) {
	    $this->_loopMethodCall($nloops, $callback, $args);
	}
	else {
	    $this->_loopFunctionCall($nloops, $callback, $args);
	}
	$tm = microtime(true) - $tm - $this->_overhead * ($nloops/1000000);
	return sprintf("%s: %d in %.6f sec, %.3f / sec\n", $description, $nloops, $tm, $nloops/$tm);
    }


    protected function _loopMethodCall( $nloops, $callback, $args ) {
	list($obj, $method) = $callback;
	switch (count($args)) {
	case 0:
	    // 3.5m / sec
	    for ($i=0; $i<$nloops; ++$i) $obj->$method();
	    break;
	case 1:
	    // 3.3m / sec
	    list($a1) = $args;
	    for ($i=0; $i<$nloops; ++$i) $obj->$method($a1);
	    break;
	case 2:
	    // 3.1m / sec
	    list($a1, $a2) = $args;
	    for ($i=0; $i<$nloops; ++$i) $obj->$method($a1, $a2);
	    break;
	case 3:
	    // 3.1m / sec
	    list($a1, $a2, $a3) = $args;
	    for ($i=0; $i<$nloops; ++$i) $obj->$method($a1, $a2, $a3);
	    break;
	default:
	    // 0.72m / sec (! only; ie much slower to call as generic)
	    for ($i=0; $i<$nloops; ++$i) {
		call_user_func_array($callback, $args);
	    }
	}
    }

    protected function _loopFunctionCall( $nloops, $function, $args ) {
	list($a1, $a2, $a3, $a4, $a5) = array(1, 2, 3, 4, 5);
	switch (count($args)) {
	case 0:
	    // 4.2m / s
	    for ($i=0; $i<$nloops; ++$i) $function();
	    break;
	case 1:
	    // 3.9m / s
	    list($a1) = $args;
	    for ($i=0; $i<$nloops; ++$i) $function($a1);
	    break;
	case 2:
	    // 3.5m/s
	    list($a1, $a2) = $args;
	    for ($i=0; $i<$nloops; ++$i) $function($a1, $a2);
	    break;
	default:
	    // 0.87m/s (! slow)
	    for ($i=0; $i<$nloops; ++$i) {
		call_user_func_array($function, $args);
	    }
	}
    }
}
