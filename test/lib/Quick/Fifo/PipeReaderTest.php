<?

class Quick_Fifo_PipeReaderTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_tempfile = new Quick_Test_Tempfile();
        $this->_filename = (string)$this->_tempfile;
        $this->_cut = new Quick_Fifo_PipeReader(fopen($this->_filename, 'r'));
    }

    public function tearDown( ) {
        unset($this->_tempfile);
    }

    /**
     * @expectedException       Quick_Fifo_Exception
     */
    public function testShouldThrowExceptionOnInvalidHandle( ) {
        $cut = new Quick_Fifo_PipeReader(1);
    }

    public function testShouldAcceptFileHandle( ) {
        $cut = new Quick_Fifo_PipeReader(fopen("/dev/null", "r"));
        $cut->fgets();
    }

    public function testShouldAcceptPipe( ) {
        $cut = new Quick_Fifo_PipeReader($fp = popen("cat /dev/null", "r"));
        $ret = $cut->fgets();
    }

    public function testReadingEmptyFileShouldReturnFalse( ) {
        $this->assertFalse($this->_cut->fgets());
    }

    public function testShouldReturnLinesFromFile( ) {
        $this->_writeLine("line 1\nline 2\n");
        $this->assertEquals("line 1\n", $this->_cut->fgets());
        $this->assertEquals("line 2\n", $this->_cut->fgets());
    }

    public function testShouldReturnLineOnlyWhenNewlineSeen( ) {
        $this->_writeLine("line");
        $this->assertFalse($this->_cut->fgets());
        if ($this->_cut->feof()) $this->_cut->clearEof();
        $this->_writeLine(" 1\n");
        $line = $this->_cut->fgets();
        $this->assertEquals("line 1\n", $line);
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $str = str_repeat('x', 199)."\n";
        $lines = str_repeat($str, 100000);
        echo "\n";

        file_put_contents($this->_filename, $lines);
        $cut = new Quick_Fifo_PipeReader(fopen($this->_filename, 'r'));
        echo $timer->timeit(1, "fgets 100k lines", array($this, '_testSpeedFgetsLines'), array($cut));
        // 530k/sec lines w/ fgets

        for ($i=0; $i<9; ++$i) file_put_contents($this->_filename, $lines, FILE_APPEND);
        $cut = new Quick_Fifo_PipeReader(fopen($this->_filename, 'r'));
        echo $timer->timeit(1, "read 1000k lines", array($this, '_testSpeedReadLines'), array($cut));
        // 8.8m/sec 200B lines w/ read (20k), 10.5m/sec (50k)
    }

    public function _testSpeedFgetsLines( $cut ) {
        while ($line = $cut->fgets())
            ;
    }

    public function _testSpeedReadLines( $cut ) {
        while ($lines = $cut->read(20000))
            ;
    }

    protected function _writeLine( $line ) {
        file_put_contents($this->_filename, $line, FILE_APPEND);
    }
}
