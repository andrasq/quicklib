<?

/**
 * REST response that builds XML output.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Response_Xml
    extends Quick_Rest_Response_Http
{
    protected $_indentIncrement = '  ';
    protected $_xmlPreamble = "";       // "<?xml version=\"1.0\" encoding=\"UTF-8\" ? >\n";
    protected $_xmlContainerStart = "<xml>\n", $_xmlContainerEnd = "</xml>\n";

    protected function _emitValues( ) {
        // null values suppresses values output
        if ($this->_values === null) return;
        $str = $this->_getXmlList($this->_values);
        echo $str;
    }


    protected function _getXmlList( & $values ) {
        ob_start();
        echo $this->_xmlPreamble;
        echo $this->_xmlContainerStart;
        foreach ($values as $name => & $val) {
            $this->_emitXmlValue($this->_indentIncrement, $name, $val, $name);
        }
        echo $this->_xmlContainerEnd;
        return ob_get_clean();
    }

    protected function _emitXmlValue( $indent, $fieldName, & $value, $collectionName ) {
        $fieldName = htmlentities($fieldName);
        if ($value === null || is_scalar($value) || is_object($value) && method_exists($value, '__toString')) {
            // control chars are not valid xml but utf8_encode leaves them; strip them here
            $value = preg_replace('/[\x01-\x08\x0B-\x0C\x0E-\x1F]/', '', $value);
            $val = htmlentities(utf8_encode($value));
            echo "$indent<$fieldName>$val</$fieldName>\n";
        }
        elseif (is_array($value)) {
            echo $indent . "<$fieldName>\n";
            if (isset($this->_collections[$collectionName])) {
                $itemName = $this->_collections[$collectionName];
                foreach ($value as $k => & $val) {
                    // in a collection with named items numeric indexes are output with the shared name
                    if (is_integer($k))
                        $this->_emitXmlValue($indent.$this->_indentIncrement, $itemName, $val, "$collectionName>>$itemName");
                    else
                        $this->_emitXmlValue($indent.$this->_indentIncrement, $k, $val, "$collectionName>>$k");
                }
            }
            else {
                foreach ($value as $k => & $val) {
                    $this->_emitXmlValue($indent.$this->_indentIncrement, $k, $val, "$collectionName>>$k");
                }
            }
            echo $indent . "</$fieldName>\n";
        }
        elseif (is_object($value)) {
            // objects emit each field
            echo $indent . "<$fieldName>\n";
            foreach ($value as $k => & $val) {
                $this->_emitXmlValue($indent.$this->_indentIncrement, $k, $val, "$collectionName>>$k");
            }
            echo $indent . "</$fieldName>\n";
        }
        else {
            // unknown values brute-force convert to strings
            $val = (string) $value;
            $this->_emitXmlValue($indent, $fieldName, $val, $collectionName);
        }
    }
}
