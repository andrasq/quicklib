<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Mysqli_Adapter
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

    public function mysqli_connect( $host, $user, $password, $dbname, $port, $socket ) {
        return mysqli_connect($host, $user, $password, $dbname, $port, $socket);
    }

    public function mysql_close( $link ) {
        return mysqli_close($link);
    }

    public function mysql_errno( $link ) {
        return $link->errno;
    }

    public function mysql_error( $link ) {
        return $link->error;
    }

    public function execute( $sql, $link ) {
        return $this->mysql_query($sql, $link);
    }

    public function mysql_query( $sql, $link ) {
        return mysqli_query($link, $sql);
    }

    public function mysql_free_result( $rs ) {
        return mysqli_free_result($rs);
    }

    public function affected_rows( $link ) {
        return mysqli_affected_rows($link);
    }

    public function mysql_insert_id( $link ) {
        return mysqli_insert_id($link);
    }

    public function num_rows( $rs ) {
        return $rs->num_rows;
    }

    public function mysql_real_escape_string( $str, $link ) {
        return is_object($link) ? mysqli_real_escape_string($str, $link) : addslashes($str);
    }

    public function reconnect( $link ) {
        return mysqli_ping($link);
    }
}
