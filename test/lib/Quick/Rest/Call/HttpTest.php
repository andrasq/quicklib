<?

class Quick_Rest_Call_HttpExposer
    extends Quick_Rest_Call_Http
{
    public function getMethod( ) {
        return $this->_method;
    }

    public function getMethodArg( ) {
        return $this->_methodArg;
    }

    public function getHeaders( ) {
        return $this->_headers;
    }

    public function getParams( ) {
        return $this->_params;
    }

    public function getUrl( ) {
        return $this->_url;
    }

    public function _appendParamsToUrl( $url, $params ) {
        return parent::_appendParamsToUrl($url, $params);
    }
}

class Quick_Rest_Call_HttpTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Rest_Call_HttpExposer();
        $this->_cut->setUrl('http://localhost:12345/path/to/page.php');
        $this->_cut->setMethod('GET');
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
        $this->assertHeaderContainsString('GET', $message);
        $this->_cut->setMethod('Put', TEST_ROOT . "/../bin/socket_echo");
        $this->_cut->setParam('filename', "socket_echo");
        $message = $this->_runCall();
        $this->assertHeaderContainsString('PUT', $message);
    }

    public function testUploadShouldSendFileToRemote( ) {
        //$this->_cut->setUrl("http://aradics.com/server_vars.php");
        $this->_cut->setMethod('UPLOAD', TEST_ROOT . "/../bin/socket_echo");
        $this->_cut->setParam('filename', "socket_echo");
        $message = $this->_runCall();
        //$this->assertBodyContainsLine('$stream = acceptconn($sock = opensock($_port), $_timeout);', $message);
        $this->assertBodyContainsLine(" * socket_echo -- echo the message received on the socket back to the sender", $message);
    }

    public function testSetHeaderShouldSaveHeader( ) {
        $this->_cut->setHeader('Header1', 'one');
        $this->_cut->setHeader('Header2', 'two');
        $this->assertEquals(array('Header1' => 'one', 'Header2' => 'two'), $this->_cut->getHeaders());
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
        $echo = new Quick_Proc_Process(TEST_ROOT . '/../bin/socket_echo 12345');
        // wait until process starts and opens the listener socket
        usleep(150000);
        $reply = $this->_cut->call();
        usleep(40000);
        $output = $echo->getOutput(1);
        $echo->close();
        return $output;
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
