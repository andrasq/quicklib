<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Sqlite_Db
    extends Quick_Db_Base_Db
    implements Quick_Db, Quick_Db_Engine
{
    public function __construct( $link, Quick_Db_Sqlite_Adapter $adapter ) {
        parent::__construct($link, $adapter);
    }

    protected function _createSelectResult( $rs ) {
        return new Quick_Db_Sqlite_SelectResult($rs);
    }

    protected function _createAdapter( $link ) {
        return new Quick_Db_Sqlite_Adapter($link);
    }
}
