<?

class Quick_Db_Mysql_CreateAllClassesTest
    extends Quick_Test_Case
{
    public function testClassFilesShouldNotHaveErrors( ) {
        foreach ($files = $this->_findClassFiles() as $file)
            include_once $file;
    }

    public function testCreateAllClass( ) {
        global $phpunitDbCreds;

        $mysql = new Quick_Db_Mysql_Adapter();

        $creds = new Quick_Db_Credentials_Discrete();
        $conn = new Quick_Db_Mysql_Connection($phpunitDbCreds, $mysql);

        $db = new Quick_Db_Mysql_Db($conn->createLink(), $mysql);

        $link = 0;
        $rs = null;
        $selectResult = new Quick_Db_Mysql_SelectResult($rs);

        $selectFetcher = new Quick_Db_Mysql_SelectFetcher($rs, 'fetchHash', $selectResult, null);

        // only in dev:
        //$dbInfo = new Quick_Db_Mysql_DbInfo($db);

        $resultInfo = new Quick_Db_Mysql_ResultInfo($rs, new Quick_Db_Mysql_Adapter(null));

        // only in dev:
        //$dbTable = new Quick_Db_Mysql_Table($db, 'table_name');
    }

    public function testFindClassFiles( ) {
        $files = $this->_findClassFiles();
        $this->assertEquals(9-2, count($files));
    }

    protected function _findClasses( ) {
        $files = $this->_findClassFiles();
        foreach ($files as & $file)
            $file = basename($file, '.php');
        return $files;
    }

    protected function _findClassFiles( ) {
        return  glob($_SERVER['DOCUMENT_ROOT'] . '/lib/Quick/Db/Mysql/*.php');
    }
}
