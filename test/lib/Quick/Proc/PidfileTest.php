<?

class Quick_Proc_PidfileTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_tempfile = new Quick_Test_Tempfile();
        $this->_filename = (string)$this->_tempfile;
        $this->_cut = new Quick_Proc_Pidfile($this->_filename);
    }

    public function tearDown( ) {
        unset($this->_tempfile);
        @unlink($this->_filename);
    }

    public function testAcquireShouldStorePid( ) {
        $ok = $this->_cut->acquire();
        $this->assertTrue($ok);
        $this->assertEquals((string)getmypid(), trim($this->_tempfile->getContents()));
    }

    public function testReleaseShouldRemovePidfile( ) {
        $this->_cut->acquire();
        $this->_cut->release();
        $this->assertEquals("", trim(@$this->_tempfile->getContents()));
    }

    public function testAcquireWithPidShouldSaveSetThatValue( ) {
        $this->_cut->acquire(12345);
        $this->assertEquals(12345, trim($this->_tempfile->getContents()));
    }

    public function testAcquireShouldAllowLockingWithSamePid( ) {
        $this->_cut->acquire(1);
        $this->assertTrue($this->_cut->acquire(1));
    }

    public function testAcquireShouldOverrideLockIfProcessDoesNotExist( ) {
        $this->_cut->acquire(0);
        $this->assertTrue($this->_cut->acquire(1));
    }

    /**
     * @expectedException       Quick_Proc_Exception
     */
    public function testAcquireShouldThrowExceptionIfAlreadyHasPidSetToSomethingDifferent( ) {
        $this->_cut->acquire(1);
        $this->assertTrue($this->_cut->acquire(1));
        $this->_cut->acquire(2);
    }

    public function testAcquireShouldCreateTheFileIfNotExists( ) {
        unlink($this->_filename);
        $ok = $this->_cut->acquire();
        clearstatcache();
        $this->assertTrue(file_exists($this->_filename));
        $this->assertEquals(getmypid(), (int)$this->_tempfile->getContents());
    }

    /**
     * @expectedException       Quick_Proc_Exception
     */
    public function testAcquireShouldThrowExceptionIfUnableToCreateFile( ) {
        $cut = new Quick_Proc_Pidfile("/nonesuch/test.pid");
        $cut->acquire();
    }
}
