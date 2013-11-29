<?

/**
 * Wrapper to encapsulate sqlite3 functions with mysql_ names.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Sqlite3_Adapter
    extends Quick_Db_Sqlite_Adapter
    implements Quick_Db_Adapter
{
    protected $_link;

    public function __construct( $link = null ) {
        $this->_link = $link;
    }

    public function sqlite3_connect( $filename, $flags = null, $encryptionKey = null ) {
        // flags cannot be null else "out of memory" error; default = (SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE)
        if ($encryptionKey !== null)
            return new SQLite3($filename, $flags, $encryptionKey);
        elseif ($flags !== null)
            return new SQLite3($filename, $flags);
        else
            return new SQlite3($filename);
    }

    public function mysql_close( $link ) {
        return $link->close();
    }

    public function mysql_errno( $link ) {
        return $link->lastErrorCode();
    }

    public function mysql_error( $link ) {
        return $link->lastErrorMsg();
    }

    public function execute( $sql, $link ) {
        return $this->mysql_query($sql, $link);
    }

    public function mysql_query( $sql, $link ) {
        // suppress php warnings about syntax errors, we throw our own exception
        if ($this->_isExec($sql)) return @$link->exec($sql);
        $rs = @$link->query($sql);
        // check whether the result set is empty for num_rows.  This is an ugly hack...
        if ($rs && $rs->fetchArray(SQLITE3_NUM)) { $rs->reset(); $rs->haveRows = 1; }
        return $rs;
    }

    public function mysql_free_result( $rs ) {
        return $rs->finalize();
    }

    public function affected_rows( $link ) {
        return $link->changes();
    }

    public function mysql_insert_id( $link ) {
        return $link->lastInsertRowID();
    }

    public function num_rows( $rs ) {
        // SQLite does not implement buffered results, thus no row count is available
        // Sqlite2 faked it; Sqlite3 omits it altogether.  Cant fake it ourselves
        // without a way to seek the read point (since rewind resets it to 0).
        return isset($rs->haveRows) ? 1 : 0;
    }

    public function mysql_real_escape_string( $str, $link ) {
        return $link->escapeString($str);
    }
}
