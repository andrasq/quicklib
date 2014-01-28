<?php

/**
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

require_once 'HttpTest.php';


class Quick_Rest_Call_HttpFileTest
    extends Quick_Rest_Call_HttpTest
{
    protected function _createCut( ) {
        $this->_replyFile = new Quick_Test_Tempfile();
        return new Quick_Rest_Call_HttpFile($this->_replyFile);
    }

    public function testSetReplyFileShouldGetPopulatedWithReplyIncludingHeaders( ) {
        $this->_cut->setReplyFile($replyfile = new Quick_Test_Tempfile());
        $this->_runCall();
        $contents = file_get_contents($replyfile);
        $this->assertContains("HTTP/1", $contents, "should contain headers");
        $this->assertContains("post=1&postargs=1", $contents, "should contain body");
    }

    /**
     * @expectedException       Quick_Rest_Exception
     */
    public function testGetContentFileShouldThrowExceptionIfSourceIsNotReadable( ) {
        $this->_cut->setReplyFile($infile = new Quick_Test_Tempfile());
        $this->_runCall();
        //unlink($infile);
        chmod($infile, 0);
        $this->_cut->getContentFile($filename = new Quick_Test_Tempfile());
    }
}
