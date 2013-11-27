<?

class Quick_Store_FileHashTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_dir = "/tmp/test-store";
        `rm -rf $this->_dir; mkdir $this->_dir`;
        $this->_cut = new Quick_Store_FileHash($this->_dir, "test-", 0);
    }

    public function tearDown( ) {
        `rm -rf $this->_dir`;
    }

    public function testWithTtlShouldReturnCopyOfStore( ) {
        $copy = $this->_cut->withTtl($ttl = rand());
        $this->assertType(get_class($this->_cut), $copy);
        $this->assertContains($ttl, (array)$copy);
    }

    public function testGetShouldReturnFalseIfMissing( ) {
        $this->assertFalse($this->_cut->get('foo'));
    }

    public function testGetShouldReturnFalseIfDifferentPrefix( ) {
        $cut = new Quick_Store_FileHash($this->_dir, "test2-");
        $this->_cut->set('foo', 1);
        $this->assertFalse($cut->get('foo'));
    }

    public function testGetShouldReturnValueIfPresent( ) {
        $this->_cut->set('foo', 1234);
        $this->_cut->set('bar', 1111);
        $this->assertEquals(1111, $this->_cut->get('bar'));
        $this->assertEquals(1234, $this->_cut->get('foo'));
    }

    public function testSetShouldOverwriteExisting( ) {
        $this->_cut->set('foo', 1234);
        $this->_cut->set('foo', 1111);
        $this->assertEquals(1111, $this->_cut->get('foo'));
    }

    public function testDeleteShouldRemoveValue( ) {
        $this->_cut->set('foo', 1234);
        $this->_cut->set('bar', 1111);
        $this->_cut->delete('foo');
        $this->assertFalse($this->_cut->get('foo'));
    }

    public function testAddShouldSetValue( ) {
        $this->_cut->add('foo', 123);
        $this->assertEquals(123, $this->_cut->get('foo'));
    }

    public function testAddShouldReturnFalseIfAlreadySet( ) {
        $this->assertTrue($this->_cut->add('foo', 1234));
        $this->assertFalse($this->_cut->add('foo', 1111));
        $this->assertFalse($this->_cut->add('foo', 222));
        $this->assertEquals(1234, $this->_cut->get('foo'));
    }

    public function testGcShouldExpireValues( ) {
        $this->_cut->set('foo', 1234);
        $this->_cut->set('bar', 1111);
        $cut = new Quick_Store_FileHash($this->_dir, "test-", 5);
        $cut->set('baz', 1);
        $this->_cut->gc();
        $this->assertFalse($this->_cut->get('foo'));
        $this->assertFalse($this->_cut->get('bar'));
        $this->assertEquals(1, $this->_cut->get('baz'));
    }

    public function xx_testSpeed( ) {
        $store = $this->_cut;
        $timer = new Quick_Test_Timer();

        $timer->calibrate(1000, array($this, '_testSpeedNull'), array($store, 'foo', 123));
        echo $timer->timeit(3000, 'create', array($this, '_testSpeedCreate'), array($this->_dir, 'foo', 123));
        // 148k/s
        echo $timer->timeit(3000, 'set', array($this, '_testSpeedSet'), array($store, 'foo', 123));
        // 40k/s
        // ?? NOTE: only 5100/sec on vmware ext4 (noatime)
        echo $timer->timeit(3000, 'set + delete', array($this, '_testSpeedSetDelete'), array($store, 'foo', 1234));
        // 22k/s
        echo $timer->timeit(3000, 'add', array($this, '_testSpeedAdd'), array($store, 'foo', 1234));
        // 85k/s (already exists, skipped)

        $timer->calibrate(3000, array($this, '_testSpeedNull'), array($store, 'foo'));
        $store->set('foo', 12345);
        echo $timer->timeit(3000, 'get', array($this, '_testSpeedGet'), array($store, 'foo'));
        // 41k/s
    }

    public function _testSpeedNull( ) {
    }

    public function _testSpeedCreate( $dir, $name, $value ) {
        return new Quick_Store_FileHash($dir, "test-");
    }

    public function _testSpeedSet( $store, $name, $value ) {
        return $store->set($name, $value);
    }

    public function _testSpeedGet( $store, $name ) {
        return $store->get($name);
    }

    public function _testSpeedAdd( $store, $name, $value ) {
        return $store->add($name, $value);
    }

    public function _testSpeedSetDelete( $store, $name, $value ) {
        $store->set($name, $value);
        $store->delete($name);
    }
}
