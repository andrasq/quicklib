<?

/**
 * Unchanging adaptive value, for testing.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_AdaptiveValue_ConstantTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Data_AdaptiveValue_Constant(3);
    }

    public function testGetShouldReturnCurrentValue( ) {
        $this->assertEquals(3, $this->_cut->get());
    }

    public function testSetShouldChangeTheValue( ) {
        $this->_cut->set(4);
        $this->assertEquals(4, $this->_cut->get());
    }

    public function testAdjustShouldReturnTheUnchangedValue( ) {
        $this->assertEquals(3, $this->_cut->adjust(true));
        $this->assertEquals(3, $this->_cut->adjust(false));
    }
}
