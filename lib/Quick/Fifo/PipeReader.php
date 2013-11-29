<?

class Quick_Fifo_PipeReader
    implements Quick_Fifo_Reader
{
    protected $_fp;
    protected $_reopenOnEof = false;

    public function __construct( $fp ) {
        if (gettype($fp) !== 'resource' || get_resource_type($fp) !== 'stream')
            throw new Quick_Fifo_Exception("constructor argument is not a stream");
        $this->_fp = $fp;
        stream_set_blocking($fp, false);
        $this->_fragment = new Quick_Fifo_FragmentReader();
    }

    public function fgets( ) {
        if (($line = fgets($this->_fp)) === false && $this->_reopenOnEof) {
            return $this->clearEof();
        }
        return $this->_fragment->fgets($line);

        // 10% faster to omit the eof test
        // return $this->_fragment->fgets(fgets($this->_fp));
    }

    public function read( $nbytes ) {
        $lines = fread($this->_fp, $nbytes);
        if ($lines === false && $this->_reopenOnEof) {
            return $this->clearEof();
        }
        if (substr($lines, -1) !== "\n") {
            $this->_fragment->addFragment($lines);
            return $this->_fragment->fgets(fgets($this->_fp));
        }
        else
            return $this->_fragment->fgets($lines);
    }

    public function fseek( $offset, $whence ) {
        return fseek($this->_fp, $offset, $whence);
    }

    public function ftell( ) {
        return @ftell($this->_fp);
    }

    public function clearEof( ) {
        @fseek($this->_fp, 0, SEEK_CUR);
        return false;
    }

    public function feof( ) {
        return feof($this->_fp);
    }

    public function rsync( ) {
    }
}
