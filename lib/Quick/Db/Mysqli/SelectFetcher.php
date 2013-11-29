<?

/**
 * Fetch results from a mysqli_result resource.
 * This is virtually the same as for mysql_result, just some minor diffs.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-06-29 - AR.
 */

class Quick_Db_Mysqli_SelectFetcher
    extends Quick_Db_Mysql_SelectFetcher
    implements Quick_Db_SelectFetcher
{
    protected $_fetch_assoc_function = 'mysqli_fetch_assoc';
    protected $_fetch_row_function = 'mysqli_fetch_row';

    public function reset( ) {
        @mysqli_data_seek($this->_rs, 0);
    }

    protected function _numRows( $rs ) {
        return $rs->num_rows;
    }

    protected function fetchList( ) {
        return ($r = mysqli_fetch_row($this->_rs)) ? $r : false;
    }
    protected function fetchHash( ) {
        return ($r = mysqli_fetch_assoc($this->_rs)) ? $r : false;
    }
    protected function fetchListColumn( ) {
        return ($r = mysqli_fetch_row($this->_rs)) ? $r[$this->_columnIndex] : false;
    }
    protected function fetchHashColumn( ) {
        return ($r = mysqli_fetch_assoc($this->_rs)) ? $r[$this->_columnIndex] : false;
    }
}
