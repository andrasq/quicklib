<?

interface Quick_Db_Adapter
{
    public function setLink($link);
    public function getLink();
    public function execute($sql, $link);

/**
    // these are the calls needed by DbEngine:
    public function escapeString($str);
    public function query($sql);
    public function getErrorCode();
    public function getErrorMessage();
    public function getNumRows($rs);
    public function getAffectedRows($rs);
**/
}
