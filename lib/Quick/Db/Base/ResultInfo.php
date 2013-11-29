<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Base_ResultInfo
    implements Quick_Db_ResultInfo
{
    protected $_rs;

    public function __construct( $rs, Quick_Db_Adapter $adapter ) {
        $this->_rs = $rs;
        $this->_adapter = $adapter;
    }

    public function getNumRows( ) {
        return $this->_adapter->num_rows($this->_rs);
    }
}
