<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Fake_SelectFetcher
    implements Quick_Db_SelectFetcher
{
    protected $_rs, $_fetched = array();

    public function __construct( Array $rs ) {
        $this->_rs = $rs;
    }

    public function fetch( ) {
        return $this->_rs ? $this->_fetched[] = array_shift($this->_rs) : false;
    }

    public function reset( ) {
        $this->_rs = array_merge($this->_fetched, $this->_rs);
        $this->_fetched = array();
        return true;
    }

    public function fetchAll( $limit = null ) {
        if (!$this->_rs) return array();
        $ret = $this->_rs;
        $this->_fetched = array_merge($this->_fetched, $this->_rs);
        $this->_rs = array();
        return $ret;
    }

    public function getIterator( ) {
        return new Quick_Db_Iterator($this);
    }
}
