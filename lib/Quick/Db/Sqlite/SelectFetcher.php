<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Sqlite_SelectFetcher
    extends Quick_Db_Base_SelectFetcher
    implements Quick_Db_SelectFetcher
{

    public function reset( ) {
        sqlite_rewind($this->_rs);
    }

    protected function _numRows( $rs ) {
        return sqlite_num_rows($rs);
    }

    public function fetchAll( $limit = null ) {
        if ($limit <= 0) return array();
        if ($limit === null && $this->_fetchMethod === 'fetchHash') {
            return sqlite_fetch_all($this->_rs, SQLITE_ASSOC);
        }
        elseif ($limit === null && $this->_fetchMethod === 'fetchList') {
            return sqlite_fetch_all($this->_rs, SQLITE_NUM);
        }
        else {
            // @NOTE: cannot fetch_all partial results, sqlite can only rewind not seek
            return parent::fetchAll($limit);
        }
    }
    public function getIterator( ) {
        return new Quick_Db_Iterator($this);
    }

    protected function fetchList( ) {
        return sqlite_fetch_array($this->_rs, SQLITE_NUM);
    }
    protected function fetchHash( ) {
        return sqlite_fetch_array($this->_rs, SQLITE_ASSOC);
    }
    protected function fetchListColumn( ) {
        return ($r = sqlite_fetch_array($this->_rs, SQLITE_NUM)) ? $r[$this->_columnIndex] : false;
    }
    protected function fetchHashColumn( ) {
        return ($r = sqlite_fetch_array($this->_rs, SQLITE_ASSOC)) ? $r[$this->_columnIndex] : false;
    }
}
