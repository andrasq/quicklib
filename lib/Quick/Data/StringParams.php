<?

/**
 * Templated substring extractor.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * Given a string composed of substrings joined by a separator and a template
 * string with parameter names to extract, this code will match the template
 * to the string and will return the substrings corresponding to the named
 * parameters.
 *
 * Returns false if the template does not match the string, an empty array
 * if the string matched but the template contained no named parameters, or
 * a name => value hash of the named parameters matched.
 *
 * Examples:
 *    $string = "/root/x/index.php/1/2/3"
 *    $template = "/root/{base}/index.php/{a}/{b}"
 *    $separator = "/"
 *    $namedelimiters = "{}"
 *    => array('base' => 'x', 'a' => '1','b' => '2').
 *
 *    $string = "pubsub.entityUpdates.homepage.hitcount.1.batched"
 *    $template = "pubsub.entityUpdates.{site}.{entity}.{count}"
 *    $separator = "."
 *    $namedelimiters = "{}"
 *    => array('site' => 'homepage', 'entity' => 'hitcount', 'count' => 1)
 *
 *    $string = "/home/andras/Documents/file.txt"
 *    $template = "/home/%%USER%%/%%DIR%%/%%FILE%%"
 *    $separator = "/"
 *    $namedelimiters = "%%"
 *    => array('USER' => 'andras', 'DIR' => 'Documents', 'FILE' => 'file.txt')
 *
 * Note: this code is intended for extacting parts of strings, and would
 * be quite slow for testing whether the template and string match.  The
 * caller should use a more efficient means to check the match before
 * extracting values, eg a hash-based lookup or regex-based comparision.
 */

class Quick_Data_StringParams
{
    protected $_separator = "/", $_delimiters = "{}", $_template, $_templateparts;

    public function setSeparator( $separator ) {
        $this->_separator = (string) $separator;
        return $this;
    }

    public function setNameDelimiters( $delimiters ) {
        $this->_delimiters = (string) $delimiters;
        if ($this->_delimiters <= "") $this->_delimiters = " ";
        return $this;
    }

    public function setTemplate( $template ) {
        $this->_template = (string) $template;
        $this->_templateparts = null;
        return $this;
    }

    public function withSeparator( $separator ) {
        $copy = clone $this;
        return $copy->setSeparator($separator);
    }

    public function getParams( $string ) {
        $params = array();

        if (!isset($this->_templateparts))
            $this->_templateparts = $this->_splitString($this->_separator, $this->_template);
        $templateparts = & $this->_templateparts;
        $stringparts = $this->_splitString($this->_separator, $string);

        /*
         * Check the template against the string part by part.
         * Each template part must match the corresponding substring
         * or must extract a named parameter, else the no match possible.
         */
        foreach ($templateparts as $ix => $part) {
            if (!isset($stringparts[$ix])) {
                // template has more parts than the string, no match
                return false;
            }
            elseif ($part !== "" && $part[0] === $this->_delimiters[0]) {
                // substring matches template named parameter, save value
                $params[trim($part, $this->_delimiters)] = $stringparts[$ix];
            }
            elseif ($part !== $stringparts[$ix]) {
                // substring does not match template, no match
                return false;
            }
            // else
                // template part matches corresponding substring, continue
        }

        return $params;
    }

    protected function _splitString( $separator, $string ) {
        if ($separator > "")
            return explode($separator, $string);
        else
            return $string;
    }
}
