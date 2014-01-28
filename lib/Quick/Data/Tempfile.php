<?

/**
 * Self-removing tempfile builder, handy for integration tests.
 * Construct as if it were a call to tempnam(), use as it were a string,
 * but file will be automatically deleted when the object falls out of scope.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-14 - AR.
 */

class Quick_Data_Tempfile
{
    protected $_filename;

    public function __construct( $dir = "/tmp", $prefix = "qtemp-" ) {
        // note: tempnam() will use /tmp if $dir is not writable!
        $this->_filename = tempnam($dir, $prefix);
    }

    public function __destruct( ) {
        @unlink($this->_filename);
    }

    public function __toString( ) {
        return $this->_filename;
    }

    public function getPathname( ) {
        return $this->_filename;
    }

    public function getContents( ) {
        return file_get_contents($this->_filename);
    }

    public function putContents( $str ) {
        return file_put_contents($this->_filename, $str, LOCK_EX);
    }

    public function appendContents( $str ) {
        return file_put_contents($this->_filename, $str, FILE_APPEND | LOCK_EX);
    }
}
