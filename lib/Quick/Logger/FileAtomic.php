<?

/**
 * Logger that atomically appends the lines to the named file.
 * Lines are newline-terminated.  Each append is done under a mutex
 * to guarantee that writes do not overlap.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     quicklib
 *
 * It is presumed that an open handle to the file may be used for up to
 * 1/20th of a second before needing to be reopened.  Logtable archival
 * should rename the file and wait before removing it to not lose appends.
 *
 * 2013-02-09 - AR.
 */

class Quick_Logger_FileAtomic
    extends Quick_Logger_File
    implements Quick_Logger, Quick_Logger_Filter
{
    protected function _logit( $msg ) {
        // note: in some cases strlen() can be slower than substr_compare()
	$len = strlen($msg);
	if ($msg[$len-1] !== "\n") $msg .= "\n";

        if (microtime(true) > $this->_nextReopen_tm) $this->_openLogfile();
        $fp = $this->_fp;
	flock($fp, LOCK_EX);
	$nb = fputs($fp, $msg);
	flock($fp, LOCK_UN);

	//if ($msg[$nb] !== '') throw new Quick_Logger_Exception("$this->_logfile: error appendling line: ``$line''");
	if ($nb < $len) throw new Quick_Logger_Exception("$this->_logfile: error appendling line: ``$line''");
        //if ($nb < strlen($msg)) throw new Quick_Logger_Exception("$this->_logfile: error appendling line: ``$line''");
	// wow! 50% faster to omit the $msg[$nb] test ?! ... php 5.2.5 vs 5.3? or charset dependent??
    }
}
