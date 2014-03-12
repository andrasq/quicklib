<?

/**
 * In-memory fifo, for testing with.
 *
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Fifo_Fake
    implements Quick_Fifo_Reader, Quick_Fifo_Writer
{
    protected $_data = "";
    protected $_offset = 0;

    public function __construct( $data = "" ) {
        $this->_data = $data;
    }

    public function acquire( ) {
        return true;
    }

    public function release( ) {
        return true;
    }

    public function fgets() {
        $strlen = strlen($this->_data);
        if (($endpos = strpos($this->_data, "\n", $this->_offset)) === false) {
            // no terminating newline yet
            return false;
        }
        $line = substr($this->_data, $this->_offset, $endpos - $this->_offset + 1);
        $this->_offset = $endpos + 1;
        return $line;
    }

    public function read( $nbytes ) {
        $strlen = strlen($this->_data);
        if ($this->_offset >= $strlen) return false;
        if (($offset2 = $this->_offset + $nbytes) >= $strlen) {
            // read consumes all the remaining data
            if (substr($this->_data, -1) !== "\n")
                return false;
            $ret = substr($this->_data, $this->_offset, $nbytes);
            $this->_offset = $strlen;
            return $ret;
        }
        else {
            $ret = substr($this->_data, $this->_offset, $nbytes);
            if (substr($ret, -1) === "\n") {
                $this->_offset += $nbytes;
                return $ret;
            }
            elseif (($end2 = strpos($this->_data, "\n", ($end = $this->_offset + $nbytes))) !== false) {
                $ret .= substr($this->_data, $end, $end2 - $end + 1);
                $this->_offset = $end2 + 1;
                return $ret;
            }
            else {
                return false;
            }
        }
    }

    public function ftell(  ) {
        return $this->_offset;
    }

    public function feof(  ) {
        return ($this->_offset >= strlen($this->_data));
    }

    public function rsync(  ) {
        if ($this->_offset > 0) {
            $this->_data = substr($this->_data, $this->_offset);
            $this->_offset = 0;
        }
    }

    public function clearEof(  ) {
        // n/a
    }


    public function fputs( $line ) {
        $this->_data .= $line;
    }

    public function write( $lines ) {
        $this->_data .= $lines;
    }

    public function fflush(  ) {
        // n/a
    }
}
