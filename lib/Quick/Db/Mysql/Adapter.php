<?

/**
 * Simple little wrapper to encapsulate mysql access functions.
 * Handy for unit testing, but critical loops (result fetchers) bypass this.
 *
 * Note that it's 5% faster to use $this->_link than to pass it via func arg.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 *
 * 2013-02-18 - AR.
 */

class Quick_Db_Mysql_Adapter
    implements Quick_Db_Adapter
{
    protected $_link;

    public function __construct( $link = null ) {
        $this->_link = $link;
    }

    public function setLink( $link ) {
        $this->_link = $link;
    }

    public function getLink( ) {
        return $this->_link;
    }

    public function mysql_connect( $host, $user, $password, $newConnection ) {
        return mysql_connect($host, $user, $password, $newConnection);
    }

    public function mysql_close( $link ) {
        return mysql_close($link);
    }

    public function mysql_errno( $link ) {
        return mysql_errno($link);
    }

    public function mysql_error( $link ) {
        return mysql_error($link);
    }

    public function execute( $sql, $link ) {
        return $this->mysql_query($sql, $link);
    }

    public function mysql_query( $sql, $link ) {
        return mysql_query($sql, $link);
    }

    public function mysql_free_result( $rs ) {
        return mysql_free_result($rs);
    }

    public function affected_rows( $link ) {
        return mysql_affected_rows($link);
    }

    public function mysql_insert_id( $link ) {
        return mysql_insert_id($link);
    }

    public function num_rows( $rs ) {
        return mysql_num_rows($rs);
    }

    public function mysql_real_escape_string( $str, $link ) {
        return is_resource($link) ? mysql_real_escape_string($str, $link) : addslashes($str);
    }

    public function reconnect( $link ) {
        return mysql_ping($link);
    }
}
