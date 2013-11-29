<?

/**
 * Sqlite select results.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Sqlite_SelectResult
    implements Quick_Db_SelectResult, Quick_Db_SelectResultFactory
{
    protected $_rs;
    protected $_fetchMethod = 'fetchHash';

    public static function create( $rs ) {
        return new self($rs);
    }

    public function __construct( $rs ) {
        $this->_rs = $rs;
    }
    public function __destruct( ) {
        // ? no need to free sqlite results?
    }
    public function getResultInfo( ) {
        return new Quick_Db_Sqlite_ResultInfo($this->_rs, new Quick_Db_Sqlite_Adapter(null));
    }
    public function asList( ) {
        return new Quick_Db_Sqlite_SelectFetcher($this->_rs, 'fetchList', $this);
    }
    public function asHash( ) {
        return new Quick_Db_Sqlite_SelectFetcher($this->_rs, 'fetchHash', $this);
    }
    public function asColumn( $idx = 0 ) {
        $this->_fetchMethod = is_integer($idx) ? 'fetchListColumn' : 'fetchHashColumn';
        return new Quick_Db_Sqlite_SelectFetcher($this->_rs, $this->_fetchMethod, $this, $idx);
    }
    public function asObject( $class = 'Quick_Db_Object' ) {
        if (!is_subclass_of($class, 'Quick_Db_Object') && $class !== 'Quick_Db_Object')
            throw new Quick_Db_Exception("$class: not a Quick_Db_Object");
        return new Quick_Db_Sqlite_SelectFetcher($this->_rs, 'fetchObject', $this, $class);
    }
}
