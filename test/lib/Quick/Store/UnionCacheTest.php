<?php

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Store_UnionCacheTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_master = $this->getMock('Quick_Store_Null', array('get', 'set', 'delete'));
        $this->_cache = $this->getMock('Quick_Store_Null', array('get', 'set', 'delete'));
        $this->_cut = new Quick_Store_UnionCache($this->_master, $this->_cache);
    }

    public function testGetShouldReadMasterIfItemNotFoundInCache( ) {
        $this->_cache->expects($this->once())->method('get')->with('foo')->will($this->returnValue(false));
        $this->_master->expects($this->once())->method('get')->with('foo')->will($this->returnValue(123));
        $item = $this->_cut->get('foo');
        $this->assertEquals(123, $item);
    }

    public function testGetshouldNotReadMasterIfItemFoundCache( ) {
        $this->_master->expects($this->never())->method('get');
        $this->_cache->expects($this->once())->method('get')->with('foo')->will($this->returnValue(1));
        $item = $this->_cut->get('foo');
        $this->assertEquals(1, $item);
    }

    public function omit_testGetShouldReadThroughIntoLocalCache( ) {
        // readthrough off by default
        $this->_master->expects($this->any())->method('get')->with('foo')->will($this->returnValue(123));
        $this->_cache->expects($this->once())->method('get')->with('foo')->will($this->returnValue(false));
        $this->_cache->expects($this->once())->method('set')->with('foo', 123)->will($this->returnValue(true));
        $item = $this->_cut->get('foo');
    }

    public function testSetShouldSetItemOnlyInCache( ) {
        $this->_master->expects($this->never())->method('set');
        $this->_cache->expects($this->once())->method('set')->with('foo', 123)->will($this->returnValue(true));
        $this->_cut->set('foo', 123);
    }

    public function testDeleteShouldDeleteOnlyInCache( ) {
        $this->_cache->expects($this->once())->method('delete')->with('foo')->will($this->returnValue(true));
        $this->_cut->delete('foo');
    }

    public function testDeletedItemShouldReturnFalseUntilSetAgain( ) {
        $this->_cache->expects($this->any())->method('get')->with('foo')->will($this->returnValue(123));
        $this->_cut->delete('foo');
        $item = $this->_cut->get('foo');
        $this->assertFalse($item);
        $this->_cut->set('foo', 123);
        $item = $this->_cut->get('foo');
        $this->assertEquals(123, $item);
    }
}
