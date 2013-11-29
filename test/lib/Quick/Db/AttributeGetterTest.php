<?

class Quick_Db_AttributeGetterTest_TestObject
{
    public $a = 1;
    protected $b = 2;
    private $c = 3;
}

class Quick_Db_AttributeGetterTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Db_AttributeGetter();
        $this->_obj = new Quick_Db_AttributeGetterTest_TestObject();
        $this->_obj->x = 1234;
    }

    public function testGetterShouldReturnAllPublicAttributes( ) {
        $this->assertEquals(array('a' => 1, 'x' => 1234), $this->_cut->getPublicAttributes($this->_obj));
    }

    public function testGetterShouldReturnProtectedAttributes( ) {
        $this->assertEquals(array('b' => 2), $this->_cut->getProtectedAttributes($this->_obj));
    }

    public function testGetterShouldReturnPrivateAttributes( ) {
        $this->assertEquals(array('c' => 3), $this->_cut->getPrivateAttributes($this->_obj));
    }
}
