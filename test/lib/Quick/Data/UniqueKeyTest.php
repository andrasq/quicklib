<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_UniqueKeyTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Data_UniqueKey();
    }

    public function testShouldCreateDistinctKeys( ) {
        $key1 = $this->_cut->makeKey();
        $key2 = $this->_cut->makeKey();
        $this->assertNotEquals($key1, $this->_cut->getKey());
        $this->assertNotEquals($key2, $this->_cut->getKey());
        $this->assertNotEquals($key1, $key2);
    }

    public function testShouldNotGenerateDuplicateKeysEvenInQuickBursts( ) {
        $cut = $this->_cut;
        for ($i=0; $i<1000; ++$i) $keys[] = $cut->makeKey();
        $this->assertEquals(1000, count(array_unique($keys)));
    }

    public function testGetKeyShouldReturnSameKeyEachCall( ) {
        $key = $this->_cut->getKey();
        $this->assertEquals($key, $this->_cut->getKey());
        $this->assertEquals($key, $this->_cut->getKey());
    }

    public function testMakeKeyShouldReturnDifferentKeys( ) {
        $key = $this->_cut->getKey();
        $this->assertNotEquals($key, $this->_cut->makeKey());
        $this->assertNotEquals($key, $this->_cut->makeKey());
    }

    public function testFetchShouldReturnDifferentKeys( ) {
        $key = $this->_cut->getKey();
        for ($i=0; $i<1000; ++$i) {
            $keys[] = $this->_cut->fetch();
        }
        $keys = array_unique($keys);
        $this->assertEquals(1000, count($keys));
    }

    public function testResetShouldReturnTrue( ) {
        $this->assertTrue($this->_cut->reset());
    }

    public function testStringCastShouldReturnSameKeyAsGetKey( ) {
        $this->assertEquals((string)$this->_cut, $this->_cut->getKey());
    }

    public function xx_testSpeed( ) {
        $cut = $this->_cut;
        $timer = new Quick_Test_Timer();
        $timer->calibrate(10000, array($this, '_testSpeedNull'), array($cut));
        echo $timer->timeit(10000, "makeKey", array($this, '_testSpeedRate'), array($cut));
        echo $timer->timeit(10000, "oneshot", array($this, '_testSpeedNew'), array($cut));
        echo $timer->timeit(10000, "::createKey", array($this, '_testSpeedCreate'), array($cut));
    }

    public function _testSpeedNull( $cut ) {
    }

    public function _testSpeedRate( $cut ) {
        $cut->makeKey();
    }

    public function _testSpeedNew( $cut ) {
        (string)(new Quick_Data_UniqueKey());
    }

    public function _testSpeedCreate( $cut ) {
        Quick_Data_UniqueKey::createKey();
    }
}
