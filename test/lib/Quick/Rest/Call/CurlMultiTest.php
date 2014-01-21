<?php

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Call_CurlMultiExposer
    extends Quick_Rest_Call_CurlMulti
{
    public $_mh, $_timeout = 2, $_windowSize = 5, $_ch = array(), $_chDone = array();
    public $_multiHandles = array();
    public $_notDone = false;
    public $_calls = array();

    protected function _curl_multi_init( ) {
        return 1;
    }

    protected function _curl_multi_add_handle( $mh, $ch ) {
        $this->_multiHandles[$ch] = $ch;
    }

    protected function _curl_multi_remove_handle( $mh, $ch ) {
        unset($this->_multiHandles[$ch]);
    }

    protected function _curl_multi_exec( $mh, & $running ) {
        $running = count($this->_multiHandles);
        return CURLM_OK;
    }

    protected function _curl_multi_info_read( $mh ) {
        if ($this->_notDone || !$this->_multiHandles) return false;
        $info = array(
            'handle' => reset($this->_multiHandles),
        );
        return $info;
    }
}

class Quick_Rest_Call_CurlMultiTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Rest_Call_CurlMultiExposer(111);
        $this->_cut->setTimeout(0);
    }

    public function testConstructShouldUseGivenMultiHandle( ) {
        $cut = new Quick_Rest_Call_CurlMultiExposer($id = uniqid());
        $this->assertEquals($id, $cut->_mh);
    }

    public function testSetTimeoutShouldSetTimeout( ) {
        $this->_cut->setTimeout(123.456);
        $this->assertEquals(123.456, $this->_cut->_timeout);
    }

    public function testSetWindowSizeShouldSetWindowSize( ) {
        $this->_cut->setWindowSize(123);
        $this->assertEquals(123, $this->_cut->_windowSize);
    }

    public function testSetHandlesShouldSetHandles( ) {
        $this->_cut->setHandles(array(1,2,3,4));
        $this->assertEquals(array(1,2,3,4), $this->_cut->_ch);
    }

    public function testAddHandlesShouldAppendHandles( ) {
        $this->_cut->setHandles(array(1,2,3,4));
        $this->_cut->addHandles(array(5,6));
        $this->assertEquals(array(1,2,3,4,5,6), $this->_cut->_ch);
    }

    public function testExecShouldNotReturnBeforeTimeout( ) {
        $tm = microtime(true);
        $this->_cut->setTimeout(.02);
        $this->_cut->addHandles(array(1));
        $this->_cut->_notDone = true;
        $this->_cut->exec();
        $this->assertGreaterThanOrEqual($tm + .02, microtime(true));
    }

    public function testExecShouldAddHandles( ) {
        $this->_cut->_notDone = true;
        $this->_cut->setHandles(array(1,2,3));
        $this->_cut->exec();
        $this->assertContainsElements(array(1,2,3), $this->_cut->_multiHandles);
    }

    public function testExecShouldRemoveDoneHandles( ) {
        $this->_cut->setHandles(array(1,2,3));
        $this->_cut->exec();
        $this->assertEquals(array(), $this->_cut->_ch);
        $this->assertContainsElements(array(1,2,3), $this->_cut->_chDone);
    }

    public function testExecShouldCallCurlMultiExecAndCurlMultiInfoRead( ) {
        $cut = $this->getMockSkipConstructor(
            'Quick_Rest_Call_CurlMultiExposer', array('_curl_multi_exec', '_curl_multi_info_read')
        );
        $cut->expects($this->exactly(1))->method('_curl_multi_exec')->will($this->returnValue(CURLM_OK));
        $cut->expects($this->exactly(1))->method('_curl_multi_info_read')->will($this->returnValue(false));
        $cut->setTimeout(0);
        $cut->_notDone = true;
        $cut->setWindowSize(10);
        $cut->setHandles(array(1,2,3));
        $cut->exec();
    }

    public function testExecShouldAddAndRemoveMoreHandlesThanWindowSize( ) {
        $this->_cut->setWindowSize(4);
        $this->_cut->setHandles(range(1, 100));
        $this->_cut->setTimeout(.1);
        $this->_cut->exec();
        $this->assertEquals(array(), $this->_cut->_ch);
        $this->assertContainsElements(range(1, 100), $this->_cut->_chDone);
        $this->assertContainsElements(range(1, 100), $this->_cut->getDoneHandles());
    }

    public function xx_testSpeed( ) {
        $tm = microtime(true);
        for ($i=0; $i<10000; ++$i) {
            // nb: nginx 12% faster as-is, but apache 5% faster w/ taskset 1 (??) (20% diff to apache; 60 vs 71% cpu)
            // taskset 1 20-wide can run 12,000 calls/sec (4 cores)
            $handles[] = $ch = curl_init("http://127.0.0.1:80/index.html");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
        $tm = microtime(true) - $tm;
        echo "AR: created batch of 10k ch in $tm sec\n";

        $tm = microtime(true);
        $multi = new Quick_Rest_Call_CurlMulti(curl_multi_init());
        $multi->setHandles($handles);
        $multi->setWindowSize(5);
        $multi->exec();
        $tm = microtime(true) - $tm;
        echo "AR: ran batch in $tm sec\n";
        // 27500/s calls to empty index.html (4-core system, window=5; test using 95% cpu)
    }


    protected function assertContainsElements( Array $subset, Array $set ) {
        foreach ($subset as $e)
            $this->assertContains($e, $set);
    }
}
