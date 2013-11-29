<?

class Quick_Fifo_FileReaderTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_tempfile = new Quick_Test_Tempfile();
        $this->_filename = (string)$this->_tempfile;
        $this->_datafile = "$this->_filename.(data)";
        $this->_infofile = "$this->_filename.(info)";
        $this->_pidfile =  "$this->_filename.(pid)";

        $this->_cut = new Quick_Fifo_FileReader($this->_filename);
    }

    public function tearDown( ) {
        /*
         * @NOTE: PHPUnit destroys the dependency objects first and only then the owner...
         * Which means by the time the destructor is called, there is are no dependency objects to use!!
         * THIS IS BAD.  Hello?!  (PHP does the right thing, destroys the object, then its dependencies)
         */
        unset($this->_cut);
        @unlink($this->_infofile);
        @unlink($this->_datafile);
        @unlink($this->_pidfile);
        unset($this->_tempfile);
    }

    public function testOpenShouldLockFifo( ) {
        $this->_cut->open();
        $this->assertEquals(getmypid(), trim(file_get_contents("$this->_filename.(pid)")));
    }

    /**
     * @expectedException       Quick_Fifo_Exception
     */
    public function testOpenOfLockedFifoShouldThrowException( ) {
        file_put_contents($this->_pidfile, "1\n");
        $this->_cut->open();
    }

    public function testOpenShouldRenameOriginalFile( ) {
        $this->assertTrue(file_exists($this->_filename));
        $this->_cut->open();
        $this->assertFalse(file_exists($this->_filename));
        $this->assertTrue(file_exists($this->_datafile));
    }

    public function testOpenShouldCreateMutexAndWorkFiles( ) {
        $this->_cut->open();
        $this->assertTrue(file_exists($this->_pidfile), "mutex");
        $this->assertTrue(file_exists($this->_datafile), "data file");
        $this->assertTrue(file_exists($this->_infofile), "header file");
    }

    public function testCloseAfterEofShouldRemoveMutexAndWorkFiles( ) {
        $this->_cut->open();
        $this->_cut->fgets();
        $this->_cut->close();
        $this->assertFalse(file_exists($this->_pidfile));
        $this->assertFalse(file_exists($this->_datafile));
        $this->assertFalse(file_exists($this->_infofile));
    }

    public function testCloseWithoutEofShouldReleaseMutexButKeepDataFileAndHeaderFile( ) {
        $this->_cut->open();
        $this->_cut->close();
        $this->assertFalse(file_exists($this->_pidfile));
        $this->assertTrue(file_exists($this->_datafile));
        $this->assertTrue(file_exists($this->_infofile));
    }

    public function testCanReadFifo( ) {
        file_put_contents($this->_filename, "line1\nline2\nline3\n");
        $this->_cut->open();
        $this->assertEquals("line1\n", $this->_cut->fgets());
        $this->assertEquals("line2\n", $this->_cut->fgets());
        $this->_cut->rsync();
        $this->assertEquals("line3\n", $this->_cut->fgets());
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(1, array($this, '_testSpeedNoop'), array($this->_cut));
        $str = str_repeat('x', 199) . "\n";
        echo "\n";

        file_put_contents($this->_filename, str_repeat($str, 100000));
        $cut = new Quick_Fifo_FileReader($this->_filename);
        $cut->open();
        echo $timer->timeit(1, "fgets 100k 200B lines from fifo", array($this, '_testSpeedFgetsFifo'), array($cut));
        $this->assertTrue($cut->feof());
        $this->assertEquals(filesize($this->_datafile), $cut->ftell());

        file_put_contents($this->_filename, str_repeat($str, 100000));
        $cut = new Quick_Fifo_FileReader($this->_filename);
        $cut->open();
        echo $timer->timeit(1, "read 100k 200B lines from fifo, 50kB/", array($this, '_testSpeedReadFifo'), array($cut));
        $this->assertTrue($cut->feof());
        $this->assertEquals(filesize($this->_datafile), $cut->ftell());
    }

    public function _testSpeedNoop( $cut ) {
    }

    public function _testSpeedFgetsFifo( $cut ) {
        while ($line = $cut->fgets())
            ;
        // 490k lines/sec singly
    }

    public function _testSpeedReadFifo( $cut ) {
        while ($line = $cut->read(50000))
            ;
        // 8.4m lines/sec in 50k batches (7.2 20k) (but 100k is only 5.3, so not too big)
    }
}
