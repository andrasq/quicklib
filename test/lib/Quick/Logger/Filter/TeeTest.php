<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Logger_Filter_TeeTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_line = uniqid();
        $this->_logger = $this->getMock('Quick_Logger_Null', array('debug', 'info', 'err'));
    }

    public function testTeeShouldSendCopyOfDebugLineToLogger( ) {
        $this->_logger->expects($this->once())->method('debug')->with($this->_line);
        $this->_logger->expects($this->never())->method('info');
        $this->_logger->expects($this->never())->method('err');
        $cut = new Quick_Logger_Filter_Tee($this->_logger);
        $cut->filterLogline($this->_line, 'debug');
    }

    public function testTeeShouldSendCopyOfInfoLineToLogger( ) {
        $this->_logger->expects($this->never())->method('debug');
        $this->_logger->expects($this->once())->method('info')->with($this->_line);
        $this->_logger->expects($this->never())->method('err');
        $cut = new Quick_Logger_Filter_Tee($this->_logger);
        $cut->filterLogline($this->_line, 'info');
    }

    public function testTeeShouldSendCopyOfErrLineToLogger( ) {
        $this->_logger->expects($this->never())->method('debug');
        $this->_logger->expects($this->never())->method('info');
        $this->_logger->expects($this->once())->method('err')->with($this->_line);
        $cut = new Quick_Logger_Filter_Tee($this->_logger);
        $cut->filterLogline($this->_line, 'err');
    }
}
