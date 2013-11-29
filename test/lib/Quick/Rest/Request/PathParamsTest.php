<?

class Quick_Rest_Request_PathParamsTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Rest_Request_PathParams();
        $this->_cut->setTemplate('/path/name/{a}/{b}');
    }

    public function testShouldReturnMatchingParamsAndIgnoreExtras( ) {
        $params = $this->_cut->getParams('/path/name/1/2/3');
        $this->assertEquals(array('a' => 1, 'b' => 2), $params);
    }

    public function testShouldAllowRedunantPathSeparators( ) {
        $params = $this->_cut->getParams('//path////name/1/2');
        $this->assertEquals(array('a' => 1, 'b' => 2), $params);
    }

    public function testShouldScanBracesInParams( ) {
        $params = $this->_cut->getParams('/path/name/{a}/{b}');
        $this->assertEquals(array('a' => '{a}', 'b' => '{b}'), $params);
    }

    /**
     * @expectedException       Quick_Rest_Exception
     */
    public function testShouldErrorOutIfPathsDoNotMatch( ) {
        $cut = new Quick_Rest_Request_PathParams();
        $cut->setTemplate('/path/name/{a}/{b}');
        $params = $cut->getParams('/foo/bar/1/2');
    }
}
