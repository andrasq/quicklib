<?

/**
 * Common database implementation, works through the adapter.
 * The original implementation was that of Mysql_Db.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Base_Db
    implements Quick_Db, Quick_Db_Engine
{
    protected $_link, $_adapter;
    protected $_resultFactory, $_queryInfoFactory;
    protected $_logger;

    // only derived classes can be created
    protected function __construct( $link, Quick_Db_Adapter $adapter ) {
        $this->_link = $link;
        $this->_adapter = $adapter;
    }

    public function setLink( $link ) {
        $this->_link = $link;
        $this->_adapter->setLink($link);
        return $this;
    }

    public function getLink( ) {
        return $this->_link;
    }

    public function setLogger( $logger ) {
        if (!method_exists($logger, 'debug') || !method_exists($logger, 'info') || !method_exists($logger, 'err'))
            throw new Quick_Db_Exception("db logger must have debug, info and err methods");
        $this->_logger = $logger;
        $this->_logger = $logger;
        return $this;
    }

    protected function _createSelectResult( $rs ) {
        // override in derived class
        throw new Quick_Db_Exception("must be implemented in derived class");
    }

    protected function _createAdapter( $link ) {
        // override in derived class
        throw new Quick_Db_Exception("must be implemented in derived class");
    }

    public function query( $sql, $tag = '' ) {
        if ($tag !== '') $sql = "$sql /* $tag */";
        if ($this->_logger) $tm = microtime(true);
        try {
            $rs = $this->_adapter->mysql_query($sql, $this->_link);
            if ($this->_logger) $this->_echoQuery($rs, $sql, microtime(true) - $tm);
            if ($rs === false) $this->_throwMysqlException($sql);
            elseif ($rs === true) return true;
            return $this->_createSelectResult($rs);
        }
        catch (Quick_Db_Exception $e) {
            $this->_throwMysqlException($sql);
        }
    }

    public function select( $sql, Array $values = null ) {
        if (($rs = $this->query($values ? $this->_interpolateValues($sql, $values) : $sql)) === true)
            $this->_throwException("select: sql did not return results, use execute() instead; sql = $sql");
        else return $rs;
    }

    public function execute( $sql, Array $values = null ) {
        if (($rs = $this->query($values ? $this->_interpolateValues($sql, $values) : $sql)) !== true)
            $this->_throwException("execute: sql returned results, use select() instead; sql = $sql");
        else return $rs;
    }

    public function getQueryInfo( ) {
        // capture current link in a new adapter to not be affected by setLink()
        return new Quick_Db_Base_QueryInfo($this->_link, $this->_createAdapter($this->_link));
    }


    protected function _interpolateValues( $sql, Array & $values ) {
        if (($nq = substr_count($sql, '?')) !== ($nv = count($values))) {
            $this->_throwException("interpolated parameter count mismatch: expected $nq != provided $nv");
        }
        $fmt = str_replace('?', '%s', str_replace('%', '%%', $sql));
        foreach ($values as $value) {
            // faster to test for is_numeric if we can then avoid addslashes
            $args[] = ($value === null) ? 'NULL' : (is_numeric($value) ? "$value" : "'".$this->_adapter->mysql_real_escape_string($value, $this->_link)."'");
        }
        return vsprintf($fmt, $args);
    }

    protected function _echoQuery( $rs, $sql, $tm ) {
        if ($rs === false) {
            $nrows = "ERROR {$this->_adapter->mysql_errno($this->_link)}: {$this->_adapter->mysql_error($this->_link)}";
        }
        elseif ($rs === true) {
            $nrows = "{$this->_adapter->affected_rows($this->_link)} rows affected";
        }
        else {
            $nrows = "{$this->_adapter->num_rows($rs)} rows";
        }
        $msg = sprintf("SQL: %s (%.6f) -- %s", $nrows, $tm, $sql);
        if ($this->_logger) $this->_logger->info($msg);
        else echo $msg . "\n";
    }

    protected function _throwMysqlException( $sql ) {
        $errno = $this->_adapter->mysql_errno($this->_link);
        $error = $this->_adapter->mysql_error($this->_link);
        throw new Quick_Db_Exception("sql error: $errno: $error; sql = ``$sql''");
    }

    protected function _throwException( $msg ) {
        throw new Quick_Db_Exception($msg);
    }
}
