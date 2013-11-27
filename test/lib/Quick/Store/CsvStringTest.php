<?

class Quick_Store_CsvStringTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Store_CsvString("a=1,b=two");
    }

    public function testConstructorShouldInitializeFromArray( ) {
        $cut = new Quick_Store_CsvString(array('c' => 3, 'd' => 4));
        $this->assertEquals("c=3,d=4", (string)$cut);
    }

    public function testConstructorShouldInitializeFromObject( ) {
        $cut = new Quick_Store_CsvString((object)array('e' => 5, 'f' => 6.5));
        $this->assertEquals("e=5,f=6.5", (string)$cut);
    }

    public function testCastToArrayShouldReturnHash( ) {
        $hash = (array)$this->_cut;
        $this->assertEquals(array('a' => 1, 'b' => 'two'), $hash);
    }

    public function testObjectVarsShouldBeSameAsHash( ) {
        $hash = get_object_vars($this->_cut);
        $this->assertEquals((array)$this->_cut, $hash);
    }

    public function testSetShouldAddField( ) {
        $this->_cut->set('c', '333');
        $this->assertEquals(array('a' => 1, 'b' => 'two', 'c' => '333'), (array)$this->_cut);
    }

    public function testGetShouldReturnFalseIfFieldNotFound( ) {
        $this->assertFalse($this->_cut->get('c'));
    }

    public function testGetShouldReturnField( ) {
        $this->assertEquals('two', $this->_cut->get('b'));
    }

    public function testAddShouldAddFieldIfNotAlreadySet( ) {
        $this->_cut->add('b', 2);
        $this->_cut->add('c', 3);
        $this->assertEquals(array('a' => 1, 'b' => 'two', 'c' => 3), (array)$this->_cut);
    }

    public function testDeleteShouldUnsetValue( ) {
        $this->_cut->delete('b');
        $this->_cut->delete('c');
        $this->assertEquals(array('a' => 1), (array)$this->_cut);
    }

    public function testStringCastShouldPackValuesAsCsvString( ) {
        $this->_cut->add('c', 3);
        $this->_cut->set('b', 2);
        $this->assertEquals("a=1,b=2,c=3", (string)$this->_cut);
    }
}
