<?

/**
 * No-op store: fails to set, fails to find and fails to delete.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-14 - AR.
 */

class Quick_Store_NullTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Store_Null();
    }

    public function testWithTtlShouldReturnCopyOfStore( ) {
        $copy = $this->_cut->withTtl(1);
        $this->assertTrue($copy == $this->_cut);
        $this->assertTrue($copy !== $this->_cut);
    }
}
