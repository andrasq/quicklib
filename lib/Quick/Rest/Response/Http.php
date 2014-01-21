<?

/**
 * Gather the output for the REST call response.  Http version just prints the values.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Response_Http
    implements Quick_Rest_Response
{
    protected $_cli = 'http';
    protected $_httpId = "HTTP/1.1";
    protected $_statusCode = 200, $_statusMessage = "OK", $_headers = array(
        'Content-Type' => 'Content-Type: text/plain',
    );
    protected $_content = false;
    protected $_contentFile = false;
    protected $_values = array();
    protected $_collections = array();
    protected $_isOutput = false;               // set once output has been started, for appendContent

    public function setCli( $yesno ) {
        $this->_cli = $yesno ? 'cli' : 'http';
    }

    public function setHttpId( $version ) {
        $this->_httpId = $version;
    }

    public function setHttpHeaders( Array $headerStrings ) {
        $this->_headers = $headerStrings;
    }

    public function setHttpHeader( $name, $value ) {
        $this->_headers[$name] = "$name: $value";
    }

    public function appendHttpHeader( $name, $value ) {
        $this->_headers[] = "$name: $value";
    }

    public function setStatus( $status, $message = "" ) {
        $this->_statusCode = $status;
        $this->_statusMessage = $message;
        // to provide an http status code without specifying HTTP 1.0 or 1.1:
        // header("Status: 200", true, 200);
        // note: the status code *must* be a know http status code,
        //       else php substitutes 500 Internal Error
    }

    public function getStatus( ) {
        return $this->_statusCode;
    }

    public function getMessage( ) {
        return $this->_statusMessage;
    }

    public function setHttpRedirect( $url ) {
        $this->setHttpHeader('Location', $url);
    }

    // content is a string that is output unmodified
    public function setContent( $str ) {
        $this->_content = $str;
    }

    public function appendContent( $str ) {
        // if output has already been started, emit the appended value, else gather
        // note: do not gather once output started to support arbitrarily large streaming responses
        if ($this->_isOutput) $this->_emitExtra($str);
        else $this->_content .= $str;
    }

    public function setContentFile( $filename ) {
        $this->_contentFile = $filename;
    }

    // check whether content has been set
    public function hasContent( ) {
        // empty string "" is valid content, test for unset
        return ($this->_content !== false && $this->_content !== null) || $this->_contentFile > "";
    }

    public function hasValues( ) {
        return (bool)$this->_values;
    }

    // set the field to value.  Unnamed fields are appended to the list.
    public function setValue( $name, $value, $separator = null ) {
        if ($separator === null)
            if ("$name" !== '') $this->_values[$name] = $value;
            else $this->_values[] = $value;
        else
            $this->_setCollectionValue($this->_values, $name, $value, $separator);
    }

    public function setValues( Array $namevals ) {
        foreach ($namevals as $k => $v) {
            $this->_values[$k] = $v;
        }
    }

    public function getValues( ) {
        return $this->_values;
    }

    public function getValue( $name ) {
        // FIXME: also return values from collection
        return isset($this->_values[$name]) ? $this->_values[$name] : null;
    }

    // do not putput any values, show just the setContent() or setContentFile() text
    public function unsetValues( ) {
        $this->_values = null;
    }

    // collections are named with itemname to allow for XML lists of the form
    // <listname><itemname>item1</itemname><itemname>item2</itemname></listname>
    // The collection can be at a nested level, if separator is set it splits up listname
    public function nameCollection( $listname, $itemname, $separator = null ) {
        // xml field names will never contain '>>', use that as the internal separator
        if ($separator === null) $separator = '>>';
        $this->_collections[implode('>>', explode($separator, $listname))] = $itemname;
    }

    public function appendCollection( $listname, $value, $separator = null ) {
        if ($separator === null) $this->_values[$listname][] = $value;
        else $this->setValue($listname.$separator, $value, $separator);
    }

    protected function _setCollectionValue( & $collection, $name, $value, $separator ) {
        if ($pos = strpos($name, $separator)) {
            $fieldname = substr($name, 0, $pos);
            if (!isset($collection[$fieldname])) $collection[$fieldname] = array();
            $this->_setCollectionValue(
                $collection[$fieldname], (string)substr($name, $pos+strlen($separator)), $value, $separator
            );
        }
        else {
            // if the name is empty, append the value onto the collection list
            if ($name === '') $collection[] = $value;
            else $collection[$name] = $value;
        }
    }

    public function emitResponse( ) {
        $this->_emitHeaders();
        $this->_emitContent();
        $this->_emitValues();
        $this->_isOutput = true;
        return $this;
    }

    public function getResponse( $includeHeaders = false ) {
        ob_start();
        if ($includeHeaders) $this->_emitHeaders();
        $this->_emitContent();
        $this->_emitValues();
        return ob_get_clean();
    }

    public function __toString( ) {
        return $this->getResponse();
    }

    protected function _emitHeaders( ) {
        if ($this->_cli !== 'cli') {
            if (isset($this->_statusCode)) {
                // header('HTTP/') will set the http header string to any status and message
                // header('Status:') will also set the http header string, but
                // only if not otherwise set, and enforces the standard status messages;
                // for non-standard status codes it sets the http header to 500 Internal Server Error.
                // NOTE: php replaces an empty message with the standard message for the code,
                // or with 500 Internal Server Error if not standard
                header("$this->_httpId $this->_statusCode $this->_statusMessage");
                header("Status: $this->_statusCode $this->_statusMessage", true, $this->_statusCode);
            }
            // faster to emit singly with header()
            foreach ($this->_headers as $hdr)
                header($hdr);
        }
        else {
            if (isset($this->_statusCode)) {
                echo "$this->_httpId $this->_statusCode $this->_statusMessage\n" .
                     "Status: $this->_statusCode\n";
            }
            // for cli, faster to emit all at once
            echo implode("\n", $this->_headers) . "\n" .
                 "\n";
        }
    }

    protected function _emitContent( ) {
        if ($this->hasContent()) {
            if ($this->_content !== false || $this->_content !== null) {
                echo $this->_content;
                $last = substr($this->_content, -1);
            }
            if ($this->_contentFile > "") {
                $last = $this->_emitFile($this->_contentFile);
            }
            if ($this->_values && $last !== "\n")
                echo "\n";
        }
    }

    protected function _emitExtra( $str ) {
        echo $str;
    }

    protected function _emitValues( ) {
        if ($this->_values === null) return;
        if ($this->_values) echo print_r($this->_values, true);
    }

    protected function _emitFile( $filename ) {
        $fp = @fopen($filename, "r");
        if (!$fp) return;
        while (($buf = fread($fp, 20000)) > "") {
            echo $buf;
            $lastChar = substr($buf, -1);
        }
        return $lastChar;
    }
}
