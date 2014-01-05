<?

/**
 * Mysql select results.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-21 - AR.
 */

class Quick_Db_Mysql_SelectResult
    implements Quick_Db_SelectResult, Quick_Db_SelectResultFactory
{
    protected $_rs;

    public static function create( $rs ) {
        /**
        if ($rs === false) throw new Quick_Db_Exception("sql error");
        elseif ($rs === true) return true;
        **/
        return new self($rs);
    }

    public function __construct( $mysql_rs ) {
        $this->_rs = $mysql_rs;
    }
    public function __destruct( ) {
        // test before freeing, unit tests use non-resource rs
        if ($this->_rs > 0) mysql_free_result($this->_rs);
    }
    public function getResultInfo( ) {
        return new Quick_Db_Mysql_ResultInfo($this->_rs, new Quick_Db_Mysql_Adapter(null));
    }
    public function asList( ) {
        return new Quick_Db_Mysql_SelectFetcher($this->_rs, 'fetchList', $this);
    }
    public function asHash( ) {
        return new Quick_Db_Mysql_SelectFetcher($this->_rs, 'fetchHash', $this);
    }
    public function asColumn( $idx = 0 ) {
        return new Quick_Db_Mysql_SelectFetcher(
            $this->_rs, (is_integer($idx) ? 'fetchListColumn' : 'fetchHashColumn'), $this, $idx);
    }
    public function asObject( $objectSpecifier /*, $objectParams = null*/ ) {
        return new Quick_Db_Mysql_SelectFetcher($this->_rs, 'asObject', $this, $objectSpecifier);
    }
}
