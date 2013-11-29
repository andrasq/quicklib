<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Base_QueryInfo
    implements Quick_Db_QueryInfo
{
    protected $_link, $_adapter;

    public function __construct( $link, Quick_Db_Adapter $adapter ) {
        $this->_link = $link;
        $this->_adapter = $adapter;
    }

    public function getAffectedRows( ) {
        return $this->_adapter->affected_rows($this->_link);
    }

    public function getLastInsertId( ) {
        return $this->_adapter->mysql_insert_id($this->_link);
    }

    public function getError( ) {
        return $this->_adapter->mysql_error($this->_link);
    }

    public function getErrno( ) {
        return $this->_adapter->mysql_errno($this->_link);
    }
}
