<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Sqlite3_SelectFetcher
    implements Quick_Db_SelectFetcher
{
    protected $_rs, $_fetchMethod, $_selectResult;
    protected $_columnIndex, $_objectClass, $_objectTemplate;
    protected $_fetch_assoc_function = 'mysql_fetch_assoc';
    protected $_fetch_row_function = 'mysql_fetch_row';

    public function __construct( $mysql_rs, $fetchMethod, Quick_Db_Sqlite3_SelectResult $selectResult, $arg = null ) {
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
        $ret = array();
        $fetchMethod = $this->_fetchMethod;
        if ($limit === null) {
            while ($ret[] = $this->$fetchMethod())
                ;
            array_pop($ret);
            return $ret;
        }
        else {
            while ($ret[] = $this->$fetchMethod() && ++$i <= $limit)
                ;
            $tail = end($ret);
            if (!isset($tail)) array_pop($ret);
            return $ret;
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
