<?

/**
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Fifo_FileCompactor
{
    protected $_filename;
    protected $_chunkSize = 20480;

    public function __construct( $filename = null ) {
        $this->_filename = $filename;
    }

    public function setChunkSize( $sz ) {
        $this->_chunkSize = $sz;
    }

    // compact the file by removing the length bytes at offset
    public function compact( $offset, $length ) {
        if ($length < 0) return false;

        // compact toward the left, anything to the left of 0 disappears
        if ($offset < 0) {
            // remove range and shift remainder to $offset.
            // If offset is negative, some of what is left will not be visible!
            // Eg delete 4 at offset -2:  --.AB are removed, cd moves to offset -2,
            // first visible is E: [--.abcdef] (-2, 4) --> [cd.ef] => "ef"
            // (where . shows the offset = 0 origin)
            $offset = 0;
        }

        $wfp = $this->_fopen($offset, "r+");
        $rfp = $this->_fopen($offset + $length, "r");

        if ($offset >= $this->_filesize($wfp))
            return false;

        // move the bulk of the data concurrently with appends to the file
        $this->_copyDown($rfp, $wfp);

        // when done, prevent any further appends and also copy whatever just arrived
        flock($rfp, LOCK_EX);
        try {
            $this->_copyDown($rfp, $wfp);
            ftruncate($wfp, ftell($wfp));
        }
        catch (Exception $e) {
            // must release the lock separately from just closing the handle
            flock($rfp, LOCK_UN);
            throw $e;
        }
        flock($rfp, LOCK_UN);
        return true;
    }

    // create a hole in the file of length bytes at offset
    public function uncompact( $offset, $length ) {
        // WRITEME
    }

    protected function _fopen( $offset, $mode ) {
        $fp = fopen($this->_filename, $mode);
        if (!$fp) throw new Quick_Fifo_Exception("$this->_filename: unable to open for \"$mode\"");
        fseek($fp, $offset, SEEK_SET);
        return $fp;
    }

    protected function _filesize( $fp ) {
        $pos = ftell($fp);
        fseek($fp, 0, SEEK_END);
        $len = ftell($fp);
        fseek($fp, $pos, SEEK_SET);
        return $len;
    }

    // copy data from a higher byte offset to lower byte offset
    protected function _copyDown( $rfp, $wfp ) {
        $nbytes = $this->_chunkSize;
        while (($buf = fread($rfp, $nbytes)) > "" || !feof($rfp)) {
            $nb = fwrite($wfp, $buf);
            if (!$nb) throw new Quick_Fifo_Exception("$this->_filename: write error when compacting");
        }
        if ($buf === false) throw new Quick_Fifo_Exception("$this->_filename: read error when compacting");
    }
}
