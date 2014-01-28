<?

/**
 * Stream fragment reader.
 * Reassembles newline-terminated lines as eg. when read in chunks
 * from non-blocking sockets.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-06 - AR.
 */

class Quick_Fifo_FragmentReader
{
    public $fragment;

    public function __construct( $string = '' ) {
	$this->fragment = $string;
    }

    public function isFragment( ) {
	// for speed, test the public $this->fragment field directly
	return $this->fragment > '';
    }

    public function addFragment( $string ) {
	$this->fragment .= $string;
    }

    public function fgets( $string ) {
	if (substr($string, -1) === "\n") {
	    if ($this->fragment > '') {
		// string completes fragments, return completed line
		$string = $this->fragment . $string;
		$this->fragment = '';
		return $string;
	    }
	    else {
		// no other fragments, string by itself is a complete line
		return $string;
	    }
	}
	else {
	    // add string to fragments, still waiting for newline
	    $this->fragment .= $string;
	    return false;
	}
    }
}
