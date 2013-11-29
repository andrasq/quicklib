<?

class Quick_Fifo_FragmentReaderTest
    extends Quick_Test_Case
{
    public function setUp( ) {
	$this->_cut = new Quick_Fifo_FragmentReader();
    }

    public function testShouldReturnLineWhenInputEndsInNewline( ) {
	$this->assertEquals("line\n", $this->_cut->fgets("line\n"));
    }

    public function testShouldFlushFragmentsWhenInputEndsInNewline( ) {
	$this->_cut->fgets("line");
	$this->assertEquals("line\n", $this->_cut->fgets("\n"));
    }

    public function testShouldAssembleFragmentsUntilNewline( ) {
	$this->_cut->fgets("line");
	$this->_cut->fgets("line");
	$this->assertEquals("linelineline\n", $this->_cut->fgets("line\n"));
    }

    public function testConstructorArgumentShouldBeSavedAsFirstFragment( ) {
	$cut = new Quick_Fifo_FragmentReader("one");
	$cut->fgets(" two");
	$this->assertEquals("one two three\n", $cut->fgets(" three\n"));
    }

    public function testAddFragmentShouldAccumulateString( ) {
	$this->_cut->fgets("one");
	$this->_cut->addFragment(" two");
	$this->assertEquals("one two three\n", $this->_cut->fgets(" three\n"));
    }

    public function _testSpeedNoop( ) {
    }

    public function xx_testSpeed( ) {
	$timer = new Quick_Test_Timer();
	$nloops = 10000;
	echo "\n";
	// note: timer::_noop() runs 16% faster than $this::_testSpeedNoop() ??
	$timer->calibrate($nloops, array($this, '_testSpeedNoop'), array());
	echo $timer->timeit($nloops, "fgets", array($this, '_testSpeedFgets'), array());
	echo $timer->timeit($nloops, "fgets+fgets", array($this, '_testSpeedReassemble'), array());
	echo $timer->timeit($nloops, "construct+fgets", array($this, '_testSpeedConstructAndFgets'), array());
	echo $timer->timeit($nloops, "addFragment+fgets", array($this, '_testSpeedAddFragmentThenGet'), array());
    }

    public function _testSpeedFgets( ) {
	// 12% slower to access method on $this->_cut than on a local object $cut
	$this->_cut->fgets("line 1\n");
    }

    public function _testSpeedReassemble( ) {
	$this->_cut->fgets("line");
	$this->_cut->fgets(" 1\n");
    }

    public function _testSpeedConstructAndFgets( ) {
	$cut = new Quick_Fifo_FragmentReader("line 1");
	$cut->fgets("\n");
    }

    public function _testSpeedAddFragmentThenGet( ) {
	$this->_cut->addFragment("line 1");
	$this->_cut->fgets("\n");
    }
}
