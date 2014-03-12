<?

/**
 * Store name=value pairs in a comma-separated string.
 * Access elements with get/set, retrieve the whole string by a string cast,
 * or cast to array to return the items as a hash.
 *
 * Names must not contain commas or equal signs.  This implementation sweeps
 * all chars not the name or the separator as part of the value.
 *
 * Copyright (C) 2013-2014 Andras Radics
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
        // http_build_query is much faster, but always urlencodes values
        // return http_build_query(get_object_vars($this), '_', $this->_getSeparator());
        foreach ($this as $name => $value) {
            //if (strpos($value, ',') !== false) $value = str_replace(',', '\\,', $value);
            $namevals[] = "$name=$value";
        }
        return isset($namevals) ? implode(',', $namevals) : "";
    }


    protected function _getSeparator( ) {
        return ",";
    }

    protected function _fromString( $string, $separator ) {
        $sepchar = $this->_getSeparator();

        // split on (,)(name)=, leaving an empty string fragment "" at the start
        $parts = preg_split("/(^|[$sepchar])([^=$sepchar]+)=/", $string, null, PREG_SPLIT_DELIM_CAPTURE);

        $n = count($parts);
        for ($i=2; $i<$n; $i+=3) {
            $name = $parts[$i];
            $value = $parts[$i+1];
            $this->$name = $value;
        }
        return;
    }
}
