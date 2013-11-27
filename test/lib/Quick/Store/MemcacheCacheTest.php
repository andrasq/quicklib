<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Store_MemcacheCacheTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $mc = new Memcache();
        $mc->addServer('localhost', 11211);
        $this->_cut = new Quick_Store_MemcacheCache($mc, "test-", 2);
    }

    public function testWithTtlShouldReturnCopyOfStore( ) {
        $copy = $this->_cut->withTtl($ttl = rand());
        $this->assertType(get_class($this->_cut), $copy);
        $hash = (array)$copy;
        unset($hash["\0*\0_memcache"]);
        $this->assertContains($ttl, $hash);
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(1000, array($this, '_testSpeedNull'), array($this->_cut));
        echo $timer->timeit(10000, 'memcache oneshot', array($this, '_testSpeedOneshot'), array($this->_cut));
        echo $timer->timeit(10000, 'memcache create', array($this, '_testSpeedCreate'), array($this->_cut));
        echo $timer->timeit(10000, 'memcache set', array($this, '_testSpeedSet'), array($this->_cut));
        echo $timer->timeit(10000, 'memcache get', array($this, '_testSpeedGet'), array($this->_cut));
        echo $timer->timeit(10000, 'memcache names', array($this, '_testSpeedNames'), array($this->_cut));
    }

    public function _testSpeedNull( $cut ) {
    }

    public function _testSpeedOneshot( ) {
        $mc = new Memcache();
        $mc->addServer('127.0.0.1', 11211);
        $cache = new Quick_Store_MemcacheCache($mc, "test-", 2);
        return $cache->get("foo");
    }

    public function _testSpeedCreate( ) {
        $mc = new Memcache();
        $mc->addServer('127.0.0.1', 11211);
        return new Quick_Store_MemcacheCache($mc, "test-", 2);
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
