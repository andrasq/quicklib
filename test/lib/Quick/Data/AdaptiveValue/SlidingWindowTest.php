<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_AdaptiveValue_SlidingWindowTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_step = -.7;
        $this->_backoff = 2;
        $this->_cut = new Quick_Data_AdaptiveValue_SlidingWindow(2, 1, 10, $this->_step, $this->_backoff);
    }

    public function testGetCurrentShouldReturnCurrentSetting( ) {
        $this->assertEquals(2, $this->_cut->get());
    }

    public function testSetCurrentShouldChangeCurrentSetting( ) {
        $this->_cut->set(7);
        $this->assertEquals(7, $this->_cut->get());
    }

    public function testAdjustShouldDoubleCurrentIfTooLong( ) {
        $now = $this->_cut->get();
        $this->assertGreaterThanOrEqual(2*$now, $this->_cut->adjust(true));
    }

    public function testAdjustShouldDecreaseCurrentIfNotTooLong( ) {
        $now = $this->_cut->get();
        $this->assertLessThanOrEqual($now - $this->_step, $this->_cut->adjust(false));
    }

    public function testAdjustShouldNotIncreaseAboveMax( ) {
        $this->_cut->set(9);
        $this->assertEquals(10, $this->_cut->adjust(true));
    }

    public function testAdjustShouldNotDecreaseBelowMin( ) {
        $this->_cut->set(1.5);
        $this->assertEquals(1, $this->_cut->adjust(false));
    }
}
