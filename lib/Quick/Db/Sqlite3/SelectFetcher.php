<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Sqlite3_SelectFetcher
    extends Quick_Db_Base_SelectFetcher
    implements Quick_Db_SelectFetcher
{

    public function fetch( ) {
        $m = $this->_fetchMethod;
        return $this->$m();
    }
    public function reset( ) {
        $rs->reset();
    }

    protected function _numRows( $rs ) {
        return 0;
    }

    public function fetchAll( $limit = null ) {
        if ($limit <= 0) return array();
        if ($limit === null && $this->_fetchMethod === 'fetchHash') {
            return $this->_rs->fetchArray(SQLITE3_ASSOC);
        }
        elseif ($limit === null && $this->_fetchMethod === 'fetchList') {
            return $this->_rs->fetchArray(SQLITE3_NUM);
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
        return $this->_rs->fetchArray(SQLITE3_NUM);
    }
    protected function fetchHash( ) {
        return $this->_rs->fetchArray(SQLITE3_ASSOC);
    }
    protected function fetchListColumn( ) {
        return ($r = $this->_rs->fetchArray(SQLITE3_NUM)) ? $r[$this->_columnIndex] : false;
    }
    protected function fetchHashColumn( ) {
        return ($r = $this->_rs->fetchArray(SQLITE3_ASSOC)) ? $r[$this->_columnIndex] : false;
    }
}
