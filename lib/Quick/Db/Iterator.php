<?

/**
 * Iterator that iterates any fetchable.
 * The object can be both iterated and fetched from, even intermingled.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-17 - AR.
 */

class Quick_Db_Iterator
    implements Iterator, Quick_Db_Fetchable
{
    protected $_key = 0;
    protected $_current = false, $_isCached = false;

    public function __construct( Quick_Db_Fetchable $rs ) {
        $this->_rs = $rs;
    }

    public function reset( ) {
        $this->_rs->reset();
        $this->_key = 0;
    }
    public function fetch( ) {
        // return current and advance key
        ++$this->_key;
        if (!$this->_isCached) {
            return $this->_rs->fetch();
        }
        else {
            $this->_isCached = false;
            return $this->_current;
        }
    }

    public function rewind( ) {
        $this->_rs->reset();
        $this->_key = 0;
        $this->_isCached = false;
    }
    public function current( ) {
        if ($this->_isCached)
            return $this->_current;
        else {
            $this->_isCached = true;
            return $this->_current = $this->_rs->fetch();
        }
    }
    public function key( ) {
        return $this->_key;
    }
    public function next( ) {
        ++$this->_key;
        if (!$this->_isCached) $this->_rs->fetch();
        $this->_isCached = false;
    }
    public function valid( ) {
        return ($this->_isCached && $this->_current !== false || $this->current() !== false);
    }
}
