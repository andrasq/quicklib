<?

/**
 * Http protocol REST reply.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Reply_Http
    implements Quick_Rest_Reply
{
    protected $_reply = "";
    protected $_protocol, $_status = null, $_message = null;
    protected $_header = null, $_body = null;
    protected $_headerLen, $_bodyStart;

    public function __construct( $str = "" ) {
        if (!is_string($str)) throw new Quick_Rest_Exception("expected string reply");
        $this->_reply = $str;
    }

    public static function createFromString( $str ) {
        $reply = new self($str);
        return $reply;
    }

    public function setReply( $reply, $status = null, $message = null ) {
        if (!is_string($reply)) throw new Quick_Rest_Exception("expected string reply");
        $this->_reply = $reply;
        if (isset($status)) $this->_status = $status;
        if (isset($message)) $this->_message = $message;
        return $this;
    }

    public function getReply( ) {
        return $this->_reply;
    }

    public function getStatus( ) {
        if ($this->_status === null) {
            if ($this->_headerLen === null)
                $this->_splitHeaderBody($this->_reply);

            // HTTP status is returned on the first line, the "200" in "HTTP/1.1 200 OK".
            $statusline = substr($this->_reply, 0, strcspn($this->_reply, "\r\n"));
            list($this->_protocol, $this->_status, $this->_message) = @explode(" ", $statusline, 3);

            // Status can also be returned on a separate line "Status: 200".
            // The latter is user-set thus is more definitive; use it if available.
            if (($pos = strpos($this->_reply, "\nStatus:")) && ($pos < $this->_headerLen-9)) {
                $status = (int)substr($this->_reply, $pos+9);
                if ($status > 0) $this->_status = $status;
            }
        }
        return $this->_status;
    }

    public function getMessage( ) {
        if ($this->_message === null)
            $this->getStatus();
        return $this->_message;
    }

    public function getHeaders( ) {
        if ($this->_headerLen === null)
            $this->_splitHeaderBody($this->_reply);
        foreach (explode("\n", substr($this->_reply, 0, $this->_headerLen)) as $line) {
            // NOTE: @ does not suppress the "Undefined offset:  1" notice in unit tests ??
            // list($name, $value) = @explode(':', $line, 2);
            // $hdr[$name] = trim($value, "\r\n \t");
            $pos = strcspn($line, ':');
            $hdr[trim(substr($line, 0, $pos))] = trim(substr($line, $pos+1));
        }
        return $hdr;
    }

    public function getBody( ) {
        if ($this->_bodyStart === null)
            $this->_splitHeaderBody($this->_reply);
        // return $this->_body;
        return substr($this->_reply, $this->_bodyStart);
    }

    protected function _splitHeaderBody( $reply ) {
        static $newlines = array("\r\n\r\n", "\n\r\n", "\r\n\n", "\n\n");
        $blanklines = array();

        foreach ($newlines as $blankline)
            if (($pos = strpos($reply, $blankline)) !== false) $blanklines[] = $pos;
        if ($blanklines) {
            $this->_headerLen = isset($blanklines) ? min($blanklines) : strlen($reply);
            $this->_bodyStart = $this->_headerLen + strspn($reply, "\r\n", $this->_headerLen);
        }
        else {
            $this->_headerLen = $this->_bodyStart = 0;
        }
        return;
    }
}
