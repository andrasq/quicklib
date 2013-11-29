<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Fetchable_ArrayTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Db_Fetchable_Array(array(1,2,3,4));
    }

    public function testFetchShouldReturnNextItem( ) {
        $this->assertEquals(1, $this->_cut->fetch());
        $this->assertEquals(2, $this->_cut->fetch());
        $this->assertEquals(3, $this->_cut->fetch());
    }

    public function testResetShouldRestartFetchingAtBeginning( ) {
        $this->assertEquals(1, $this->_cut->fetch());
        $this->assertEquals(2, $this->_cut->fetch());
        $this->_cut->reset();
        $this->assertEquals(1, $this->_cut->fetch());
    }

    public function testFetchShouldReturnFalseAfterNoMoreItems( ) {
        $this->_cut->fetch();
        $this->_cut->fetch();
        $this->_cut->fetch();
        $this->_cut->fetch();
        $this->assertFalse($this->_cut->fetch());
        $this->assertFalse($this->_cut->fetch());
        $this->assertFalse($this->_cut->fetch());
    }
}
