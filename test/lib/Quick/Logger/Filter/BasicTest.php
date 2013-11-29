<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Logger_Filter_BasicTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Logger_Filter_Basic();
    }

    public function testShouldIncludeMessage( ) {
        $string = uniqid();
        $this->assertContains($string, $this->_cut->filterLogline($string, 'info'));
    }

    public function testShouldAddTimestamp( ) {
        $line = $this->_cut->filterLogline("hello", 'info');
        $this->assertTrue((bool)preg_match('/\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d/', $line));
    }

    public function testShouldUseGivenTag( ) {
        $cut = new Quick_Logger_Filter_Basic("my tag 1");
        $this->assertContains("my tag 1", $cut->filterLogline("log message", 'info'));
    }

    public function testShouldUseGivenDateFormat( ) {
        $cut = new Quick_Logger_Filter_Basic(null, 'm/d/Y');
        $this->assertContains(date('m/d/Y'), $cut->filterLogline("log message", 'info'));
    }

    public function testShouldStartMessageAtLeftEdgeIfUntagged( ) {
        $cut = new Quick_Logger_Filter_Basic('', '');
        $line = $cut->filterLogline("logline1", 'info');
        $this->assertEquals("[info] logline1", $line);
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $nloops = 10000;
        echo "\n";
        $timer->calibrate($nloops, array($this, '_testSpeedNoop'), array());
        echo $timer->timeit($nloops, "Filter_Basic", array($this, '_testSpeedFilter'), array());
        // 300k lines/sec filtered, 252k if msec is computed, 245k if msec is included, 240k if tag included
        // 215k if millisec and date format is conditional
    }

    public function _testSpeedNoop( ) {
    }

    public function _testSpeedFilter( ) {
        $this->_cut->filterLogline("line 1\n", 'info');
    }
}
