<?

/**
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_AppBaseTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Rest_AppBase();
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

    public function testGetInstanceShouldReturnInstanceDefinedWithSetInstance( ) {
        $this->_cut->setInstance('type1', $id1 = microtime(true));
        $this->_cut->setInstance('type2', $id2 = uniqid());
        $this->assertEquals($id2, $this->_cut->getInstance('type2'));
        $this->assertEquals($id1, $this->_cut->getInstance('type1'));
    }

    public function testGetInstanceShouldBuildAndCacheAndReturnInstanceDefinedWithSetInstanceBuilder( ) {
        $type = uniqid();
        $callback = $this->getMock('StdClass', array('getpid'));
        $callback->expects($this->once())->method('getpid')->will($this->returnValue(getmypid()));
        $this->_cut->setInstanceBuilder($type, array($callback, 'getpid'));
        $this->assertEquals(getmypid(), $this->_cut->getInstance($type), "should call builder to obtain instance");
        $this->assertEquals(getmypid(), $this->_cut->getInstance($type), "should cache result and not call builder again");
    }
}
