<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Logger_FileTestBase
    extends Quick_Test_Case
{
    public function setUp( ) {
	$this->_tempfile = new Quick_Test_Tempfile();
	$this->_filename = (string)$this->_tempfile;
	$this->_cut = $this->_createClassUnderTest();
    }

    public function tearDown( ) {
	unset($this->_tempfile);
    }

    protected function _createClassUnderTest( ) {
	return new Quick_Logger_File($this->_filename);
    }

    public function testLinesShouldHaveNewlineSuppliedIfNecessary( ) {
	$this->_cut->info("line 1");
	$this->_cut->info("line 2\n");
	$this->assertEquals("line 1\nline 2\n", file_get_contents($this->_filename));
    }

    public function testInfoLevelShouldOmitDebugMessages( ) {
	$cut = new Quick_Logger_File($this->_filename, Quick_Logger::INFO);
	$cut->debug("line 1");
	$cut->info("line 2");
	$cut->err("line 3");
	$this->assertEquals("line 2\nline 3\n", file_get_contents($this->_filename));
    }

    public function testErrLevelShouldOmitInfoMessages( ) {
	$cut = new Quick_Logger_File($this->_filename, Quick_Logger::ERR);
	$cut->debug("line 1");
	$cut->info("line 2");
	$cut->err("line 3");
	$this->assertEquals("line 3\n", file_get_contents($this->_filename));
    }

    public function testShouldApplyFilters( ) {
	$line = "line 1";
	$filter = $this->getMock('Quick_Logger_Filter_Basic', array('filterLogline'));
	$filter->expects($this->once())->method('filterLogline')
            ->with($line)->will($this->returnValue("filtered line 1"));
	$this->_cut->addFilter($filter);
	$this->_cut->info($line);
	$contents = file_get_contents($this->_filename);
	$this->assertContains("filtered", $contents);
	$this->assertContains($line, $contents);
    }

    public function xx_testSpeed( ) {
	$timer = new Quick_Test_Timer();
	$nloops = 10000;

        // note: php writes 1.6m lines/sec to /dev/null, and 320k/s to a file.
        // Class hierarchy overhead eats 56%, to 205k/sec, and
        // testing the reopen time eats another 46%, to 140k/sec.
        // newline testing and success checking eats another 9%, to 129k/sec
        // file_put_contents(FILE_APPEND) is only 81k/sec

        $str = str_repeat('x', 199) . "\n";
        //$str = "line 1\n";

	$timer->calibrate($nloops, array($this, '_testSpeedNull'), array("parameter", $str));
        echo $timer->timeit($nloops, "append to file", array($this, '_testSpeedAppendToFile'), array($this->_filename, $str));
        // 105k / sec short lines file_put_contents() appended

	foreach (array(
	    //'file' => new Quick_Logger_File($this->_filename),
	    //'null' => new Quick_Logger_Null(),
	    'atomic' => new Quick_Logger_FileAtomic($this->_filename),
            'buffered' => new Quick_Logger_FileAtomicBuffered($this->_filename),
            //'syslog' => new Quick_Logger_Syslog(),
            //'dgram' => new Quick_Logger_Datagram('t60', 8888),
	) as $name => $cut)
	{
	    echo $timer->timeit($nloops, "$name.info()", array($this, '_testSpeedAppendWithObject'), array($cut, $str));
	    // 130k/sec file, 105k/sec atomic, 235k/s buffered
            // 12k/s syslog, 19k/s dgram (WARNING: some rsyslogd rate-limit syslog after 200 msgs)
            // (80k/sec file w/ file_put_contents( , , FILE_APPEND | LOCK_EX))

            $cut2 = clone($cut);
	    $cut2->addFilter(new Quick_Logger_Filter_Basic());
	    echo $timer->timeit($nloops, "$name.info(f.basic)", array($this, '_testSpeedAppendWithObject'), array($cut2, $str));
	    // 73k/sec w/ basic filter, 66k/sec atomic w/ basic, 98k/s buffered
            // 11k/s syslog, 17k/s dgram

            $template1 = array(
                // the barebones template provides these fields, in this order:
                //'timestamp' => true,
                //'level' => true,
                //'message' => true,
            );
            $template2 = array(
                'timestamp' => true,
                'duration' => true,
                'level' => true,
                'host' => 'localhost',
                'message' => true,
                'request' => array(
                    'starttime' => date('Y-m-d H:i:s T'),
                    'UNIQUE_ID' => uniqid(),
                    //'HTTP_USER_AGENT' => '',
                    'HTTP_HOST' => 'localhost',
                    'SERVER_NAME' => 'localhost',
                    'REMOTE_ADDR' => '127.0.0.1',
                    'REQUEST_METHOD' => 'GET/POST',
                    'REQUEST_URI' => 'BasicJsonTest',
                ),
                'message' => true,
            );
            $cut2 = clone($cut);
            $cut2->addFilter(new Quick_Logger_Filter_BasicJson(($barebones = true) ? $template1 : $template2));
	    echo $timer->timeit($nloops, "$name.info(f.jsonbasic)", array($this, '_testSpeedAppendWithObject'), array($cut2, $str));
	    // 45% speed of basic w/ summary, 40% w/ +duration, 60% if barebones (FileAtomic)
	}
    }

    public function _testSpeedNull( $arg, $str ) {
    }

    public function _testSpeedAppendToFile( $filename, $str ) {
        file_put_contents($filename, $str, FILE_APPEND);
    }

    public function _testSpeedAppendWithObject( $cut, $str ) {
	$cut->info($str);
    }
}
