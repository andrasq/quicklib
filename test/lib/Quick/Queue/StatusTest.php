<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_StatusTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Queue_Status();
    }

    public function testStatusShouldExtendConfig( ) {
        $this->assertType('Quick_Queue_Config', $this->_cut);
    }
}
