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
    protected $_nl = "\n";              // set to "" for terse version without newlines
    protected $_indent = "  ";          // set to "" for terse version without indentation

    public function __toString( ) {
        ob_start();
        $n = count($this->_values);
        if ($n === 1 && is_array(current($this->_values))) {
            // single array value is the envelope with the payload
            $this->_emitXmlHash($this->_values, "", "");
        }
        elseif ($n === 0) {
            echo "<xml></xml>", $this->_nl;
        }
        else {
            // else supply the envelope to not generate invalid xml
            // valid xml contains exactly one element (the envelope), which can contain others
            echo "<xml>", $this->_nl;
            $this->_emitXmlHash($this->_values, $this->_indent, "", "");
            echo "</xml>", $this->_nl;
        }
        return $this->_xmlheader . $this->_cleanXmlString(ob_get_clean());
    }


    protected function _emitXmlHash( Array $values, $indent, $currentitem ) {
        foreach ($values as $k => $v) {
            if (is_array($v))
                if (is_integer(key($v))) {
                    // assume that a numeric index only occurs for numerically indexed arrays
                    // lists L can be in-lined into the current collection as items <L></L>, or
                    // can be output as a list L with items <N></N> if the itemname N was defined
                    if (isset($this->_itemnames["$currentitem>>$k"])) {
                        echo $indent, "<$k>", $this->_nl;
                        $this->_emitXmlList($v, $indent . $this->_indent, $this->_itemnames["$currentitem>>$k"]);
                        echo $indent, $this->_closeTag($k), $this->_nl;
                    }
                    else {
                        $this->_emitXmlList($v, $indent, $k);
                    }
                }
                elseif (!$v) {
                    // note: empty list must *not* contain a newline, else is parsed as non-empty
                    echo "<$k>", $this->_closeTag($k), $this->_nl;
                }
                else {
                    echo $indent, "<$k>", $this->_nl;
                    $this->_emitXmlHash($v, $indent . $this->_indent, "$currentitem>>$k");
                    echo $indent, $this->_closeTag($k), $this->_nl;
                }
            else
                echo $indent, "<$k>", htmlspecialchars($v), $this->_closeTag($k), $this->_nl;
        }
    }

    protected function _emitXmlList( Array $values, $indent, $itemname ) {
        foreach ($values as $v)
            // note: lists must be flat, cannot have hierchical data
            echo $indent, "<$itemname>", htmlspecialchars($v), $this->_closeTag($itemname), $this->_nl;
    }

    // clean the xml of invalid characters that would break decoding
    protected function _cleanXmlString( $str ) {
        // the values have already been html escaped, protect from double-converting
        $str = str_replace(array("%", "<", ">", "&", ";"), array("%25", "%3C", "%3E", "%26", "%3B"), $str);

        // XML does not allow control chars; see also Rest_Response_Xml
        // control chars are not valid xml but utf8_encode leaves them; strip them here; \0 also breaks
        $str = preg_replace('/[\x00\x01-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $str);

        // do not encode quotes, they escape tag attribute values
        $str = htmlentities(utf8_encode($str), 0, 'UTF-8');

        // restore the protected chars and return
        return urldecode($str);
    }

    protected function _closeTag( $tag ) {
        // the closing tag stops at the first space, to allow eg <tag property1=1 property2=2></tag>
        return "</" . (($pos = strpos($tag, ' ')) ? substr($tag, 0, $pos) : $tag) . ">";
    }
}
