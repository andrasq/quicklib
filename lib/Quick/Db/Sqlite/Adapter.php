<?

/**
 * Wrapper to encapsulate sqlite access functions with mysql_ names.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Sqlite_Adapter
    extends Quick_Db_Mysql_Adapter
{
    protected $_link;

    public function __construct( $link = null ) {
        $this->_link = $link;
    }

    public function setLink( $link ) {
        $this->_link = $link;
        return $this;
    }

    public function getLink( ) {
        return $this->_link;
    }

    public function sqlite_connect( $filename, $flags = 0, $encryptionKey = null ) {
        return ($encryptionKey !== null)
            ? new sqlite_connect($ffilename, $flags, $encryptionKey)
            : new sqlite_connect($ffilename, $flags);
    }

    public function mysql_connect( $host, $user, $password, $newConnection ) {
        throw new Quick_Db_Exception("mysql_connect called in Sqlite_Adapter");
    }

    public function mysql_close( $link ) {
        return sqlite_close($link);
    }

    public function mysql_errno( $link ) {
        return sqlite_last_error($link);
    }

    public function mysql_error( $link ) {
        return sqlite_error_string(sqlite_last_error($link));
    }

    public function execute( $sql, $link ) {
        return $this->mysql_query($sql, $link);
    }

    public function mysql_query( $sql, $link ) {
        return $this->_isExec($sql) ? sqlite_exec($link, $sql) : sqlite_query($link, $sql);
    }

    public function mysql_free_result( $rs ) {
        // n/a ??
        return false;
    }

    public function affected_rows( $link ) {
        return sqlite_changes($link);
    }

    public function mysql_insert_id( $link ) {
        return sqlite_last_insert_rowid($link);
    }

    public function num_rows( $rs ) {
        return sqlite_num_rows($rs);
    }

    public function mysql_real_escape_string( $str, $link ) {
        return sqlite_escape_string($str);
    }

    // return true if the sql query does not return data, just success/failure
    protected function _isExec( $sql ) {
        // these sqlite3 commands do not return results (there are others)
        // note that .output FILENAME will redirect output away from screen; .output stdout to restore
        static $execCmds = array(
            'UPDATE' => 1,
            'INSERT' => 1,
            'DELETE' => 1,
            'CREATE' => 1,
            'PRAGMA' => 1,
            '.ECHO' => 1, '.EXPLAIN' => 1, '.HEADER' => 1, '.HEADERS' => 1,
            '.IMPORT' => 1, '.MODE' => 1, '.READ' => 1, '.SEPARATOR' => 1,
            '.TIMEOUT' => 1,
        );
        $sql = ltrim($sql);
        $cmd = strtoupper(substr($sql, 0, strcspn($sql, " \t")));
        return isset($execCmds[$cmd]);
    }
}
