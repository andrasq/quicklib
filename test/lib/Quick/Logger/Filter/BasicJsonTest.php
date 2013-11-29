<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Logger_Filter_BasicJsonTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_requestSummary = array(
            'starttime' => date('Y-m-d H:i:s T'),
            'UNIQUE_ID' => uniqid(),
            //'HTTP_USER_AGENT' => '',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_METHOD' => 'GET/POST',
            'REQUEST_URI' => 'BasicJsonTest',
        );
        $this->_template = array(
            'timestamp' => true,
            'duration' => true,
            'level' => true,
            'host' => 'localhost',
            'request' => $this->_requestSummary,
            'message' => true,
        );
        $this->_cut = new Quick_Logger_Filter_BasicJson($this->_template, microtime(true));
    }

    public function testFilterShouldAppendNewline( ) {
        $line = $this->_cut->filterLogline(array('a' => 1), 'info');
        $this->assertEquals("\n", substr($line, -1));
    }

    public function testFilterShouldEmbedRequestSummaryJson( ) {
        $line = $this->_cut->filterLogline("message", 'info');
        $this->assertContains(':'.json_encode($this->_requestSummary), $line);
    }

    public function testFilterShouldIncludeTemplateAndSetFields( ) {
        $line = $this->_cut->filterLogline("message", 'info');
        $bundle = json_decode($line, true);
        foreach ($this->_template as $name => $value) {
            if ($value === true)
                // fields set to TRUE in our template should get set by filter
                $this->assertGreaterThan('', $bundle[$name]);
            else
                // other fields should be output as is
                $this->assertEquals($bundle[$name], $this->_template[$name]);
        }
    }

    public function testFilterShouldSetTimestamp( ) {
        $bundle = json_decode($this->_cut->filterLogline("message", 'info'), true);
        $this->assertTrue(is_numeric($bundle['timestamp']));
        $this->assertLessThan(1, microtime(true) - $bundle['timestamp']);
    }

    public function testFilterShouldIncludeMessage( ) {
        $message = uniqid();
        $bundle = json_decode($this->_cut->filterLogline($message, 'info'), true);
        $this->assertEquals($message, $bundle['message']);
    }

    public function optionalFieldProvider( ) {
        return array(
            array('level', 'is_string'),
            array('duration', 'is_numeric'),
        );
    }

    /**
     * @dataProvider    optionalFieldProvider
     */
    public function testFilterShouldIncludeOptionalFieldsIfPresentInTemplate( $field, $test ) {
        $cut = new Quick_Logger_Filter_BasicJson(array($field => true));
        $bundle = json_decode($cut->filterLogline("message", 'info'), true);
        $this->assertTrue($test($bundle[$field]));
    }

    /**
     * @dataProvider    optionalFieldProvider
     */
    public function testFilterShouldOmitOptionalFieldsIfMissingFromTemplate( $field ) {
        $cut = new Quick_Logger_Filter_BasicJson(array());
        $bundle = json_decode($cut->filterLogline("message", 'info'), true);
        $this->assertFalse(isset($bundle[$field]));
    }
}
