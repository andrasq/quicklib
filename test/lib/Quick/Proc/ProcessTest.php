<?

class Quick_Proc_ProcessTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Proc_Process("/bin/sh");
    }

    public function tearDown( ) {
        unset($this->_cut);
    }

    public function testOpenShouldStartProcess( ) {
        $cut = new Quick_Proc_Process("exec sleep 12345", false);
        $cut->open();
        $pid = $cut->getPid();
        $this->assertGreaterThan(0, $this->_cut->getPid());
        $this->assertTrue($this->_linuxProcessExists($pid, '12345'));
    }

    public function testCloseShouldWaitForProcessToFinish( ) {
        $tm = microtime(true);
        $cut = new Quick_Proc_Process("usleep 12345");
        $cut->close();
        $tm = microtime(true) - $tm;
        $pid = $cut->getPid();
        $this->assertFalse($this->_linuxProcessExists($pid, '12345'));
        $this->assertGreaterThan(.0123, $tm);
    }

    public function testDestructorShouldKillProcess( ) {
        $cut = new Quick_Proc_Process("sleep 12345");
        $pid = $cut->getPid();
        unset($cut);
        $this->assertFalse($this->_linuxProcessExists($pid, '12345'));
    }

    public function testWaitShouldWaitForProcessToExit( ) {
        $cut = new Quick_Proc_Process("usleep 20000");
        $tm = microtime(true);
        $cut->wait(1);
        $tm = microtime(true) - $tm;
        $this->assertLessThan(1, $tm);
    }

    public function testIsRunningShouldReturnTrueOnceProcessStarted( ) {
        $cut = new Quick_Proc_Process("sleep 5", false);
        $this->assertFalse($cut->isRunning());
    }

    public function testIsRunningShouldReturnFalseOnceProcessExited( ) {
        $this->assertTrue($this->_cut->isRunning());
        $this->_cut->close();
        $this->assertFalse($this->_cut->isRunning());
    }

    public function testPutInputWritesStdin( ) {
        $this->_cut->putInput("exit\n");
        $this->_cut->wait(.2);
        $this->assertFalse($this->_cut->isRunning());
    }

    public function testGetOutputLineShouldReadStdout( ) {
        $this->_cut->putInput("echo line 1\necho line 2\n");
        $this->assertEquals("line 1\n", $this->_cut->getOutputLine(.02));
        $this->assertEquals("line 2\n", $this->_cut->getOutputLine(.02));
        $this->assertEquals(false, $this->_cut->getOutputLine(.02));
    }

    public function testGetErrorLineShouldReadStderr( ) {
        $this->_cut->putInput("echo line 1 >&2\necho line 2 >&2\n");
        $this->assertEquals("line 1\n", $this->_cut->getErrorLine(.02));
        $this->assertEquals("line 2\n", $this->_cut->getErrorLine(.02));
        $this->assertEquals(false, $this->_cut->getErrorLine(.02));
    }

    public function testGetOutputLineShouldReturnFalseIfNoLine( ) {
        $this->assertFalse($this->_cut->getOutputLine());
    }

    public function testGetErrorLineShouldReturnFalseIfNoLine( ) {
        $this->assertFalse($this->_cut->getErrorLine());
    }

    public function testGetOutputLinesShouldReturnEmptyArrayIfNoLine( ) {
        $this->assertEquals(array(), $this->_cut->getOutputLines(2));
    }

    public function testGetErrorLinesShouldReturnEmptyArrayIfNoLine( ) {
        $this->assertEquals(array(), $this->_cut->getErrorLines(2));
    }

    public function testGetOutputLinesShouldReturnNLinesFromStdout( ) {
        $lines = array();
        $cut = new Quick_Proc_Process("cat /etc/passwd");
        for ($i=1; $i<=3; $i++) {
            $more = $cut->getOutputLines($i, .01);
            $this->assertEquals($i, count($more));
            $lines = array_merge($lines, $more);
        }
        $this->assertEquals(array_slice(file("/etc/passwd"), 0, 6), $lines);
    }

    public function testGetOutputLinesShouldReturnTheAvailableLinesNotWaitForMore( ) {
        $cut = new Quick_Proc_Process("cat /etc/passwd");
        $tm = microtime(true);
        $lines = $cut->getOutputLines(999999, 2);
        $tm = microtime(true) - $tm;
        $this->assertLessThan(1, $tm);
        $this->assertEquals(file("/etc/passwd"), $lines);
    }

    public function testGetErrorLinesShouldReturnTheAvailableLinesNotWaitForMore( ) {
        $cut = new Quick_Proc_Process("cat /etc/passwd 1>&2");
        $tm = microtime(true);
        $lines = $cut->getErrorLines(999999, 2);
        $tm = microtime(true) - $tm;
        $this->assertLessThan(1, $tm);
        $this->assertEquals(file("/etc/passwd"), $lines);
    }

    public function testGetErrorLinesShouldReturnNLinesFromStdout( ) {
        $lines = array();
        $cut = new Quick_Proc_Process("cat /etc/passwd 1>&2");
        for ($i=1; $i<=3; $i++) {
            $more = $cut->getErrorLines($i, .01);
            $this->assertEquals($i, count($more));
            $lines = array_merge($lines, $more);
        }
        $more = $cut->getErrorLines(999999, .01);
        $lines = array_merge($lines, $more);
        $this->assertEquals(file_get_contents("/etc/passwd"), implode('', $lines));
    }

    public function testGetExitcodeReturnsNullIfProcessIsRunning( ) {
        $cut = new Quick_Proc_Process("sleep 10");
        $this->assertNull($cut->getExitcode());
    }

    public function testCloseShouldCaptureProcessExitcode( ) {
        $cut = new Quick_Proc_Process("/bin/sh -c 'exit 212'");
        $cut->wait(.5);
        $cut->close();
        $this->assertEquals(212, $cut->getExitcode());
    }

    public function testGetPidShouldReturnProcessPid( ) {
        $cut = new Quick_Proc_Process("exec sleep 10");
        $pid = $cut->getPid();
        $this->assertTrue($this->_linuxProcessExists($pid, 'sleep'));
    }

    public function testKillShouldKillProcess( ) {
        $cut = new Quick_Proc_Process("sleep 100");
        $cut->kill();
        $cut->wait(.01);
        $this->assertFalse($cut->isRunning());
    }

    public function testProcessShouldReadCommand( ) {
        $this->_cut->putInput("echo line 1\n");
        $this->assertEquals("line 1\n", $this->_cut->getOutputLine(.1));
    }


    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $cut = new Quick_Proc_Process("cat");
        $timer->calibrate(20000, array($this, '_testSpeedNoop'), array($cut));
        echo $timer->timeit(20000, "round-trip cat lines", array($this, '_testSpeedPutGet'), array($cut));
        // 74k lines / sec (75k tested standalone)
    }

    public function _testSpeedNoop( $cut ) {
    }

    public function _testSpeedPutGet( $cut ) {
        $cut->fputs("line line\n");
        $line = $cut->fgets(.2);
    }

    protected function _linuxProcessExists( $pid, $contains ) {
        // race condition: wait for child process to start, else might not see it yet
        // on linux-2.6.32-48 (php-5.4?) a short usleep is not enough to cause the child process to run
        usleep(20000);

        if (php_uname('s') !== 'Linux') {
            $pslist = `/bin/ps uxwww 2>/dev/null`;
            $whoami = trim(`whoami`);
            // FIXME: multi-line preg match syntax ??
            return preg_match("/^$whoami\s+$pid.*\s$contains\s/m", $pslist) ? true : false;
        }

        clearstatcache();
        if (!is_dir("/proc/$pid")) {
            return false;
        }

        $cmd = file_get_contents("/proc/$pid/cmdline");
        if (strpos($cmd, $contains) === false) {
            return false;
        }
        return true;
    }
}
