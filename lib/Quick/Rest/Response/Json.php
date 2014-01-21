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
        if ($json !== null) return;

        // json_encode breaks on non-utf8 strings; brute force encode everything and try again
        echo $json = json_encode(array_walk_recursive($this->_values, array($this, 'utf8_encode')));
        if ($json !== null) return;

        throw new Quick_Rest_Exception("unable to json_encode response: " . print_r($this->_values, true));
    }
}
