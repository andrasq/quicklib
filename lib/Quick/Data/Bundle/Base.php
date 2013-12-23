<?

/**
 * Data store of hierarchical data with a unified naming scheme.
 *
 * The hierarchy is specified with separators between the parts, typically "."
 * E.g., 'a.b.c' = 1, 'a.b.d' = 2 builds containers {a={b={c=1,d=2}}}.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * In addition to behaving like a store, the bundle can build and query
 * hierarchies, and can share its values with the caller or vice versa.
 */

class Quick_Data_Bundle_Base
    implements Quick_Store, Quick_Data_Bundle
{
    protected $_values = array();
    protected $_separator;

    public function __construct( Array $values = null ) {
        if ($values !== null) $this->_values = $values;
    }

    public function withSeparator( $sep ) {
        // note: undefined what happens if separator is an empty string
        $copy = clone $this;
        $copy->_values = & $this->_values;
        $copy->_separator = (string) $sep;
        return $copy;
    }

    public function & shareValues( Array & $values = null ) {
        if ($values !== null) $this->_values = & $values;
        return $this->_values;
    }

    public function get( $name ) {
        return $this->_findReference($name);
    }

    public function set( $name, $value ) {
        $ref = & $this->_findReference($name, $create = true);
        $ref = $value;
        return true;
    }

    public function push( $name, $value ) {
        $ref = & $this->_findReference($name, $create = true);
        $ref[] = $value;
        return true;
    }

    public function delete( $name ) {
        $ref = & $this->_findReference($name);
        if ($ref === false) return false;

        // mark node as empty with FALSE, which is not a valid data value
        $ref = false;
        // then unset the node and all empty arrays that contained the node
        $this->_unsetEmptyNodes($this->_values, ($this->_separator === null ? array($name) : explode($this->_separator, $name)));
        return true;
    }

    public function __toString( ) {
        return print_r($this, true);
    }


    protected function & _findReference( $name, $create = false ) {
        $no = false;
        if ($this->_separator === null) {
            if (isset($this->_values[$name]) || $create) return $this->_values[$name];
            else return $no;
        }

        $array = & $this->_values;
        foreach (explode($this->_separator, $name) as $part) {
            if (!isset($array[$part])) {
                if (!$create) return $no;
                if (!is_array($array)) {
                    if ($create) $array = array();
                    else return $no;
                }
                $array[$part] = array();
            }
            $array = & $array[$part];
        }
        return $array;
    }

    protected function _unsetEmptyNodes( Array & $array, Array $parts ) {
        $part = array_shift($parts);
        if ($parts) $this->_unsetEmptyNodes($array[$part], $parts);
        if (is_array($array[$part]) && empty($array[$part]) || $array[$part] === false)
            unset($array[$part]);
    }
}
