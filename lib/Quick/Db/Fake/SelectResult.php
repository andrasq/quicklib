<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Fake_SelectResult
    implements Quick_Db_SelectResult, Quick_Db_SelectResultFactory
{
    protected $_rs;

    public function __construct( $rs ) {
        $this->_rs = $rs;
    }

    public static function create( $rs ) {
        return new self($rs);
    }

    public function getResultInfo( ) {
        return new Quick_Db_Fake_ResultInfo($this->_rs, new Quick_Db_Mysql_Adapter(null));
    }

    public function asList( ) {
        return new Quick_Db_Fake_SelectFetcher($this->_rs);
    }

    public function asHash( ) {
        return new Quick_Db_Fake_SelectFetcher($this->_rs);
    }

    public function asColumn( $columnIndex = 0 ) {
        return new Quick_Db_Fake_SelectFetcher($this->_rs);
    }

    public function asObject( $class = 'Quick_Db_Object' ) {
        return new Quick_Db_Fake_SelectFetcher($this->_rs);
    }

}
