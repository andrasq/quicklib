<?

/**
 * If db disconnects, reconnect and run the query again.
 * Also retries failed db connections, because TCP/IP can be unreliable.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Decorator_RetryReconnect
    implements Quick_Db
{
    protected $_conn, $_db;
    protected $_retryLimit = 3;
    protected $_reconnectLimit = 2;

    public function __construct( Quick_Db_Connection $conn, Quick_Db_Engine $db ) {
        $this->_conn = $conn;
        $this->_db = $db;
    }

    public function query( $sql, $tag = '' ) {
        return $this->_retry('query', $sql, $tag);
    }

    public function select( $sql, Array $values = null ) {
        return $this->_retry('select', $sql, $values);
    }

    public function execute( $sql, Array $values = null ) {
        return $this->_retry('execute', $sql, $values);
    }

    public function getQueryInfo( ) {
        return $this->_db->getQueryInfo();
    }


    protected function _retry( $method, $sql, $arg ) {
        $ntries = 0;
        do {
            try {
                return $this->_db->$method($sql, $arg);
            }
            catch (Exception $e) {
                if ($this->_isRetriableError($this->_db->getQueryInfo()->getError()))
                    $this->_reconnect($this->_db);
                else
                    throw $e;
            }
        } while (++$ntries <= $this->_retryLimit);

        // if all retries failed, rethrow the last exception
        throw $e;
    }

    protected function _reconnect( Quick_Db_Engine $db ) {
        $ntries = 0;
        do {
            try { $db->setLink($this->_conn->createLink()); return; }
            catch (Exception $e) { }
        } (while ++$ntries <= $this->_reconnectLimit && (1 || usleep(10000)));
        // if unable to reconnect, rethrow the last exception
        throw $e;
    }

    protected function _isRetriableError( $error ) {
        return
            // retry on MySQL server disconnect errors
            stripos($error, "MySQL server has gone away") !== false ||                          // 2006
            stripos($error, "Lost connection to MySQL server during query") !== false ||        // 2013
            stripos($error, "Deadlock found when trying to get lock") ||                        // 1213
            stripos($error, "Lock wait timeout exceeded") ||                                    // 1205
            false
            ;
    }
}
