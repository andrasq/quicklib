<?

class Quick_Proc_ExistsTest
    extends Quick_Test_Case
{
    public function setUp( ) {
	$this->_cut = new Quick_Proc_Exists();
    }

    public function testShouldFindRunningProcess( ) {
	$this->assertTrue($this->_cut->processExists(getmypid()));
    }

    public function testShouldPassSystemProcess( ) {
	$this->assertTrue($this->_cut->processExists(1));
    }

    public function testShouldFailOnZeroPid( ) {
	$this->assertFalse($this->_cut->processExists(0));
    }

    public function testShouldFailOnOverlargePid( ) {
	$this->assertFalse($this->_cut->processExists(99999));
    }
}
