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

    public function open( ) {
        // only one consumer at a time, mutex implemented with a pidfile
        $this->_mutex = new Quick_Proc_Pidfile($this->_pidfile);
        $this->_mutex->acquire();
        $this->_isOpen = true;

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
                $fp = @fopen("/dev/null", "r");
                $ok = (bool)$fp;
                $this->_header = new Quick_Fifo_Header("/dev/null");
            }
            if (!$ok) throw new Quick_Fifo_Exception("$this->_logfile: unable to acquire fifo as $this->_datafile");
        }
        else {
            $fp = @fopen($this->_datafile, "r");
            $this->_header = new Quick_Fifo_Header($this->_infofile);
        }

        if (!$fp) throw new Quick_Fifo_Exception("$this->_datafile: unable to open for reading");

        $this->_reader = new Quick_Fifo_PipeReader($fp);
        $this->_loadState();
        return $this;
    }

    public function close( ) {
        if ($this->_isOpen) {
            if ($this->feof()) {
                // if done with the data, clean up work files
                @unlink($this->_datafile);
                @unlink($this->_infofile);
            }
            $this->_mutex->release();
            $this->_isOpen = false;
        }
        return $this;
    }

    public function clearEof( ) {
        // switch to reading new the logfile that was created while we were busy
        $this->close();
        return $this->open();
    }

    public function fgets( ) {
        return $this->_reader->fgets();
// FIXME: flip to reading revised original file on eof!
    }

    public function read( $nbytes ) {
        return $this->_reader->read($nbytes);
// FIXME: flip to reading revised original file on eof!
    }

    public function ftell( ) {
        return $this->_reader->ftell();
    }

    public function rsync( ) {
        $this->_saveState();
    }

    public function feof( ) {
        return $this->_reader->feof();
    }

    public function fputs( $line ) {
        if ($line > '') {
            $nb = file_put_contents(
                $this->_logfile,
                substr($line, -1) === "\n" ? $line : $line . "\n",
                LOCK_EX | FILE_APPEND
            );
            if ($nb < strlen($line)) throw new Quick_Fifo_Exception("$this->_logfile: unable to write to fifo");
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

    protected function _saveState( ) {
        $this->_header->saveState(array('HEAD' => $this->_reader->ftell()));
    }
}
