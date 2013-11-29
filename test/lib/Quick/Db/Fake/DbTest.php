<?

class Quick_Db_Fake_DbTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Db_Fake_Db();
    }

    public function testSelectShouldCallQuery( ) {
        $cut = $this->getMock('Quick_Db_Fake_Db', array('query'));
        $cut->expects($this->once())->method('query')->with('SELECT foo, 2, 3 FROM bar');
        $cut->select("SELECT foo, 2, 3 FROM bar");
    }

    public function testExecutetShouldCallQuery( ) {
        $cut = $this->getMock('Quick_Db_Fake_Db', array('query'));
        $cut->expects($this->once())->method('query')->with("UPDATE bar SET foo = 2")->will($this->returnValue(true));
        $cut->execute("UPDATE bar SET foo = ?", array(2));
    }

    public function testQueryShouldReturnSetTrue( ) {
        $this->_cut->setResult(true);
        $rs = $this->_cut->query("SELECT !");
        $this->assertTrue($rs);
    }

    /**
     * @expectedException       Quick_Db_Exception
     */
    public function testQueryShouldThrowExceptionOnFalse( ) {
        $this->_cut->setResult(false);
        $this->_cut->query("SELECT 1");
    }

    public function testSelectShouldReturnSelectResultWithResultValues( ) {
        $this->_cut->setResult( array(1,2,3) );
        $rs = $this->_cut->select("SELECT 1");
        $this->assertType('Quick_Db_SelectResult', $rs);
        $this->assertEquals(array(1,2,3), $rs->asColumn()->fetchAll());
    }

    public function testQueryShouldRememberSql( ) {
        $this->_cut->query("FOO 1 2 3");
        $this->_cut->query("SELECT 345");
        $this->assertEquals(array("FOO 1 2 3", "SELECT 345"), $this->_cut->getQueries());
    }

    public function testSelectShouldInterpolateValues( ) {
        $this->_cut->setResult(array(1));
        $this->_cut->select("SELECT ... a = ? AND b = ?", array(22,33));
        $this->assertEquals(array("SELECT ... a = 22 AND b = 33"), $this->_cut->getQueries());
    }

    public function testExecuteShouldInterpolateValues( ) {
        $this->_cut->setResult(true);
        $this->_cut->execute("UPDATE ... a = ? AND b = ?", array(22,33));
        $this->assertEquals(array("UPDATE ... a = 22 AND b = 33"), $this->_cut->getQueries());
    }

    public function testSetResultCanStackMultipleQueryResults( ) {
        $this->_cut->setResult(array(1))->setResult(array(22));
        $this->assertEquals(1, $this->_cut->select("SELECT one")->asColumn()->fetch());
        $this->assertEquals(22, $this->_cut->select("SELECT two two")->asColumn()->fetch());
    }

    public function testGetQueryInfoReturnsInfo( ) {
        $this->assertType('Quick_Db_QueryInfo', $this->_cut->getQueryInfo());
    }
}
