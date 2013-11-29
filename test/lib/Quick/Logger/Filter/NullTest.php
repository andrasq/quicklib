<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Logger_Filter_NullTest
    extends Quick_Test_Case
{
    public function testCanCreateFilter( ) {
        $cut = new Quick_Logger_Filter_Null(new Quick_Logger_Null());
    }

    public function testNullFilterShouldReturnMessage( ) {
        $msg = uniqid();
        $cut = new Quick_Logger_Filter_Null(new Quick_Logger_Null());
        $this->assertEquals($msg, $cut->filterLogline($msg, 'info'));
    }
}
