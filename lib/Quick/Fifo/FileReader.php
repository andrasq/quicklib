<?

/**
 * System logfile fifo reader.
 *
 * System logfiles are created as needed and store newline-terminated records.
 * The logfile fifo reader removes the logfile (letting a new one take in its place),
 * and consumes the data.
 *
 * The user must have write permission to the logfile directory
 * to create new files and remove the file once consumed.
 * Only local filesystem logfiles are handled.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-11 - AR.
 * @Author:     andras@2uick.com
 */

class Quick_Fifo_FileReader
    implements Quick_Fifo_Reader, Quick_Fifo_Writer
{
    protected $_logfile, $_datafile, $_infofile, $_pidfile;
    protected $_reader;
    protected $_header;
    protected $_mutex;
    protected $_openDelay = .012;
    protected $_isOpen = false;
    protected $_isOwned = false;
    protected $_isShared = false;

    public function __construct( $filename ) {
        $this->_logfile = $filename;                    // file continuously appended by others
        $this->_datafile = "$filename.(data)";          // renamed data file read by us
        $this->_infofile = "$filename.(info)";          // fifo header storing current read offset
        $this->_pidfile = "$filename.(pid)";            // fifo consumer exclusive access pid (for header update)
    }

    public function __destruct( ) {
        // note: state is not automatically saved on exit, must be done explicitly
        // this way if there is a problem, the old state will be left unaltered
        if ($this->_isOpen) $this->close();
    }

    public function setSharedMode( $yesno ) {
        $this->_isShared = $yesno;
    }

    // obtain ownership of multi-user fifo
    public function acquire( ) {
        if ($this->_isOwned) {
            // do not reload state if already owned, else might read same item over and over
            return true;
        }
        elseif ($this->_isOpen && $this->_isOwned = $this->_mutex->acquire()) {
            // reload state to be in sync with state saved by last owner
            $this->_isOwned = getmypid();
            $this->_loadState();
            return true;
        }
        else
            return false;
    }

    // give up ownership of shared fifo
    public function release( ) {
        if ($this->_isOwned === getmypid()) {
            $this->_mutex->release();
            $this->_isOwned = false;
        }
        return $this;
    }

    public function open( ) {
        if (!$this->_isShared) {
            // exclusive mode: only one consumer, mutex implemented with a pidfile
            $this->_mutex = new Quick_Proc_Pidfile($this->_pidfile);
            $this->_mutex->acquire();
            $this->_isOwned = getmypid();
        }

        // if the datafile (renamed logfile) already exists, resume reading it
        // else grab (rename) the logfile, let a new one be created in its place
        // nb: if there is no data, use /dev/nul as the data and header files, to not clutter the directory
        if (!file_exists($this->_datafile)) {
            $ok = @rename($this->_logfile, $this->_datafile);
            if ($ok) {
                // wait for traffic to settle, users of the logfile may reuse the file handle for up to .01 sec
                usleep($this->_openDelay * 1000000);
                $fp = @fopen($this->_datafile, "r");
                $this->_header = new Quick_Fifo_Header($this->_infofile);
            }
            else {
                //$ok = touch($this->_datafile);
                $fp = fopen("/dev/null", "r");
                $this->_header = new Quick_Fifo_Header("/dev/null");
                $ok = (bool)$fp;
            }
            if (!$ok) throw new Quick_Fifo_Exception("$this->_logfile: unable to acquire fifo as $this->_datafile");
        }
        else {
            $fp = @fopen($this->_datafile, "r");
            $this->_header = new Quick_Fifo_Header($this->_infofile);
        }

        if (!$fp) throw new Quick_Fifo_Exception("$this->_datafile: unable to open for reading");

        if ($this->_isShared) {
            // shared mode: one consumer at a time, mutex implememented with LOCK_EX by header
            $this->_mutex = $this->_header;
        }

        $this->_reader = new Quick_Fifo_PipeReader($fp);
        $this->_loadState();
        $this->_isOpen = true;
        return $this;
    }

    public function close( ) {
        if ($this->_isOpen) {
            $this->release();
            $this->_isOpen = false;
        }
        return $this;
    }

    public function clearEof( ) {
// @FIXME: clearEof should not implicily checkpoint the state, but is only way to maintain semantics
// of always advancing the read point
        $this->rsync();
        // switch to reading new the logfile that was created while we were busy
        // $this->close();
        // return $this->open();
    }

    public function fgets( ) {
        return $this->_reader->fgets();

        /*
         * Note: fgets and read will return false on end of file until the
         * already read lines are checkpointed with rsync.  This is needed
         * to prevent read-ahead from changing the fifo state.
         */
    }

    public function read( $nbytes ) {
        return $this->_reader->read($nbytes);
    }

    public function ftell( ) {
        return $this->_reader->ftell();
    }

    public function rsync( $offset = null ) {
        if ($this->_isOwned) {
            if ($this->_reader->feof() && ($offset === null || $offset === $this->_reader->ftell())) {
                // if done with the data, switch to reading the newly arrived logfile
                @unlink($this->_infofile);
                @unlink($this->_datafile);
                $this->close();
                $this->open();
            }
            else {
                $this->_saveState($offset);
            }
        }
        else
            throw new Quick_Fifo_Exception("not owner, cannot overwrite fifo state");
    }

    public function feof( ) {
        return $this->_reader->feof();
    }

    public function fputs( $line ) {
        if ($line > '') {
            $len = strlen($line);
            $nb = file_put_contents(
                $this->_logfile,
                $line[$len-1] === "\n" ? $line : $line . "\n",
                LOCK_EX | FILE_APPEND
            );
            if ($nb < $len) throw new Quick_Fifo_Exception("$this->_logfile: unable to write to fifo");
        }
    }

    public function write( $lines ) {
        $this->fputs($lines);
    }

    public function fflush( ) {
        // nothing required
    }

    protected function _loadState( ) {
        $this->_header->loadState();
        $this->_reader->fseek($this->_header->getState('HEAD'), SEEK_SET);
    }

    protected function _saveState( $offset = null ) {
        if ($offset === null) $offset = $this->_reader->ftell();
        $this->_header->saveState(array('HEAD' => $offset));
    }
}
