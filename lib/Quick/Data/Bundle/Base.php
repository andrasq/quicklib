<?

/**
 * Data store of hierarchical data with a unified naming scheme.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * The hierarchy is specified with separators between the parts, typically "."
 * E.g., 'a.b.c' = 1, 'a.b.d' = 2 builds containers {a={b={c=1,d=2}}}.
 *
 * In addition to behaving like a store, the bundle can build and query
 * hierarchies, and can share its values with the caller or vice versa.
 */

class Quick_Data_Bundle_Base
    implements Quick_Store, Quick_Data_Bundle
{
    protected $_values = array();
    protected $_separator;
    protected $_xmlheader = "<?xml version=\"1.0\"?>\n";
    protected $_itemnames = array();

    public function __construct( Array $values = null ) {
        if ($values !== null) $this->_values = $values;
    }

    public function withSeparator( $sep ) {
        // note: undefined what happens if separator is an empty string
        $copy = clone $this;
        $copy->_values = & $this->_values;
        $copy->_itemnames = & $this->_itemnames;
        $copy->_separator = (string) $sep;
        return $copy;
    }

    public function & shareValues( Array & $values = null ) {
        if ($values !== null) $this->_values = & $values;
        return $this->_values;
    }

    // set the xml header comment, required if the xml does not have the version/encoding attributes
    // only xml looks at the xml header, but the method is here for seamless xml/json interoperability
    public function setXmlHeader( $header ) {
        $this->_xmlheader = $header;
        return $this;
    }

    // the list a.b.c = [1,2] is normally output as <a><b><c>1</c><c>2</c></b></a>
    // setListItemName('a.b.c', 'x') will output it instead as <a><b><c><x>1</x><x>2</x></c></b></a>
    // only xml looks at itemnames, but the method is here for seamless xml/json interoperability
    public function setListItemName( $listname, $itemname ) {
        // xml field names will never contain '>>', use that as the internal separator
        $separator = $this->_separator ? $this->_separator : '>>';
        // the item name hierarchy is stacked onto the empty string, hence ">>" at the start
        $this->_itemnames[">>".implode('>>', explode($separator, $listname))] = $itemname;
        return $this;
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
