<?

/**
 * DB connection builder.  This db-specific class creates links with the
 * credentials provided in its constructor.  The link can be used by the
 * db engine if handed to it with setLink().
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Base_Connection
    implements Quick_Db_Connection
{
    protected $_creds, $_adapter;
    protected $_config = array();

    // protected constructor, must be called from derived class
    protected function __construct( Quick_Db_Credentials $creds, Quick_Db_Adapter $adapter ) {
        $this->_creds = $creds;
        $this->_adapter = $adapter;
        if ($db = $this->_creds->getDatabase()) $this->configure("USE `$db`");
    }

    // execute the sql on the next link created.  Multiple sqls will run in order
    public function configure( $sql ) {
        $this->_config[] = $sql;
        return $this;
    }

    // connect to the database identified by the credentials.
    public function createLink( ) {
        $link = $this->_createLink($this->_creds);
        if (!$link) throw new Quick_Db_Exception("unable to connect to db");
        if ($this->_config) $this->configureLink($link);
        return $link;
    }

    // apply the configuration to an existing db link
    public function configureLink( $link ) {
        $this->_applyConfig($link);
    }


    protected function _createLink( Quick_Db_Credentials $creds ) {
        throw new Quick_Db_Exception("must be implemented in derived class");
    }

    protected function _applyConfig( $link ) {
        $this->_adapter->setLink($link);
        foreach ($this->_config as $sql) {
            if (!$ok = $this->_adapter->execute($sql, $link)) {
                $errcode = $this->_adapter->mysql_errno($link);
                $errmsg = $this->_adapter->mysql_error($link);
                // ...should config errors themselves be retried?
                throw new Quick_Db_Exception("error configuring db link: $errcode: $errmsg; sql = $sql");
            }
        }
    }
}
