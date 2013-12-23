<?

/**
 * Bundle that formats itself as readable XML.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * Note: the XML formatting must not include double-quote chars.
 */

class Quick_Data_Bundle_Xml
    extends Quick_Data_Bundle_Base
{
    protected $_header = "<?xml version=\"1.0\"?>\n";
    protected $_nl = "\n";              // set to "" for terse version without newlines
    protected $_indent = "  ";          // set to "" for terse version without indentation

    public function setHeader( $header ) {
        $this->_header = $header;
    }

    public function __toString( ) {
        ob_start();
        $n = count($this->_values);
        if ($n === 1 && is_array(current($this->_values))) {
            // single array value is the envelope with the payload
            $this->_emitXmlHash($this->_values, "");
        }
        elseif ($n === 0) {
            echo "<xml></xml>", $this->_nl;
        }
        else {
            // else supply the envelope to not generate invalid xml
            // valid xml contains exactly one element (the envelope), which can contain others
            echo "<xml>", $this->_nl;
            $this->_emitXmlHash($this->_values, $this->_indent);
            echo "</xml>", $this->_nl;
        }
        return $this->_header . $this->_cleanXmlString(ob_get_clean());
    }


    protected function _emitXmlHash( Array $values, $indent ) {
        foreach ($values as $k => $v) {
            if (is_array($v))
                if (key($v) === 0) {
                    // assume that a numeric index only occurs for numerically indexed arrays
                    $this->_emitXmlList($v, $k, $indent);
                }
                elseif (!$v) {
                    // note: empty list must *not* contain a newline, else is parsed as non-empty
                    echo "<$k></$k>", $this->_nl;
                }
                else {
                    echo $indent, "<$k>", $this->_nl;
                    $this->_emitXmlHash($v, $indent . $this->_indent);
                    echo $indent, "</$k>", $this->_nl;
                }
            else
                echo $indent, "<$k>", htmlentities($v), "</$k>", $this->_nl;
        }
    }

    protected function _emitXmlList( Array $values, $tag, $indent ) {
        foreach ($values as $v)
            // note: lists must be flat, cannot have hierchical data
            echo $indent, "<$tag>", htmlentities($v), "</$tag>", $this->_nl;
    }

    protected function _cleanXmlString( $str ) {
        // XML does not allow control chars; see also Rest_Response_Xml
        // control chars are not valid xml but utf8_encode leaves them; strip them here; \0 also breaks
        $str = str_replace(array("%", "<", ">"), array("%25", "%3C", "%3E"), $str);
        $str = preg_replace('/[\x00\x01-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $str);
        $str = htmlentities(utf8_encode($str), ENT_QUOTES, 'UTF-8');
        return urldecode($str);
    }
}
