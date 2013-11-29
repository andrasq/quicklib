<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Mysqli_Db
    extends Quick_Db_Base_Db
    implements Quick_Db, Quick_Db_Engine
{
    protected $_link, $_adapter;

    public function __construct( /* mysqli */ $link, Quick_Db_Mysqli_Adapter $adapter ) {
        parent::__construct($link, $adapter);
    }

    public function setLink( $link ) {
        if (! $link instanceof Mysqli)
            throw new Quick_Db_Exception("Db_Mysqli_Db link is not a mysqli object");
        return parent::setLink($link);
    }

    protected function _createSelectResult( $rs ) {
        return new Quick_Db_Mysqli_SelectResult($rs);
    }

    protected function _createAdapter( $link ) {
        return new Quick_Db_Mysqli_Adapter($link);
    }
}
