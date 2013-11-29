<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Sqlite_SelectFetcher
    implements Quick_Db_SelectFetcher
{
    protected $_rs, $_fetchMethod, $_selectResult;
    protected $_columnIndex, $_objectClass, $_objectTemplate;

    public function __construct( $rs, $fetchMethod, Quick_Db_Sqlite_SelectResult $selectResult, $arg = null ) {
        // we trust the caller to only specify methods that exist
        $this->_rs = $rs;
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
        // the result resource only when all fetchers have been destroyed
        $this->_selectResult = $selectResult;
    }

    public function fetch( ) {
        $m = $this->_fetchMethod;
        return $this->$m();
    }
    public function reset( ) {
        sqlite_rewind($this->_rs);
    }

    protected function _numRows( $rs ) {
        return sqlite_num_rows($rs);
    }

    public function fetchAll( $limit = null ) {
        if ($limit <= 0) return array();
        // @NOTE: cannot fetch_all without seeking back to the right offset
        if ($limit === null && $this->_fetchMethod === 'fetchHash') {
            $ret = sqlite_fetch_all($this->_rs, SQLITE_ASSOC);
        }
        elseif ($limit === null && $this->_fetchMethod === 'fetchList') {
            $ret = sqlite_fetch_all($this->_rs, SQLITE_NUM);
        }
        else {
            $ret = array();
            $fetchMethod = $this->_fetchMethod;
            while ($k++ < $limit && $r = $this->$fetchMethod())
                $ret[] = $r;
        }
        return $ret;
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
