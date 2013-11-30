<?

class Quick_Db_Sqlite3_AdapterTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_link = new SQLite3("/tmp/test-sqlite3.db");
        $this->_cut = new Quick_Db_Sqlite3_Adapter($this->_link);
    }

    public function testGetLinkShouldReturnLink( ) {
        $this->assertType('SQLite3', $this->_cut->getLink());
    }

    public function testSetLinkShouldSetLink( ) {
        $id = uniqid();
        $this->assertEquals($id, $this->_cut->setLink($id)->getLink());
    }

    public function testConnectShouldAttachToFile( ) {
        $link = $this->_cut->sqlite3_connect("/tmp/test-sqlite3.db");
        $this->assertType('SQLite3', $link);
    }

    public function testErrnoShouldReturnErrorCode( ) {
        $rs = $this->_cut->mysql_query("SELECT 1 FROM", $this->_link);
        $this->assertFalse($rs);
        $this->assertTrue(is_numeric($this->_cut->mysql_errno($this->_link)));
    }

    public function testErrorShouldReturnErrorCode( ) {
        $rs = $this->_cut->mysql_query("SELECT 1 FROM", $this->_link);
        $this->assertFalse($rs);
        $this->assertContains("syntax error", $this->_cut->mysql_error($this->_link));
    }

    public function testQueryShouldReturnFalseOnError( ) {
        $rs = $this->_cut->mysql_query("SELECT x FROM", $this->_link);
        $this->assertFalse($rs);
    }

    public function testQueryShouldReturnTrueOnSuccess( ) {
        $rs = $this->_cut->mysql_query("CREATE TEMPORARY TABLE t (i INT)", $this->_link);
        $this->assertTrue($rs);
    }

    public function testQueryShouldReturnResults( ) {
        $rs = $this->_cut->mysql_query("SELECT 1", $this->_link);
        $this->assertType('SQLite3Result', $rs);
    }

    public function testFreeResultShouldAcceptResult( ) {
        $rs = $this->_cut->mysql_query("SELECT 1", $this->_link);
        $ok = $this->_cut->mysql_free_result($rs);
        $this->assertTrue($ok);
    }

    public function testAffectedRowsShouldReturnChangeCount( ) {
        $this->_cut->mysql_query("CREATE TEMPORARY TABLE t (i INT)", $this->_link);
        $this->_cut->mysql_query("INSERT INTO t (i) SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3", $this->_link);
        $this->assertEquals(3, $this->_cut->affected_rows($this->_link));
    }

    public function testNumRowsReturnsRowCount( ) {
        $this->_cut->mysql_query("CREATE TEMPORARY TABLE t (i INT)", $this->_link);

        // empty table has 0 rows
        $rs = $this->_cut->mysql_query("SELECT * FROM t", $this->_link);
        $this->assertEquals(0, $this->_cut->num_rows($rs));

        // sqlite < 3.7.11 does not support multiple insert values, use the UNION workaround
        $this->_cut->mysql_query("INSERT INTO t (i) SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3", $this->_link);

        // inserted table has 3 rows, but sqlite3 does not provide the result count, test for non-zero instead
        $rs = $this->_cut->mysql_query("SELECT * FROM t", $this->_link);
        $this->assertGreaterThanOrEqual(1, $this->_cut->num_rows($rs));
    }

    public function testEscapeStringShouldQuoteChars( ) {
        $str = $this->_cut->mysql_real_escape_string("a'b", $this->_link);
        $this->assertEquals("a''b", $str);
    }
}
