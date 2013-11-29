<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Request_HttpTest
    extends Quick_Test_Case
{
    public function testMissingParamReturnsNull( ) {
        $cut = new Quick_Rest_Request_Http();
        $this->assertEquals(null, $cut->getParam('nonesuch'));
    }

    public function testGetParamsReturnsAllParams( ) {
        $cut = $this->_makeRequestWithParams(
            $a = array('a' => 1, 'b' => 2),
            $b = array('aa' => 11, 'bb' => '22'),
            $c = array('aaa' => 'a', 'bbb' => 'b')
        );
        $expect = $a + $b + $c;
        foreach ($expect as $k => $v) $this->assertEquals($v, $cut->getParam($k));
        $this->assertEquals($expect, $cut->getParams());
    }

    public function testGetRequestQueryStringReturnsGetQueryParams( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 1, 'b' => 2), array('c' => 3), array());
        $this->assertEquals('a=1&b=2', $cut->getRequestQueryString());
    }

    public function testGetCombinedQueryReturnsGetAndPostQueryParams( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 1, 'b' => 2), array('c' => 3), array());
        $this->assertEquals('a=1&b=2&c=3', $cut->getCombinedQueryString());
    }

    public function testGetPostBodyShouldReturnJustPostQueryString( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 1, 'b' => 2), array('c' => 3), array());
        $this->assertEquals('c=3', $cut->getPostBody());
    }

    public function testPostBodyShouldReturnArbitraryPostContent( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 1, 'b' => 2), 'Post-Body-String', array());
        $this->assertEquals('Post-Body-String', $cut->getPostBody());
    }

    public function testGetUploadFilepathsShouldReturnFiles( ) {
        $get = $post = $path = array();
        $globals = $this->_createGlobals($get, $post, $path);
        $this->_setGlobalsFile($globals, 'file1', '/tmp/file1_tmp');
        $this->_setGlobalsFile($globals, 'file2', '/tmp/file2_tmp');
        $cut = new Quick_Rest_Request_Http();
        $cut->setParamsFromGlobals($globals);
        $expect = array('file1' => '/tmp/file1_tmp', 'file2' => '/tmp/file2_tmp');
        $this->assertEquals($expect, $cut->getUploadFilepaths());
    }

    public function testGetUploadFilenameShouldReturnNamedFileOrFalse( ) {
    }

    public function testGetCombinedQueryReturnsQueryParamsAsSent( ) {
        $cut = $this->_makeRequestWithParams(array('a' => array(1, 2, 3)), array('b' => array(1, 2)), array());
        $this->assertEquals('a[]=1&a[]=2&a[]=3&b[]=1&b[]=2', $cut->getCombinedQueryString());
    }

    public function testGetParametersShouldOverridePostParameters( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 1), array('a' => 2), array());
        $this->assertEquals(array('a' => 1), $cut->getParams());
    }

    public function testGetParamFindsAllParams( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 1), array('b' => 2), array('c' => 3));
        $this->assertEquals(1, $cut->getParam('a'));
        $this->assertEquals(2, $cut->getParam('b'));
        $this->assertEquals(3, $cut->getParam('c'));
    }

    public function testSetParamOverridesCallParams( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 1), array('a' => 2), array('a' => 3));
        $cut->setParam('a', 4444);
        $this->assertEquals(4444, $cut->getParam('a'));
    }

    public function testCheckParamsFindsMissingParam( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 1), array('a' => 2), array('a' => 3));
        $this->assertTrue($cut->checkRequiredParams(array('a' => true), array('b' => true)));
        $this->assertFalse($cut->checkRequiredParams(array('a' => 1, 'd' => 1), array('b' => true)));
    }

    public function testGetUnknownParamsReturnsUnexpectedValues( ) {
        $require = array('a' => true);
        $optional = array('b' => true);
        $cut = $this->_makeRequestWithParams(array('a' => 1, 'b' => 2, 'c' => 3), array(), array());
        $this->assertEquals(array('c' => 3), $cut->getUnknownParams($require, $optional));
    }

    public function testRequireParamShouldReturnSetParamEvenIfEmpty( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 0), array(), array());
        $this->assertEquals(0, $cut->requireParam('a'));
    }

    public function emptyParamProvider( ) {
        return array(
            array(''),
            array(null),
            array(false),
        );
    }

    /**
     * @dataProvider            emptyParamProvider
     * @expectedException       Quick_Rest_Exception
     */
    public function testRequireParamShouldThrowExceptionIfEmptyParam( $value ) {
        $cut = $this->_makeRequestWithParams(array('a' => $value), array(), array());
        $cut->requireParam('a');
    }

    /**
     * @expectedException       Quick_Rest_Exception
     */
    public function testRequireParamShouldThrowExceptionIfMissingParam( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 1), array(), array());
        $cut->requireParam('b');
    }

    public function testGetParamsWithoutNamesShouldReturnAllParams( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 1, 'b' => 2, 'd' => 4), array(), array());
        $this->assertEquals(array('a' => 1, 'b' => 2, 'd' => 4), $cut->getParams());
    }

    public function testGetParamsShouldReturnValuesForAllNamesAndOptionallyReturnMissingNames( ) {
        $cut = $this->_makeRequestWithParams(array('a' => 1, 'b' => 2, 'd' => 4), array(), array());
        $missing = array();
        $vals1 = $cut->getParams(array('a', 'b', 'c'));
        $vals2 = $cut->getParams(array('a', 'b', 'c'), $missing);
        // c is missing, should return null
        $this->assertEquals(array('a' => 1, 'b' => 2, 'c' => null), $vals1);
        $this->assertEquals($vals1, $vals2);
        $this->assertEquals(array('c'), $missing);
    }

    public function testGetHeadersShouldReturnNamedHeaderOrAllHeadersByDefault( ) {
        $globals = $this->_createGlobals(array(), array(), array());
        $globals['_SERVER']['HTTP_CONTENT_TYPE'] = 'header content type';
        $globals['_SERVER']['HTTP_AUTHORIZATION'] = 'OAuth ...';
        $cut = new Quick_Rest_Request_Http();
        $cut->setParamsFromGlobals($globals);
        $this->assertEquals('header content type', $cut->getHeaders('Content-Type'));
        $this->assertEquals('OAuth ...', $cut->getHeaders('Authorization'));
        $this->assertEquals(array('Content-Type' => 'header content type', 'Authorization' => 'OAuth ...'), $cut->getHeaders());
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(10000, array($this, '_testSpeedNull'), array(array('a' => 1)));

        $globals = $this->_createGlobals(array('a' => 1), array(), array());
        echo $timer->timeit(20000, "new Quick_Rest_Request_Http()", array($this, '_testCreateSpeed'), array($globals));
        // 193k/s to init params from globals (262k/s w/o Query)
        // 120k/s w/ headers saved if 4 server params

        $cut = $this->_makeRequestWithParams(array('a' => 1), array('b' => 1), array());
        echo $timer->timeit(10000, "check params", array($this, '_testCheckSpeed'), array($cut));
        // 750k checks/sec
        echo $timer->timeit(10000, "getUnknownParams()", array($this, '_testGetUnknownSpeed'), array($cut));
        // 720k diffs/sec
        echo $timer->timeit(10000, "getParam()", array($this, '_testGetParamSpeed'), array($cut));
        // 1260 k/s
        echo $timer->timeit(10000, "set params from template", array($this, '_testTemplateSpeed'), array($cut));
        //  89k/s
        echo $timer->timeit(20000, "set params from globals", array($this, '_testGlobalsSpeed'), array($cut));
        // 355k/s
    }

    public function _testSpeedNull( $arg ) {
    }

    public function _testCreateSpeed( $globals ) {
        $ret = new Quick_Rest_Request_Http();
        $ret->setParamsFromGlobals($globals);
    }

    public function _testCheckSpeed( $cut ) {
        $ret = $cut->checkRequiredParams(array('a' => true, 'b' => true, 'c' => true));
    }

    public function _testGetUnknownSpeed( $cut ) {
        $ret = $cut->getUnknownParams(array('a' => true), array('b' => true));
    }

    public function _testGetParamSpeed( $cut ) {
        $ret = $cut->getParam('b');
    }

    public function _testTemplateSpeed( $cut ) {
        $params = Quick_Rest_Request_PathParams::getParamsFromPath("/path/index.php/{a}/{b}/{c}", "/path/index.php/1/2/3");
    }

    public function _testGlobalsSpeed( $cut ) {
        static $globals;
        if (!$globals) $globals = array(
            '_GET' => array('a' => 1, 'b' => 2),
            '_POST' => array('c' => 3),
            '_SERVER' => array(
                'SERVER_PROTOCOL' => 'HTTP/1.1',
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/path/index.php',
            ),
        );
        $cut->setParamsFromGlobals($globals);
    }

    protected function _makeRequestWithParams( Array $get, $post, Array $path ) {
        $globals = $this->_createGlobals($get, $post, $path);
        $cut = new Quick_Rest_Request_Http();
        $cut->setParamsFromGlobals($globals);
        if ($path) {
            $template = '/path/index.php/{' . implode('}/{', array_keys($path)) . '}';
            $params = $this->_getParamsFromPath($cut, $template);
            // merge in the path params but without overwriting already set post or get params
            foreach ($params as $k => $v) if ($cut->getParam($k) === null) $cut->setParam($k, $v);
        }
        return $cut;
    }

    protected function _getParamsFromPath( $cut, $template ) {
        return Quick_Rest_Request_PathParams::getParamsFromPath($template, $cut->getPath());
    }

    protected function _createGlobals( Array $get, $post, $path ) {
        return $globals = array(
            '_GET' => $get,
            '_POST' => is_array($post) ? $post : array(),
            '_SERVER' => array(
                'SERVER_PROTOCOL' => 'http',
                'REQUEST_METHOD' => $post ? 'POST' : 'GET',
                'SCRIPT_NAME' => '/path/index.php/' . implode('/', array_values($path)),
                'REQUEST_URI' => '/path/index.php/' . implode('/', array_values($path)) . '?' . $this->_buildQuery($get),
                // 'PATH_INFO' => !$path ? null : '/'.implode('/', array_values($path)),
                'HTTP_CONTENT_TYPE' => 'x-www-form-urlencoded',
                'HTTP_AUTHORIZATION' => 'OAuth oauth_consumer_key=ck,oauth_token=tk',
            ),
            // equivalent to php://input but much easier to set
            'HTTP_RAW_POST_DATA' => $this->_buildQuery($post),
        );
    }

    protected function & _setGlobalsFile( Array & $globals, $name, $filename ) {
        $globals['_FILES'][$name] = array(
            'name' => $name,
            'error' => null,
            'tmp_name' => $filename,
        );
        return $globals;
    }

    // build a query like a user would (a[]=1&a[]=2), not like php (a[0]=1&a[1]=2)
    protected function _buildQuery( $params ) {
        if (!is_array($params)) {
            // POST string body is sent as-is
            return $params;
        }
        $namevals = array();
        foreach ($params as $name => $value) {
            if (is_array($value)) {
                if ($value === array_values($value)) {
                    // encode a numerically indexed array as a[]=&a[]=
                    foreach ($value as $val)
                        $namevals[] = "{$name}[]=$val";
                }
                else {
                    foreach ($value as $nam => $val)
                        $namevals[] = "{$name}[$nam]=$val";
                }
            }
            else {
                $namevals[] = "$name=$value";
            }
        }
        return implode('&', $namevals);
    }
}
