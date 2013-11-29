<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Fake_Adapter
    extends Quick_Db_Mysql_Adapter
{
    protected $_link;
    protected $_queries = array();

    public function mysql_connect( $host, $user, $password, $newConnection ) {
        throw new Quick_Db_Exception("mysql_connect called in Fake_Adapter");
    }

    public function mysql_close( $link ) {
        return false;
    }

    public function mysql_errno( $link ) {
        return 0;
    }

    public function mysql_error( $link ) {
        return "";
    }

    public function mysql_query( $sql, $link ) {
        $this->_queries[] = $sql;
        return false;
    }

    public function getQueries( ) {
        return $this->_queries;
    }

    public function mysql_free_result( $rs ) {
        return false;
    }

    public function affected_rows( $link ) {
        return 0;
    }

    public function mysql_insert_id( $link ) {
        return 0;
    }

    public function num_rows( $rs ) {
        return count($rs);
    }

    public function mysql_real_escape_string( $str, $link ) {
        return $str;
    }
}
