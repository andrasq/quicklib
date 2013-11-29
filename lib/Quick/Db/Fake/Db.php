<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Fake_Db
    extends Quick_Db_Base_Db
    implements Quick_Db, Quick_Db_Engine
{
    protected $_queries = array();
    protected $_results = array();

    public function __construct( ) {
        parent::__construct(null, $this->_createAdapter(null));
    }

    protected function _createSelectResult( $rs ) {
        return new Quick_Db_Fake_SelectResult($rs);
    }

    protected function _createAdapter( $link ) {
        return new Quick_Db_Fake_Adapter($link);
    }

    public function setResult( $res ) {
        if (!is_bool($res) && !is_array($res))
            throw new Quick_Db_Exception("setResult:  expects a boolean or an array of return values to fetch");
        $this->_results[] = $res;
        return $this;
    }

    public function getQueries( ) {
        return $this->_queries;
    }

    public function query( $sql, $tag = '' ) {
        if ($tag !== '') $sql = "$sql /* $tag */";
        $this->_queries[] = $sql;
        $ret = $this->_results ? array_shift($this->_results) : true;
        if ($ret === false) throw new Quick_Db_Exception("sql error: -1: FakeError: $sql");
        return $ret === true ? true : $this->_createSelectResult($ret);
    }
}
