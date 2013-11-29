<?

class Quick_Db_Fake_SelectFetcherTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Db_Fake_SelectFetcher(array(1,22));
    }

    public function testFetchShouldReturnValuesThenFalse( ) {
        $this->assertEquals(1, $this->_cut->fetch());
        $this->assertEquals(22, $this->_cut->fetch());
        $this->assertFalse($this->_cut->fetch());
        $this->assertFalse($this->_cut->fetch());
    }

    public function testFetchAllShouldReturnSuppliedValuesThenEmptyArray( ) {
        $this->assertEquals(array(1, 22), $this->_cut->fetchAll());
        $this->assertEquals(array(), $this->_cut->fetchAll());
        $this->assertEquals(array(), $this->_cut->fetchAll());
    }

    public function testFetchCanBeCombinedWithFetchAll( ) {
        $this->assertEquals(1, $this->_cut->fetch());
        $this->assertEquals(array(22), $this->_cut->fetchAll());
        $this->assertFalse($this->_cut->fetch());
        $this->assertEquals(array(), $this->_cut->fetchAll());
    }

    public function testResetShouldRewindTheData( ) {
        $this->_cut->fetchAll();
        $this->assertFalse($this->_cut->fetch());
        $this->_cut->reset();
        $this->assertEquals(array(1, 22), $this->_cut->fetchAll());
    }

    public function testGetIteratorReturnsDbIterator( ) {
        $this->assertType('Iterator', $this->_cut->getIterator());
        $this->assertType('Quick_Db_Iterator', $this->_cut->getIterator());
    }
}
