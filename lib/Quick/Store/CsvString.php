<?

/**
 * Store name=value pairs in a comma-separated string.
 * Access elements with get/set, retrieve the whole string by a string cast,
 * or cast to array to return the items as a hash.  The string cast urlencodes
 * commas embedded in values; names must not contain commas or equal signs.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Store_CsvString
    implements Quick_Store
{
    public function __construct( $data ) {
        if (is_array($data) || is_object($data))
            foreach ($data as $k => $v) $this->$k = $v;
        else
            $this->_fromString($data, $this->_getSeparator());
    }

    public function set( $name, $value ) {
        $this->$name = $value;
        return true;
    }

    public function get( $name ) {
        return isset($this->$name) ? $this->$name : false;
    }

    public function add( $name, $value ) {
        if (isset($this->$name)) return false;
        $this->$name = $value;
        return true;
    }

    public function delete( $name ) {
        if (isset($this->$name)) {
            unset($this->$name);
            return true;
        }
        return false;
    }

    // cast to string to recover the name=value,name2=value2,... string
    // values must not contain commas, but equal signs are ok
    public function __toString( ) {
        foreach ($this as $name => $value) {
            if (strpos($value, ',') !== false) $value = str_replace(',', '%2C', $value);
            $namevals[] = "$name=$value";
        }
        return isset($namevals) ? implode(',', $namevals) : "";
    }


    protected function _getSeparator( ) {
        return ",";
    }

    protected function _fromString( $string, $separator ) {
        foreach (explode($separator, $string) as $nameval) {
            if (!$nameval) continue;
            if (($pos = strpos($nameval, '=')) === false) {
                $this->$nameval = '';
            }
            else {
                $name = substr($nameval, 0, $pos);
                if ($name > '')
                    $this->$name = substr($nameval, $pos+1);
                else
                    throw new Quick_Store_Exception("$nameval: invalid csv string nameval, missing name");
            }
        }
    }
}
