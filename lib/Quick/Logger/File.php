<?

/**
 * Logger that appends the lines to the named file.
 * Each line is newline-terminated.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     quicklib
 *
 * The logfile is kept open for up to .05 seconds, during which all writes
 * are appended to the same file handle.  The file is reopened after more than
 * .05 sec has elapsed.  The logfile consumers must wait out this period, else
 * the log lines will be lost.
 *
 * CAUTION: the writes are not guaranteed atomic, though the operating system
 * may append small strings indivisibly (less than a block).
 *
 * 2013-02-09 - AR.
 */

class Quick_Logger_File
    extends Quick_Logger_Base
{
    protected $_logfile;
    protected $_fp;
    protected $_reopenInterval = .05;
    protected $_nextReopen_tm = 0;

    public function __construct( $logfile, $loglevel = self::INFO ) {
        $this->_logfile = $logfile;
        $this->_loglevel = $loglevel;
    }

    protected function _logit( $msg ) {
        // note: in some cases strlen() can be slower than substr_compare()
        $len = strlen($msg);                                    // 3% cost to call
        if ($msg[$len-1] !== "\n") { $msg .= "\n"; ++$len; }    // 3% cost to test

        if (microtime(true) > $this->_nextReopen_tm)            // 45% cost to call microtime()
            $this->_openLogfile();
        $nb = fputs($this->_fp, $msg);

        if ($nb !== $len)                                       // 3% cost to test (5% to compare <)
            throw new Quick_Logger_Exception("$this->_logfile: error appendling line, short write: ``$msg''");
    }

    protected function _openLogfile( ) {
        if (!($this->_fp = fopen($this->_logfile, "a")))
            throw new Quick_Logger_Exception("$this->_logfile: unable to open for append");
        $this->_nextReopen_tm = microtime(true) + $this->_reopenInterval;
        return $this->_fp;
    }
}
