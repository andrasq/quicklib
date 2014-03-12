<?

/**
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Fifo_FakeTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Fifo_Fake("line1\nline2\n");
    }

    public function testFgetsShouldReturnLinesThenFalse( ) {
        $this->assertEquals("line1\n", $this->_cut->fgets());
        $this->assertEquals("line2\n", $this->_cut->fgets());
        $this->assertEquals(false, $this->_cut->fgets());
    }

    public function testFgetsShouldReturnFalseUntilTerminatingNewlineArrives( ) {
        $cut = new Quick_Fifo_Fake("line1\nline2");
        $this->assertEquals("line1\n", $cut->fgets());
        $this->assertFalse($cut->fgets());
        $this->assertFalse($cut->fgets());
        $cut->write("\n");
        $this->assertEquals("line2\n", $cut->fgets());
    }

    public function testFtellShouldReturnCurrentReadOffset( ) {
        $this->assertEquals(0, $this->_cut->ftell());
        $this->_cut->fgets();
        $this->assertEquals(6, $this->_cut->ftell());
        $this->_cut->fgets();
        $this->assertEquals(12, $this->_cut->ftell());
    }

    public function testFeofShouldReturnTrueWhenEof( ) {
        $this->assertFalse($this->_cut->feof());
        $this->_cut->fgets();
        $this->_cut->fgets();
        $this->assertTrue($this->_cut->feof());
    }

    public function testRsyncShouldDiscardReadData( ) {
        $this->_cut->fgets();
        $this->_cut->rsync();
        $this->assertEquals(0, $this->_cut->ftell());
        $this->assertEquals("line2\n", $this->_cut->fgets());
    }

    public function testFputsShouldAppendData( ) {
        $this->_cut->fputs("line3\n");
        $this->assertEquals("line1\nline2\nline3\n", $this->_cut->read(1000));
    }

    public function testReadShouldExtendToTerminatingNewlineOrReturnFalse( ) {
        $this->_cut->write("line3");
        $this->assertEquals("line1\n", $this->_cut->read(6), "is ok, read ends on newline");
        $this->assertEquals("line2\n", $this->_cut->read(2), "is short, read extends to newline");
        $this->assertEquals(false, $this->_cut->read(2), "is short, but no more newlines");
    }

    public function testReadReturnsFalseIfNoTerminatingNewlineAvailable( ) {
        $this->_cut->write("line3");
        $this->assertFalse($this->_cut->read(100), "no newline yet");
        $this->_cut->write("\n");
        $this->assertEquals("line1\nline2\nline3\n", $this->_cut->read(100), "newline arrived, return data");
    }
}
