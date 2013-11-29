<?

/**
 * Add Fetchable semantics to an array.  Handy for passing synthetic data to tests.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Fetchable_Array
    implements Quick_Db_Fetchable
{
    public function __construct( Array $array ) {
        $this->_array = array_values($array);
        $this->_count = count($array);
        $this->_key = 0;
    }

    public function reset( ) {
        $this->_key = 0;
    }

    public function fetch( ) {
        return $this->_key < $this->_count ? $this->_array[$this->_key++] : false;
    }
}
