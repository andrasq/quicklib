<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Mysqli_Connection
    extends Quick_Db_Base_Connection
    implements Quick_Db_Connection
{
    public function __construct( Quick_Db_Credentials $creds, Quick_Db_Mysqli_Adapter $adapter ) {
        parent::__construct($creds, $adapter);
    }

    protected function _createLink( Quick_Db_Credentials $creds ) {
        return $this->_adapter->mysqli_connect(
            $creds->getHost(),
            $creds->getUser(),
            $creds->getPassword(),
            $creds->getDatabase(),
            (int) $creds->getPort(),    // must be integer
            $creds->getSocket()
        );
    }
}
