<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Mysql_Connection
    extends Quick_Db_Base_Connection
    implements Quick_Db_Connection
{
    public function __construct( Quick_Db_Credentials $creds, Quick_Db_Mysql_Adapter $adapter ) {
        parent::__construct($creds, $adapter);
    }

    protected function _createLink( Quick_Db_Credentials $creds ) {
        $host = $creds->getHost();
        if ($socket = $creds->getSocket()) $host .= ":$socket";
        elseif ($port = $creds->getPort()) $host .= ":$port";
        return $this->_adapter->mysql_connect(
            $host,
            $creds->getUser(),
            $creds->getPassword(),
            true
        );
    }
}
