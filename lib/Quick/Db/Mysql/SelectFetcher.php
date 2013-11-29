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
    implements Quick_Db_SelectFetcher
{
    protected $_rs, $_fetchMethod, $_selectResult;
    protected $_columnIndex, $_objectClass, $_objectTemplate;
    protected $_fetch_assoc_function = 'mysql_fetch_assoc';
    protected $_fetch_row_function = 'mysql_fetch_row';

    public function __construct( $mysql_rs, $fetchMethod, Quick_Db_Mysql_SelectResult $selectResult, $arg = null ) {
        // we trust the caller to only specify methods that exist
        $this->_rs = $mysql_rs;
        $this->_fetchMethod = $fetchMethod;
        if ($arg !== null) {
            $this->_columnIndex = $arg;
            $this->_objectClass = $arg;
            if (is_object($arg))
                $this->_objectTemplate = $arg;
            //elseif (class_exists($arg))
            //    $this->_objectTemplate = new $arg();
        }

        // retain a reference to the result object so its destructor will free
        // the mysql result resource only when all fetchers have been destroyed
        $this->_selectResult = $selectResult;
    }

    public function fetch( ) {
        $m = $this->_fetchMethod;
        return $this->$m();
    }
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
        while (!end($ret) && $ret) array_pop($ret);
        return $ret;
    }
    public function getIterator( ) {
        return new Quick_Db_Iterator($this);
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
/**
    protected function fetchObject( ) {
        if ($r = mysql_fetch_assoc($this->_rs)) {
            $object = clone($this->_objectTemplate);
            foreach ($r as $k => $v) $object->$k = $v;
            return $object;
        }
        return false;
    }
    protected function fetchObjectWithBuilder( ) {
        return ($r = mysql_fetch_assoc($this->_rs)) ? $this->_objectBuilder->createFromHash($r) : false;
    }
    protected function fetchObjectByCallback( ) {
        return ($r = mysql_fetch_assoc($this->_rs)) ? $this->_callback($r) : false;
    }
**/
    protected function fetchObject( ) {
        if ($r = $this->fetchHash()) {
            $object = clone($this->_objectTemplate);
            foreach ($r as $k => $v) $object->$k = $v;
            return $object;
        }
        return false;
    }
    protected function fetchObjectWithBuilder( ) {
        return ($r = $this->fetchHash()) ? $this->_objectBuilder->createFromHash($r) : false;
    }
    protected function fetchObjectByCallback( ) {
        return ($r = $this->fetchHash()) ? $this->_callback($r) : false;
    }
}
