<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Mysql_Db
    extends Quick_Db_Base_Db
    implements Quick_Db, Quick_Db_Engine
{
    public function __construct( $link, Quick_Db_Mysql_Adapter $adapter ) {
        parent::__construct($link, $adapter);
    }

    public function setLink( $link ) {
        if (!is_resource($link))
            throw new Quick_Db_Exception("Db_Mysql_Db link is not a mysql resource");
        return parent::setLink($link);
    }

    protected function _createSelectResult( $rs ) {
        return new Quick_Db_Mysql_SelectResult($rs);
    }

    protected function _createAdapter( $link ) {
        return new Quick_Db_Mysql_Adapter($link);
    }
}
