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

    public function testRouteCallShouldAcceptCallbacksThatAppearValid( ) {
        // valid
        $this->_cut->routeCall('GET', '/path1', 'Quick_Rest_AppRunnerTest_EchoController::echoAction');
        $this->_cut->routeCall('GET', '/path2', 'function_exists');
        $this->_cut->routeCall('GET', '/path3', create_function('$a,$b,$c', 'return;'));
        // not valid, but syntactically appear to be
        $this->_cut->routeCall('GET', '/path4', array("hello", "world"));
        $this->_cut->routeCall('GET', '/path5', "Hello, world");
    }

    /**
     * @expectedException       Quick_Rest_Exception
     */
    public function testRunCallShouldThrowExceptionIfPathNotRouted( ) {
        $this->_cut->routeCall('GET', '/foo', 'Quick_Rest_AppRunnerTest_EchoController::echoAction');
        $request = $this->_makeRequest('GET', '/bar', array(), array());
        $this->_cut->runCall($request, $this->_makeResponse());
    }

    public function invalidCallbackProvider( ) {
        return array(
            array(123),
            array(array(1, 2)),
        );
    }

    /**
     * @expectedException       Quick_Rest_Exception
     * @dataProvider            invalidCallbackProvider
     */
    public function testRouteCallShouldTrowExceptionIfCallbackNotValid( $callback ) {
        $this->_cut->routeCall('GET', '/call/name', $callback);
    }

    public function testRunCallShouldInvokeDoublecolonStringCallback( ) {
        $this->_cut->routeCall('GET|POST|OTHER', "/call/path", 'Quick_Rest_AppRunnerTest_EchoController::echoAction');
        foreach (array('GET', 'POST', 'OTHER') as $method) {
            $id = uniqid();
            $request = $this->_makeRequest($method, '/call/path', array('a' => 1), array('b' => $id));
            $response = $this->_cut->runCall($request, $this->_makeResponse());
            $this->assertContains($id, $response->getResponse());
        }
    }

    public function testRunCallShouldInvokeArrayCallbacks( ) {
        $runner = $this->_makeRunner($calls = array('a', 'b', 'c'));
        foreach ($calls as $call)
            $this->_cut->routeCall('GET', "/call/$call", array($runner, $call));
        foreach ($calls as $call) {
            $request = $this->_makeRequest('GET', "/call/$call", array(), array());
            $this->_cut->runCall($request, $this->_makeResponse());
        }
    }

    public function testRunCallShouldInvokeAnonymousFunctionCallbacks( ) {
        if (version_compare(phpversion(), "5.3.0") < 0)
            $this->markTestSkipped();
        $runner = $this->_makeRunner($calls = array('a', 'b', 'c'));
        foreach ($calls as $call)
            $this->_cut->routeCall('GET', "/call/$call", function ($req, $resp, $app) use ($runner) {
                $path = $req->getPath();
                $method = substr($path, strrpos($req->getPath(), '/')+1);
                $runner->$method($req, $resp, $app);
            });
        foreach ($calls as $call) {
            $request = $this->_makeRequest('GET', "/call/$call", array(), array());
            $this->_cut->runCall($request, $this->_makeResponse());
        }
    }

    public function testRunCallShouldInvokeSetCallInsteadOfPath( ) {
        $runner = $this->_makeRunner($calls = array('a'));
        $this->_cut->routeCall('ALL', 'CALLS', array($runner, 'a'));
        $this->_cut->setCall('ALL::CALLS');
        $request = $this->_makeRequest('GET', '/call/foo/bar', array(), array());
        $this->_cut->runCall($request, $this->_makeResponse());
    }

    public function testRunCallShouldPassUserAppToActionMethod( ) {
        $app = $this->getMock('Quick_Rest_App', array('getInstance'));
        $controller = $this->getMock('Quick_Rest_Controller', array('testAction'));
        //$this->_cut->routeCall('GET', '/test', array($controller, 'testAction'));
        $routes = array(
            'GET::/test' => array($controller, 'testAction')
        );
        $this->_cut->setRoutes($routes);
        $request = $this->_makeRequest('GET', '/test', array(), array());
        $response = $this->_makeResponse();
        $controller->expects($this->once())->method('testAction')->with($request, $response, $app);
        $this->_cut->runCall($request, $response, $app);
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(10000, array($this, '_testNullSpeed'), array(1, 2));
        echo $timer->timeit(20000, 'empty call', array($this, '_testNullSpeed'), array(1, 2));
        echo $timer->timeit(20000, 'create', array($this, '_testCreateSpeed'), array(1, 2));
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
        echo $timer->timeit(20000, 'route to string w/ request', array($this, '_testRunCallSpeed'), array($cut, $request));
        // 140k/s
        echo $timer->timeit(20000, 'route to string w/ globals', array($this, '_testRunCallSpeedGlobals'), array($cut, $globals));
        // 90k/s
        $cut->routeCall('GET', '/call/name', array(new Quick_Rest_AppRunnerTest_EchoController(), 'echoAction'));
        echo $timer->timeit(20000, 'route to callback w/ globals', array($this, '_testRunCallSpeedGlobals'), array($cut, $globals));
        // 101k/s
        echo $timer->timeit(20000, 'oneshot, handle page hit', array($this, '_testOneshotPageHitSpeed'), array($cut, $globals));
        // 65k/s (85k/s on amd 3.6 GHz)
        // NOTE: measuring inside apache to capture apc autoloading delays, more like ~1k (cold) to 4k/s (looped)
    }

    public function _testNullSpeed( $x, $y ) {
    }

    public function _testRoutesSpeed( $cut, & $routes ) {
        $cut->setRoutes($routes);
    }

    public function _testCreateSpeed( $x, $y ) {
        $new = new Quick_Rest_AppRunner();
    }

    public function _testRunCallSpeed( $cut, $request ) {
        $cut->runCall($request, new Quick_Rest_Response_Http());
    }

    public function _testRunCallSpeedGlobals( $cut, & $globals ) {
        $request = new Quick_Rest_Request_Http();
        $request->setParamsFromGlobals($globals);
        $response = new Quick_Rest_Response_Http();
        $cut->runCall($request, $response);
    }

    public function _testOneshotPageHitSpeed( $cut, & $globals ) {
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
