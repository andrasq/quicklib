<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

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
     * @expectedException       Exception
     * // actually throws Quick_Proc_Exception from the mutex
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

    public function testSetSharedModeShouldCauseOpenToNotCreatePidfile( ) {
        $this->_cut->setSharedMode(true);
        $this->_cut->open();
        $this->assertFalse(file_exists($this->_pidfile), "mutex");
    }

    /**
     * @expectedException       Quick_Fifo_Exception
     */
    public function testRsyncShouldThrowExceptionIfNotOwner( ) {
        $this->_cut->setSharedMode(true);
        $this->_cut->open();
        $this->_cut->rsync();
    }

    /**
     * @expectedException       Quick_Fifo_Exception
     */
    public function testFputsRsyncShouldThrowExceptionIfNotOwner( ) {
        $this->_cut->setSharedMode(true);
        $this->_cut->open();
        $this->_cut->rsync();
    }

    public function testRsyncAfterEofShouldKeepMutexButRemoveDataFileAndHeaderFile( ) {
        $this->_cut->open();
        $this->_cut->fgets();
        $this->_cut->rsync();
        $this->assertTrue(file_exists($this->_pidfile));
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

    public function testFputsShouldAppendToFifo( ) {
        $id = uniqid();
        $this->_cut->fputs("id = $id\n");
        $this->_cut->open();
        $this->assertEquals("id = $id\n", $this->_cut->fgets());
    }

    public function testFputsShouldAppendToFile( ) {
        $this->_cut->fputs("test123\n");
        $this->assertEquals("test123\n", file_get_contents($this->_filename));
    }

    public function testFputsShouldAddMissingNewline( ) {
        $this->_cut->fputs("test123");
        $this->assertEquals("test123\n", file_get_contents($this->_filename));
    }

    public function testWriteShouldAppendToFile( ) {
        $this->_cut->write("line1\nline2");
        $this->assertEquals("line1\nline2\n", file_get_contents($this->_filename));
    }

    public function testRsyncShouldSeeNewlyAddedLines( ) {
        $this->_cut->fputs("line1");
        $this->_cut->open();
        $this->assertEquals("line1\n", $this->_cut->fgets());
        $this->assertFalse($this->_cut->fgets());
        $this->_cut->fputs("line2");
        $this->_cut->rsync();
        $this->assertEquals("line2\n", $this->_cut->fgets());
    }

    public function testClearEofShouldNotSkipLines( ) {
        $this->_cut->fputs("line1\nline2");
        $this->_cut->open();
        $this->assertEquals("line1\n", $this->_cut->fgets());
        // commit the lines read so far, because clearEof reopens to last checkpointed read point
        $this->_cut->rsync();
        $this->_cut->clearEof();
        $this->assertEquals("line2\n", $this->_cut->fgets());
    }

    public function testFtellShouldReturnReadOffset( ) {
        $this->_cut->fputs("line 1\nline 2\n");
        $this->_cut->open();
        $this->assertEquals(0, $this->_cut->ftell());
        $line = $this->_cut->fgets();
        $this->assertEquals(strlen($line), $this->_cut->ftell());
    }

    public function testRsyncShouldSaveOffsetParameter( ) {
        $this->_cut->fputs("line 1\nline 2\n");
        $this->_cut->open();
        $this->_cut->rsync(4);
        $this->_cut->close();
        $this->_cut->open();
        $this->assertEquals(" 1\n", $this->_cut->fgets());
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
        // 750k/s

        file_put_contents($this->_filename, str_repeat($str, 100000));
        $cut = new Quick_Fifo_FileReader($this->_filename);
        $cut->open();
        echo $timer->timeit(1, "read 100k 200B lines from fifo, 50kB/", array($this, '_testSpeedReadFifo'), array($cut));
        $this->assertTrue($cut->feof());
        $this->assertEquals(filesize($this->_datafile), $cut->ftell());
        // 9000k/s

        echo $timer->timeit(1, "fputs 100k 200B lines to fifo", array($this, '_testSpeedFputsFifo'), array($cut));
        // 125k/s using file_put_contents(LOCK_EX|FILE_APPEND)

        $lines = array(
            $str, $str, $str, $str, $str, $str, $str, $str, $str, $str,
            //$str, $str, $str, $str, $str, $str, $str, $str, $str, $str,
        );
        echo $timer->timeit(1, "write 100k 200B lines to fifo, 10/", array($this, '_testSpeedWriteFifo'), array($cut, $lines));
        // 100k/s in batches of 1 or 4, 725k/s in batches of 10 (380k/s in batches of 20, 390k/s in batches of 50)
        // ... but on subsequent tests only 300k in batches of 10??  then 660k/s??
    }

    public function _testSpeedNoop( $cut ) {
    }

    public function _testSpeedFgetsFifo( $cut ) {
        while ($line = $cut->fgets())
            ;
        // 490k lines/sec singly
        // 750k lines/sec phenom ii 3.6 ghz
    }

    public function _testSpeedReadFifo( $cut ) {
        while ($line = $cut->read(50000))
            ;
        // 8.4m lines/sec in 50k batches (7.2 20k) (but 100k is only 5.3, so not too big)
        // 12.4m lines/sec phenom ii 3.6 ghz
    }

    public function _testSpeedFputsFifo( $cut ) {
        for ($i=0; $i<100000; ++$i) {
            $cut->fputs("$i\n");
        }
    }

    public function _testSpeedWriteFifo( $cut, & $lines ) {
        for ($i=0; $i<10000; ++$i) {
            $cut->write(implode("\n", $lines) . "\n");
        }
    }
}
