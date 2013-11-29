<?

class Quick_Db_Sqlite3_DbTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        if (!class_exists('Sqlite3')) $this->markTestSkipped('Sqlite3 not supported');
        $this->_db = $this->_createDb();
    }

    protected function _createDb( ) {
        @unlink("/tmp/test-sqlite.db");
        $this->_adapter = new Quick_Db_Sqlite3_Adapter();
        $db = new Quick_Db_Sqlite3_Db(new Sqlite3("/tmp/test-sqlite.db"), $this->_adapter);
        unlink("/tmp/test-sqlite.db");
        $db->execute("CREATE TEMPORARY TABLE test (i INT, f DOUBLE, t TEXT)");
        return $db;
    }

    public function testDbShouldSelectListValues( ) {
        $rs = $this->_db->select("SELECT 1, 2, 3");
        $this->assertEquals(array(1, 2, 3), $rs->asList()->fetch());
    }

    public function testDbShouldSelectHashValues( ) {
        $rs = $this->_db->select("SELECT 1, 2, 3");
        $this->assertEquals(array('1' => 1, '2' => 2, '3' => 3), $rs->asHash()->fetch());
    }

    public function testDbShouldSelectColumnByIndex( ) {
        $rs = $this->_db->select("SELECT 1, 2, 3");
        $this->assertEquals(2, $rs->asColumn(1)->fetch());
    }

    public function testDbShouldSelectColumnByName( ) {
        $rs = $this->_db->select("SELECT 1 as a, 2 as b, 3 as c");
        $this->assertEquals(2, $rs->asColumn('b')->fetch());
    }

    public function testDbShouldSelectObject( ) {
        $this->markTestSkipped();
    }

    public function testGetQueryInfoShouldReturnQueryInfo( ) {
        $rs = $this->_db->select("SELECT 1, 2, 3");
        $info = $this->_db->getQueryInfo();
        $this->assertType('Quick_Db_QueryInfo', $info);
    }

    public function testQueryInfoShouldSupportStandardCalls( ) {
        $this->_db->execute("CREATE TEMPORARY TABLE t (i INT)");
        $this->_db->execute("INSERT INTO t VALUES (1), (2), (3)");
        $n = $this->_db->getQueryInfo()->getAffectedRows();
        $this->assertEquals(3, $n);
        $id = $this->_db->getQueryInfo()->getLastInsertId();
        $this->assertEquals(3, $id);
        $err = $this->_db->getQueryInfo()->getError();
        $this->assertType('string', $err);
        $code = $this->_db->getQueryInfo()->getErrno();
        $this->assertType('int', $code);
    }
}
