<?

class Quick_Db_Sqlite3_Db
    extends Quick_Db_Base_Db
    implements Quick_Db_Engine
{
    public function __construct( SQLite3 $link, Quick_Db_Sqlite3_Adapter $adapter ) {
        parent::__construct($link, $adapter);
    }

    public function setLink( $link ) {
        if (! $link instanceof SQLite3)
            throw new Quick_Db_Exception("Db_Sqlite3_Db link is not an SQLite3 object");
        return parent::setLink($link);
    }

    protected function _createSelectResult( $rs ) {
        return new Quick_Db_Sqlite3_SelectResult($rs);
    }

    protected function _createAdapter( $link ) {
        return new Quick_Db_Sqlite3_Adapter($link);
    }
}
