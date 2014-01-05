<?

/**
 * Fetch results from a mysql_result resource.
 * This is on the critical path, so makes mysql_* calls directly.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-21 - AR.
 */

class Quick_Db_Mysql_SelectFetcher
    extends Quick_Db_Base_SelectFetcher
    implements Quick_Db_SelectFetcher
{
    protected $_fetch_assoc_function = 'mysql_fetch_assoc';
    protected $_fetch_row_function = 'mysql_fetch_row';

    public function reset( ) {
        @mysql_data_seek($this->_rs, 0);
    }

    protected function _numRows( $rs ) {
        return mysql_num_rows($rs);
    }

    public function fetchAll( $limit = null ) {
        $nrows = $this->_numRows($this->_rs);
        $limit = $limit === null ? $nrows : min($limit, $nrows);
        if ($limit <= 0) return array();
        $ret = array();
        if ($this->_fetchMethod === 'fetchHash') {
            // 9.25 10k cons returned / sec as hash if no false needs to be popped off the end (vs PDO 9.3 10k)
            // 59 10k cons 3 fields returned / sec as hash (vs 71 PDO built-in fetchAll), 62.2 as list (vs 75 PDO)
            $ret = array_map($this->_fetch_assoc_function, array_fill(0, $limit, $this->_rs));
        }
        elseif ($this->_fetchMethod === 'fetchList') {
            // hashes and lists are faster fetched with array_map
            // 22% faster to fetch all items w/ array_map than to accumulate singly
            $ret = array_map($this->_fetch_row_function, array_fill(0, $limit, $this->_rs));
        }
        else {
            // 6.8 10k cons returned / sec as hash by calling fetchMethod (15% faster w/o reference return!!)
            // columns and objects are faster fetched singly
            $fetchMethod = $this->_fetchMethod;
            for ($k=0; $k<$limit; ++$k) $ret[] = $this->$fetchMethod();
        }
        while (end($ret) === false && $ret) array_pop($ret);
        return $ret;
    }

    protected function fetchList( ) {
        return mysql_fetch_row($this->_rs);
    }
    protected function fetchHash( ) {
        return mysql_fetch_assoc($this->_rs);
    }
    protected function fetchListColumn( ) {
        return ($r = mysql_fetch_row($this->_rs)) ? $r[$this->_columnIndex] : false;
    }
    protected function fetchHashColumn( ) {
        return ($r = mysql_fetch_assoc($this->_rs)) ? $r[$this->_columnIndex] : false;
    }
}
