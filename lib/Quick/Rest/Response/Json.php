<?

/**
 * REST response that builds JSON output, optionally with HTTP headers.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Response_Json
    extends Quick_Rest_Response_Http
{
    protected $_header = array('Content-Type: application/json');

    protected function _emitValues( ) {
        // null values suppresses values output
        if ($this->_values === null) return;
        echo $json = json_encode($this->_values);
        if ($json === null)
            // json_encode breaks on non-utf8 strings; should fix the encoding and try again
            throw new Quick_Rest_Exception("unable to json_encode response: " . print_r($this->_values, true));
    }
}
