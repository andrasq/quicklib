<?php

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Call_HttpMultiTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Rest_Call_HttpMulti();
    }

    public function testConstructorShouldCreateClass( ) {
        $this->assertType('Quick_Rest_Call', $this->_cut);
    }

    public function testGetCallsShouldReturnAddedCalls( ) {
        $this->assertEquals(array(), $this->_cut->getCalls());
        $this->_cut->addCall($call1 = new Quick_Rest_Call_Http("http://1"));
        $this->_cut->addCall($call2 = new Quick_Rest_Call_Http("http://2"));
        $this->assertEquals(array($call1, $call2), $this->_cut->getCalls());
    }

    public function testClearCallsShouldDiscardCalls( ) {
        $this->_cut->addCall($call1 = new Quick_Rest_Call_Http("http://1"));
        $this->_cut->clearCalls();
        $this->assertEquals(array(), $this->_cut->getCalls());
    }

    public function testCallShouldRunAllAddedCalls( ) {
        $ncalls = 5;
        for ($i=0; $i<$ncalls; ++$i)
            $calls[] = new Quick_Rest_Call_Http("http://localhost/index.html");
        foreach ($calls as $call) $this->_cut->addCall($call);
        $this->_cut->call();
        $expect = substr($calls[0]->getReply(), $calls[0]->getContentOffset());
        foreach ($calls as $call) {
            $this->assertContains('200 OK', substr($call->getReply(), 0, strcspn($call->getReply(), "\n")));
            $this->assertEquals($expect, substr($call->getReply(), $call->getContentOffset()));
        }
    }
}
