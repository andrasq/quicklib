<?

/**
 * Copyright (C) 2013-2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Call_HttpTest
    extends Quick_Test_Case
{
    static $echoProc;

    static public function setUpBeforeClass( ) {
        self::$echoProc = new Quick_Proc_Process(TEST_ROOT . '/../bin/socket_echo 12345');
        // wait until process starts and opens the listener socket
        // this delay is about the same as for "time php -v", as much as .15 sec, so reuse the proc for all tests
        usleep(150000);
    }

    static public function tearDownAfterClass( ) {
        self::$echoProc->kill()->close();
    }

    public function setUp( ) {
        $this->_cut = $this->_createCut();
        $this->_cut->setUrl('http://localhost:12345/path/to/page.php');
        $this->_cut->setMethod('POST', 'post=1&postargs=1');
        $this->_echoProc = self::$echoProc;
    }

    protected function _createCut( ) {
        return new Quick_Rest_Call_Http();
    }

    public function validUrlProvider( ) {
        return array(
            array("host/index.html"),
            array("host:80/index.html"),
            array("host/index.html#tab3?a=1&b=2"),
            array("http://host/page.php"),
        );
    }

    public function invalidUrlProvider( ) {
        return array(
            array(""),
            array("host:///"),          // empty hostname
        );
    }

    /**
     * @dataProvider            validUrlProvider
     */
    public function testSetUrlShouldParseUrl( $url ) {
        $cut = new Quick_Rest_Call_Http($url);
    }

    public function testSetParamShouldStoreParam( ) {
        $id = uniqid();
        $this->_cut->setParam('id', $id);
        $this->_cut->setParam($id, $id);
        $this->assertEquals(array('id' => $id, $id => $id), $this->_cut->getParam());
    }

    public function testGetParamShouldReturnNamedParam( ) {
        $id = uniqid();
        $this->_cut->setParam('id2', $id);
        $this->assertEquals($id, $this->_cut->getParam('id2'));
    }

    public function testCallShouldPassSetUrlToRemote( ) {
        $this->_cut->setUrl("http://localhost:12345/path/to/page.php?a=1");
        $message = $this->_runCall();
        $this->assertHeaderContainsString("/path/to/page.php?a=1", $message);
        $this->assertHeaderContainsString("localhost:12345", $message);
    }

    public function testCallShouldOnlyIncludeSetUrlParamsOnce( ) {
        $this->_cut->setUrl("http://localhost:12345/path/to/page.php?a=1");
        $message = $this->_runCall();
        $this->assertHeaderContainsString("/path/to/page.php?a=1", $message);
        $this->assertFalse(strpos($message, "a=1&"));
        $this->assertHeaderContainsString("localhost:12345", $message);
    }

    public function testCallShouldSendParamsToRemote( ) {
        $this->_cut->setParam('a', 1);
        $this->_cut->setParam('b', 2);
        $message = $this->_runCall();
        $this->assertHeaderContainsString("a=1&b=2", $message);
    }

    public function testSetMethodShouldSetMethodUpcasedAndSetMethodArgVerbatim( ) {
        $this->_cut->setMethod('Method', '__Arg__');
        $this->assertEquals('METHOD', $this->_cut->getMethod());
        $this->assertEquals('__Arg__', $this->_cut->getMethodArg());
    }

    public function testCallShouldSendMethodToRemote( ) {
        $message = $this->_runCall();
        $this->assertHeaderContainsString('POST', $message);
        $this->_cut->setMethod('Put', TEST_ROOT . "/phpunit");
        $this->_cut->setParam('filename', "phpunit");
        $message = $this->_runCall();
        $this->assertHeaderContainsString('PUT', $message);
    }

    public function testUploadShouldSendFileToRemote( ) {
        $this->_cut->setMethod('UPLOAD', TEST_ROOT . "/phpunit");
        $this->_cut->setParam('filename', "phpunit");
        $message = $this->_runCall();
        $this->assertContains("filename=phpunit", $message);
        $this->assertContains(file_get_contents(TEST_ROOT . "/phpunit"), $message);
    }

    public function testPostFileShouldSendFileToRemote( ) {
        $file = TEST_ROOT . "/phpunit";
        $this->_cut->setMethod('POSTFILE', $file);
        $message = $this->_runCall();
        $this->assertContains(file_get_contents($file), $message);
    }

    public function testPostFileShouldNotOverwriteHeaders( ) {
        $this->_cut->setHeader('Header1', 'one');
        $file = TEST_ROOT . "/phpunit";
        $this->_cut->setMethod('POSTFILE', $file);
        $message = $this->_runCall();
        $this->assertContains("\nHeader1: one\r\n", $message);
    }

    public function testPutFileShouldSendFileToRemoteAndSetContentLength( ) {
        $file = TEST_ROOT . "/phpunit";
        $this->_cut->setMethod('PUTFILE', $file);
        $message = $this->_runCall();
        $this->assertContains(file_get_contents($file), $message);
        $this->assertContains("\nContent-Length: " . strlen(file_get_contents($file)), $message);
    }

    public function testSetHeaderShouldSaveHeader( ) {
        $this->_cut->setHeader('Header1', 'one');
        $this->_cut->setHeader('Header2', 'two');
        $this->assertEquals(array('Header1' => 'one', 'Header2' => 'two'), $this->_cut->getHeaders());
    }

    public function testSetHeadersShouldOverwriteHeaders( ) {
        $this->_cut->setHeader('Header1', 'one');
        $this->_cut->setHeaders(array('Header2' => 'two', 'Header3' => 'three'));
        $this->assertEquals(array('Header2' => 'two', 'Header3' => 'three'), $this->_cut->getHeaders());
    }

    public function testGetHeaderShouldReturnSavedHeaderElseNull( ) {
        $this->_cut->setHeader('Header1', 'one');
        $this->assertEquals('one', $this->_cut->getHeader('Header1'));
        $this->assertEquals(null, $this->_cut->getHeader('Header2'));
    }

    public function testCallShouldSendHeadersToRemote( ) {
        $this->_cut->setHeader('Header1', 'line1');
        $this->_cut->setHeader('Header2', 'line2');
        $message = $this->_runCall();
        list($header, $body) = $this->_splitHeaderBody($message);
        $this->assertContains("Header1: line1\r", explode("\n", $header));
    }

    /**
     * @dataProvider            invalidUrlProvider
     * @expectedException       Quick_Rest_Exception
     */
    public function testSetUrlShouldThrowExceptionOnInvalidUrl( $url ) {
        $cut = new Quick_Rest_Call_Http($url);
    }

    public function testRequestShouldPassHeadersInQuery( ) {
        $this->_cut->setHeader('Header1', 111);
        $this->_cut->setHeader('Header2', 222);
        $message = $this->_runCall();
        $this->assertContains('Header1: 111', $message);
        $this->assertContains('Header2: 222', $message);
    }

    public function testGetReplyShouldReturnReplyWithHeaders( ) {
        $this->_cut->setParam('a', uniqid());
        $message = $this->_runCall();
        $this->assertEquals($this->_cut->getReply(), $message);
        $this->assertContains("path/to/page", $this->_cut->getReply());
    }

    public function testGetContentOffsetShouldReturnByteOffsetOfBody( ) {
        $this->_cut->setUrl("http://localhost/index.html");
        $reply = $this->_runCallHttp();
        $this->assertEquals(substr($reply, $this->_cut->getContentOffset()), `curl -s -k http://localhost/index.html`);
    }

    public function testGetContentLengthShouldReturnSizeOfBody( ) {
        $this->_cut->setUrl("http://localhost/index.html");
        $reply = $this->_runCallHttp();
        $this->assertEquals(substr($reply, -$this->_cut->getContentLength()), `curl -s -k http://localhost/index.html`);
    }

    /**
     * @expectedException       Quick_Rest_Exception
     */
    public function testGetContentFileShouldThrowExceptionIfTargetIsNotWritable( ) {
        $this->_runCall();
        $filename = new Quick_Test_Tempfile();
        chmod($filename, 0);
        $this->_cut->getContentFile($filename);
    }

    public function testGetContentFileShouldPlaceReplyBodyWithoutHeadersIntoTempfile( ) {
        $this->_cut->setUrl("http://localhost/index.html");
        $this->_cut->setMethod('GET', null);
        $this->_runCallHttp();
        $outfile = new Quick_Test_Tempfile();
        $this->_cut->getContentFile($outfile);
        $contents = file_get_contents($outfile);
        $this->assertNotContains("Content-Length:", $contents);
        $this->assertNotContains("HTTP/1:", $contents);
    }

    public function testGetRequestShouldPassArrayParametersInQuery( ) {
        $this->_cut->setParam('a', 11);
        $this->_cut->setParam('b', 22);
        $message = $this->_runCall();
        $this->assertHeaderContainsString("page.php?a=11&b=22", $message);
    }

    public function testPostRequestShouldPassArrayParametersInBody( ) {
        $this->_cut->setMethod('POST');
        $this->_cut->setParam('a', 11);
        $this->_cut->setParam('b', 22);
        $message = $this->_runCall();
        $this->assertBodyContainsLine("a=11&b=22", $message);
    }

    public function testPostRequestShouldPassStringParameterInBody( ) {
        $this->_cut->setMethod('POST', "a b c\n");
        $message = $this->_runCall();
        $this->assertBodyContainsLine("a b c", $message);
    }

    public function testCallDoesNotSetPublicAttributes( ) {
        $this->_cut->setUrl("http://host.com:8080/path/to/page.php?a=1&b=2#fragment-tag");
        $this->_cut->setParam('c', 3);
        $this->_cut->setMethod('GET');
        $this->assertEquals(array(), get_object_vars($this->_cut));
    }

    public function testSetProfilingShouldLogStats( ) {
        $this->_cut->setProfiling($datalogger = new Quick_Data_Datalogger_Array());
        $this->_cut->setParam('a', 123);
        $this->_cut->setParam('b', 456);
        $this->_runCall();
        $data = $datalogger->getData();
        $this->assertContains("a=123&b=456", $data[0]['url']);
        $this->assertGreaterThan(0.0, $data[0]['duration']);
    }

    public function testSetProfilingShouldLogEachCall( ) {
        $this->_cut->setProfiling($datalogger = new Quick_Data_Datalogger_Array());
        $this->_runCall();
        $this->_runCall();
        $this->assertEquals(2, count($datalogger->getData()));
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(1000, array($this, '_testSpeedNull'), array(null));
        echo $timer->timeit(10000, 'create', array($this, '_testSpeedCreate'), array(null));
        // 800k/s
        echo $timer->timeit(10000, 'setUrl', array($this, '_testSpeedSetUrl'), array("http://localhost:12345/path/to/page.php?a=1&b=2"));
        // 250k/s
    }

    public function _testSpeedNull( $url ) {
    }

    public function _testSpeedCreate( $url ) {
        $x = new Quick_Rest_Call_Http();
    }

    public function _testSpeedSetUrl( $url ) {
        $x = new Quick_Rest_Call_Http();
        $x->setUrl($url);
    }

    // make the http call and capture the message received by the remote
    protected function _runCall( ) {
        if (!function_exists('curl_init')) $this->markTestSkipped("curl not available, cannot test");
        $echo = $this->_echoProc;
        //$tempfile = new Quick_Test_Tempfile();
        // use nc if available... @FIXME: can`t get nc to work
        //if (file_exists("/bin/nc")) $echo = new Quick_Proc_Process("exec nc -l 12345 > $tempfile 2>&1 ; cat $tempfile");
        //if (file_exists("/bin/nc")) $echo = new Quick_Proc_Process("exec nc -l 12345 > /tmp/ar.out 2>&1 ; cat /tmp/ar.out");
        //usleep(150000);
        $this->_cut->call();
        $output = $echo->getOutput(1);
        return $output;
    }

    // make a real http call to the localhost web server
    protected function _runCallHttp( ) {
        $this->_cut->call();
        return $this->_cut->getReply();
    }

    protected function assertBodyContainsLine( $expect, $response ) {
        list($header, $body) = $this->_splitHeaderBody($response);
        $this->assertContains($expect, explode("\n", $body));
    }

    protected function assertHeaderContainsString( $expect, $response ) {
        list($header, $body) = $this->_splitHeaderBody($response);
        $this->assertContains($expect, $header);
    }

    protected function _splitHeaderBody( $response ) {
        $posn = array();
        foreach (array("\n\n", "\n\r\n") as $newline)
            if (($pos = strpos($response, $newline)) !== false) $posn[] = $pos + strlen($newline);
        if (!$posn) return array($response, "");
        else return array(substr($response, 0, min($posn)), substr($response, min($posn)));
    }
}
