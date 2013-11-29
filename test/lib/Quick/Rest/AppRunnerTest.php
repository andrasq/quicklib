<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_AppRunnerTest_EchoController
    implements Quick_Rest_Controller
{
    public function echoAction( Quick_Rest_Request $request, Quick_Rest_Response $response ) {
        foreach ($request->getParams() as $k => $v) $response->setValue($k, $v);
    }
}

class Quick_Rest_AppRunnerTest
    extends Quick_Test_Case
    implements Quick_Rest_Controller
{
    public function setUp( ) {
        $this->_cut = new Quick_Rest_AppRunner();
    }

    public function testSetConfigShouldSaveConfigAndGetConfigShouldRetrieveConfig( ) {
        $this->_cut->setConfig('a', $u1 = uniqid());
        $this->_cut->setConfig('b', $u2 = uniqid());
        $this->assertEquals($u1, $this->_cut->getConfig('a'));
        $this->assertEquals($u2, $this->_cut->getConfig('b'));
        $this->assertEquals(null, $this->_cut->getConfig('c'));
    }

    public function testPeekInstanceShouldReturnNullIfNotDefined( ) {
        $this->assertNull($this->_cut->peekInstance('nonesuch'));
    }

    /**
     * @expectedException       Quick_Rest_Exception
     */
    public function testGetInstanceShouldThrowExceptionIfNotDefined( ) {
        $this->_cut->getInstance('nonesuch');
    }

    public function testGetInstanceShouldReturnDefinedInstance( ) {
        $this->_cut->setInstance('type1', $id1 = microtime(true));
        $this->_cut->setInstance('type2', $id2 = uniqid());
        $this->assertEquals($id2, $this->_cut->getInstance('type2'));
    }

    public function testGetInstanceShouldBuildInstance( ) {
        $type = uniqid();
        $callback = $this->getMock('StdClass', array('getpid'));
        $callback->expects($this->once())->method('getpid')->will($this->returnValue(getmypid()));
        $this->_cut->setInstanceBuilder($type, array($callback, 'getpid'));
        $this->assertEquals(getmypid(), $this->_cut->getInstance($type));
    }

    /**
     * @expectedException       Quick_Rest_Exception
     */
    public function testRunRequestShouldThrowExceptionIfPathNotRouted( ) {
        $this->_cut->routeCall('GET', '/foo', 'Quick_Rest_AppRunnerTest_EchoController::echoAction');
        $request = $this->_makeRequest('GET', '/bar', array(), array());
        $this->_cut->runCall($request, $this->_makeResponse());
    }

    /**
     * @expectedException       Quick_Rest_Exception
     */
    public function testSetRouteShouldTrowExceptionIfCallbackNotCallable( ) {
        $this->_cut->setRoute('GET://call/name', 123);
    }

    public function testRunRequestShouldInvokeRoutedCallback( ) {
        $id = uniqid();
        $request = $this->_makeRequest('POST', '/call/path', array('a' => 1), array('b' => $id));

        $this->_cut->routeCall('GET|POST', "/call/path", 'Quick_Rest_AppRunnerTest_EchoController::echoAction');
        $response = $this->_cut->runCall($request, $this->_makeResponse());
        $this->assertContains($id, $response->getResponse());
    }

    public function testRunRequestShouldInvokeProperCallbacks( ) {
        $runner = $this->_makeRunner($calls = array('a', 'b', 'c'));
        foreach ($calls as $call)
            $this->_cut->routeCall('GET', "/call/$call", array($runner, $call));
        foreach ($calls as $call) {
            $request = $this->_makeRequest('GET', "/call/$call", array(), array());
            $this->_cut->runCall($request, $this->_makeResponse());
        }
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(10000, array($this, '_testNullSpeed'), array(1, 2));
        echo $timer->timeit(10000, 'empty call', array($this, '_testNullSpeed'), array(1, 2));
        echo $timer->timeit(10000, 'create', array($this, '_testCreateSpeed'), array(1, 2));
        $cut = new Quick_Rest_AppRunner();
        for ($call = 'aa'; $call < 'ac'; $call++) $routes["GET::/$call"] = "class::$call";
        $cut->setRoutes($routes);
        echo $timer->timeit(1000, 'set routes(5)', array($this, '_testRoutesSpeed'), array($cut, & $routes));
        // 560k/s for 2, 390k/s for 4, 136k/s for 16, 34k/s for 77, 4k/s for 675
        // w/o is_callable test 1.75m/sec for 675!! (by ref, or 7.7k/s by value overwrite, 11k/s first assign)
        // assigning an array is linear in the number of elements ?? ...even if assigned by reference ??
        $globals = $this->_makeGlobalsForCall('GET', '/call/name', array(), array());
        $request = $this->_makeRequest('GET', '/call/name', array(), array());
        // 640k/s
        $cut->routeCall('GET', '/call/name', 'Quick_Rest_AppRunnerTest_EchoController::echoAction');
        echo $timer->timeit(10000, 'route to string w/ request', array($this, '_testRunRequestSpeed'), array($cut, $request));
        // 140k/s
        echo $timer->timeit(10000, 'route to string w/ globals', array($this, '_testRunCallSpeed'), array($cut, $globals));
        // 90k/s
        $cut->routeCall('GET', '/call/name', array(new Quick_Rest_AppRunnerTest_EchoController(), 'echoAction'));
        echo $timer->timeit(10000, 'route to callback w/ globals', array($this, '_testRunCallSpeed'), array($cut, $globals));
        // 101k/s
        echo $timer->timeit(20000, 'handle page hit', array($this, '_testHandleHitSpeed'), array($cut, $globals));
        // 65k/s
    }

    public function _testNullSpeed( $x, $y ) {
    }

    public function _testRoutesSpeed( $cut, & $routes ) {
        $cut->setRoutes($routes);
    }

    public function _testCreateSpeed( $x, $y ) {
        $new = new Quick_Rest_AppRunner();
    }

    public function _testRunRequestSpeed( $cut, $request ) {
        $cut->runCall($request, new Quick_Rest_Response_Http());
    }

    public function _testRunCallSpeed( $cut, & $globals ) {
        $request = new Quick_Rest_Request_Http();
        $request->setParamsFromGlobals($globals);
        $response = new Quick_Rest_Response_Http();
        $cut->runCall($request, $response);
    }

    public function _testHandleHitSpeed( $cut, & $globals ) {
        $cut = new Quick_Rest_AppRunner();
        $request = new Quick_Rest_Request_Http();
        $request->setParamsFromGlobals($globals);
        $response = new Quick_Rest_Response_Http();
        //$cut->routeCall('GET', '/call/name', 'Quick_Rest_AppRunnerTest_EchoController::echoAction');
        $cut->routeCall('GET', $request->getPath(), 'Quick_Rest_AppRunnerTest_EchoController::echoAction');
        $cut->runCall($request, $response);
    }

    protected function _makeGlobalsForCall( $method, $uri, Array $getargs, Array $postargs ) {
        return $globals = array(
            '_GET' => $getargs,
            '_POST' => $postargs,
            '_SERVER' => array(
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'REQUEST_METHOD' => strtoupper($method),
                'REQUEST_URI' => $uri,
            ),
        );
    }

    protected function _makeRequest( $method, $uri, Array $getargs, Array $postargs ) {
        $request = new Quick_Rest_Request_Http();
        $globals = $this->_makeGlobalsForCall($method, $uri, $getargs, $postargs);
        $request->setParamsFromGlobals($globals);
        return $request;
    }

    protected function _makeResponse( ) {
        return new Quick_Rest_Response_Http();
    }

    protected function _makeRunner( Array $calls ) {
        $runner = $this->getMock('Quick_Rest_Controller', $calls);
        foreach ($calls as $call)
            $runner->expects($this->once())->method($call);
        return $runner;
    }
}
