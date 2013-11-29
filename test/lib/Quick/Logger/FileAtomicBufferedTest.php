<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Logger_FileAtomicBufferedTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_tempfile = new Quick_Test_Tempfile();
        $this->_cut = new Quick_Logger_FileAtomicBuffered((string)$this->_tempfile);
    }

    public function testShouldBufferLinesAfterFirst( ) {
        $this->_cut->info("line 1\n");
        $this->_cut->info("line 2\n");
        $this->_cut->info("line 3\n");
        $this->assertEquals("line 1\n", $this->_tempfile->getContents());
    }

    public function testShouldFlushBufferOnExit( ) {
        $this->_cut->info("line 1\n");
        $this->_cut->info("line 2\n");
        $this->_cut->info("line 3\n");
        unset($this->_cut);
        $this->assertEquals("line 1\nline 2\nline 3\n", $this->_tempfile->getContents());
    }

    public function testShouldFlushBuffer100xPerSecond( ) {
        $this->_cut->info("line 1\n");
        usleep(.01 * 1000000);
        $this->_cut->info("line 2\n");
        $this->assertEquals("line 1\nline 2\n", $this->_tempfile->getContents());
    }
}
