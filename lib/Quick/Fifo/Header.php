<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Fifo_Header
{
    protected $_filename;
    protected $_fp;
    protected $_state = array(
        'FIFO' => '1.0',
        'HEAD' => 0,
        'LEN' => 100,
    );
    protected $_isWriting = false;

    public function __construct( $filename ) {
        $this->_filename = $filename;
        $this->_fp = $this->_openFile('r', 'for reading');
    }

    public function getState( $field = null ) {
        return ($field === null) ? $this->_state : $this->_state[$field];
    }

    public function setState( $name, $value ) {
        $this->_state[$name] = $value;
    }

    public function loadState( ) {
        fseek($this->_fp, 0, SEEK_SET);
        if (($str = fgets($this->_fp)) === false) {
            // not an error if cannot read saved header, could be a new fifo
            $str = $this->_padHeaderString(json_encode($this->_state));
        }
        if (!is_array($info = json_decode($str, true)) || empty($info['FIFO']))
            throw new Quick_Fifo_Exception("$this->_filename: invalid json fifo header: ``$str''");

        // merge the read info into the object state, but always use the actual header string length
        // in case the header is followed by data
        foreach ($info as $k => $v) $this->_state[$k] = $v;
        $this->_state['LEN'] = strlen($str);

        return $this->_state;
    }

    public function saveState( Array $info = array() ) {
        if (!$this->_isWriting) {
            // reopen the file for writing, to not need write perms for read-only access
            @touch($this->_filename);
            $this->_fp = $this->_openFile('r+', "for writing");
            $this->_isWriting = true;
        }
        if ($info) $this->_state = array_merge($this->_state, $info);
        fseek($this->_fp, 0, SEEK_SET);
        $str = $this->_padHeaderString(json_encode($this->_state));
        $nb = fputs($this->_fp, $str);
        if (strlen($str) !== $nb) throw new Quick_Fifo_Exception("$this->_filename: unable to save state");
    }

    protected function _openFile( $mode, $modeName ) {
        if (!file_exists($this->_filename)) @touch($this->_filename);
        $fp = @fopen($this->_filename, $mode);
        if (!$fp) throw new Quick_Fifo_Exception("$this->_filename: unable to open $modeName ($mode)");
        return $fp;
    }

    protected function _padHeaderString( $str ) {
        return str_pad($str, $this->_state['LEN'] - 1, ' ') . "\n";
    }
}
