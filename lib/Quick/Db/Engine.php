<?

interface Quick_Db_Engine
    extends Quick_Db
{
    public function setLink($link);
    public function setLogger(Quick_Logger $logger);
}
