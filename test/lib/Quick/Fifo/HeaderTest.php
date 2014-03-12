<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Fifo_HeaderTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_tempfile = new Quick_Test_Tempfile();
        $this->_filename = (string)$this->_tempfile;
        $this->_cut = new Quick_Fifo_Header($this->_filename);
    }

    public function tearDown( ) {
        unset($this->_tempfile);
    }

    public function testHeaderShouldReturnDefaultsFromEmptyFile( ) {
        $info = $this->_cut->loadState();
        $this->assertEquals('1.0', $info['FIFO']);
        $this->assertEquals('100', $info['LEN']);
    }

    /**
     * @expectedException       Quick_Fifo_Exception
     */
    public function testBadHeaderStringShouldThrowException( ) {
        file_put_contents($this->_filename, 'invalid header string');
        $this->_cut->loadState();
    }

    /**
     * @expectedException       Quick_Fifo_Exception
     */
    public function testWriteProtectedShouldThrowException( ) {
        chmod($this->_filename, 0444);
        $this->_cut->saveState();
    }

    /**
     * @expectedException       Quick_Fifo_Exception
     */
    public function testOpenErrorShouldthrowException( ) {
        $cut = new Quick_Fifo_Header("/nonesuch");
        $cut->loadState();
    }

    public function testGetStateShouldReturnState( ) {
        $state1 = $this->_cut->getState();
        $this->_cut->setState('id', $id = uniqid());
        $state2 = $this->_cut->getState();
        $this->assertEquals(array('id' => $id), array_diff($state2, $state1));
    }

    public function testLoadStateShouldReadJsonHeaderFromFile( ) {
        $state = array('FIFO' => '1.0', 'HEAD' => 111111, 'other' => uniqid(), 'LEN' => 123);
        file_put_contents($this->_filename, str_pad(json_encode($state), 122, ' ')."\ndata line 1\ndata line 2\n");
        $this->assertEquals($state, $this->_cut->loadState());
    }

    public function testLoadStateShouldReturnAutoDetectedHeaderLength( ) {
        $state = array('FIFO' => '1.0', 'HEAD' => 111111, 'other' => uniqid(), 'LEN' => 555);
        file_put_contents($this->_filename, str_pad(json_encode($state), 99, ' ')."\n");
        $state2 = $this->_cut->loadState();
        $this->assertEquals(100, $state2['LEN']);
    }

    public function testSaveStateShouldWriteHeader( ) {
        $this->_cut->setState('tag', uniqid());
        $this->_cut->saveState();
        $hdr = new Quick_Fifo_Header($this->_filename);
        $this->assertEquals($this->_cut->getState(), $hdr->loadState());
    }

    public function testShouldAnnotateStateWithPassedArguments( ) {
        $id = uniqid();
        $this->_cut->saveState(array('stuff' => $id));
        $this->assertEquals($id, $this->_readHeaderAsObject()->stuff);
    }

    public function testAcquireShouldLockTheFile( ) {
        $this->_cut->acquire();
        $cmd = TEST_ROOT . "/../bin/flock";
        $this->assertFalse($this->_tryToLockFile($this->_filename));
    }

    public function testReleaseShouldUnlockTheFile( ) {
        $this->_cut->acquire();
        $this->_cut->release();
        $this->assertTrue($this->_tryToLockFile($this->_filename));
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        echo "\n";
        $timer->calibrate(10000, array($this, '_testSpeedNoop'), array());
        echo $timer->timeit(10000, "saveState", array($this, '_testSpeedSaveState'), array());
        echo $timer->timeit(10000, "loadState", array($this, '_testSpeedLoadState'), array());
        // 118k/sec save, 107k/sec load
    }

    public function _testSpeedNoop( ) {
    }

    public function _testSpeedSaveState( ) {
        $this->_cut->saveState();
    }

    public function _testSpeedLoadState( ) {
        $this->_cut->loadState();
    }

    protected function _readHeaderAsObject( ) {
        $hdr = new Quick_Fifo_Header($this->_filename);
        return (object)$hdr->loadState();
    }

    protected function _tryToLockFile( $filename ) {
        // flock() always succeeds from inside the same php process, so try the lock from another process
        exec(TEST_ROOT . "/../bin/flock $filename", $output, $status);
        return $status == 0 ? true : false;
    }
}
