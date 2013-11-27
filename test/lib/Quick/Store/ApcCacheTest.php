<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Store_ApcCacheTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        if (!function_exists('apc_store')) $this->markTestSkipped("apc not available");
        $this->_cut = new Quick_Store_ApcCache("test-", 2);
        $this->_uniq = uniqid();
        apc_clear_cache();
    }

    public function testWithTtlShouldReturnCopyOfStore( ) {
        $copy = $this->_cut->withTtl($ttl = rand());
        $this->assertType(get_class($this->_cut), $copy);
        $this->assertContains($ttl, (array)$copy);

        $hash1 = (array)$this->_cut;
        $hash2 = (array)$copy;
        $this->assertEquals($ttl, $hash2["\0*\0_ttl"]);
        $hash2["\0*\0_ttl"] = 2;
        $this->assertEquals($hash1, $hash2);
    }

    public function testGetShouldReturnFalseIfValueNotFound( ) {
        $this->assertFalse($this->_cut->get($this->_uniq));
    }

    public function testSetShouldStoreValueWithPrefixPrependedToName( ) {
        $this->_cut->set($this->_uniq, $this->_uniq);
        $this->assertFalse(apc_fetch($this->_uniq));
        $this->assertEquals($this->_uniq, apc_fetch("test-$this->_uniq"));
    }

    public function testGetShouldRetrieveValue( ) {
        $this->_cut->set($this->_uniq, $this->_uniq);
        $this->assertEquals($this->_uniq, $this->_cut->get($this->_uniq));
    }

    public function testDeleteShouldRemoveValue( ) {
        $this->_cut->set($this->_uniq, $this->_uniq);
        $this->_cut->delete($this->_uniq);
        $this->assertFalse($this->_cut->get($this->_uniq));
    }

    public function testAddShouldStoreValue( ) {
        $ok = $this->_cut->add($this->_uniq, 1);
        $this->assertTrue($ok);
        $this->assertEquals(1, $this->_cut->get($this->_uniq));
    }

    public function testAddShouldNotOverwriteValue( ) {
        $ok = $this->_cut->add($this->_uniq, 1);
        $ok = $this->_cut->add($this->_uniq, 2);
        $this->assertFalse($ok);
        $this->assertEquals(1, $this->_cut->get($this->_uniq));
    }

    public function xx_testGetShouldReturnFalseAfterTtlExpired( ) {
        // apc cache uses ttl = 0 to never expire the item
        $cut = new Quick_Store_ApcCache("test-", 1);
        $cut->set($this->_uniq, $this->_uniq);
        usleep(1001000);
        $this->assertFalse($cut->get($this->_uniq));
    }

    public function testGetNamesShouldReturnListing( ) {
        $a = uniqid();
        $b = uniqid();
        $this->_cut->set($a, 1);
        $this->_cut->set($b, 2);
        $names = $this->_cut->getNames();
        $this->assertContains($a, $names);
        $this->assertContains($b, $names);
    }

    public function testExistsShouldReturnTrueIfValueIsCached( ) {
        $this->_cut->set('foo', 1);
        $this->assertTrue($this->_cut->exists('foo'));
        $this->assertFalse($this->_cut->exists('bar'));
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(1000, array($this, '_testSpeedNull'), array($this->_cut));
        echo $timer->timeit(10000, 'apc oneshot', array($this, '_testSpeedOneshot'), array($this->_cut));
        echo $timer->timeit(10000, 'apc create', array($this, '_testSpeedCreate'), array($this->_cut));
        echo $timer->timeit(10000, 'apc set', array($this, '_testSpeedSet'), array($this->_cut));
        echo $timer->timeit(10000, 'apc get', array($this, '_testSpeedGet'), array($this->_cut));
        echo $timer->timeit(10000, 'apc names', array($this, '_testSpeedNames'), array($this->_cut));
    }

    public function _testSpeedNull( $cut ) {
    }

    public function _testSpeedOneshot( ) {
        $cache = new Quick_Store_ApcCache("test-", 2);
        $cache->get("foo");
    }

    public function _testSpeedCreate( ) {
        return new Quick_Store_ApcCache("test-", 2);
    }

    public function _testSpeedSet( $cut ) {
        $cut->set('foo', 1);
    }

    public function _testSpeedGet( $cut ) {
        $cut->get('foo');
    }

    public function _testSpeedNames( $cut ) {
        $cut->getNames();
    }
}
