<?

class Quick_Store_FileCacheTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        @mkdir("/tmp/test-quickstore");
        $this->_cut = new Quick_Store_FileCache("/tmp/test-quickstore", "test-", 10);
        // values set through $expired appear in $cut with an expired TTL
        $this->_expired = new Quick_Store_FileCache("/tmp/test-quickstore", "test-", -1);
    }

    public function tearDown( ) {
        `rm -rf /tmp/test-quickstore`;
    }

    public function testWithTtlShouldReturnCopyOfStore( ) {
        $copy = $this->_cut->withTtl($ttl = rand());
        $this->assertContains($ttl, (array)$copy);
        $this->assertType(get_class($this->_cut), $copy);
    }

    public function testSetShouldSaveToFile( ) {
        $this->_cut->set("name", "value");
        $this->assertEquals("value", file_get_contents($this->_cut->_getFilename("name")));
    }

    public function testDeleteShouldRemoveFile( ) {
        $this->_cut->set("name", "value");
        $this->_cut->delete("name");
        $this->assertFalse(file_Exists($this->_cut->_getFilename("name")));
    }

    public function testGetShouldLoadFromFile( ) {
        $this->_cut->set("name", "value");
        $this->assertEquals("value", $this->_cut->get("name"));
    }

    public function testGetShouldNotLoadExpiredContents( ) {
        $this->_expired->set("name", "value");
        $this->assertFalse($this->_cut->get("name"));
    }

    public function testExpireContentsShouldRemoveExpiredFiles( ) {
        $this->_expired->set("name1", "value");
        $this->_cut->set("name2", "value");
        $this->_cut->expireContents();
        $this->assertFalse(file_exists($this->_cut->_getFilename("name1")));
        $this->assertTrue(file_exists($this->_cut->_getFilename("name2")));
    }

    public function testSetShouldOverwriteValue( ) {
        $this->_cut->set("name", "value");
        $this->_cut->set("name", "value2");
        $this->assertEquals("value2", $this->_cut->get("name"));
    }

    public function testSetShouldResetExpirationDate( ) {
        $this->_expired->set("name", "value");
        $this->_cut->set("name", "value2");
        $this->assertEquals("value2", $this->_cut->get("name"));
    }

    /**
     * @expectedException       Quick_Store_Exception
     */
    public function testSetShouldThrowExceptionIfUnableToWriteFile( ) {
        @unlink($this->_cut->_getFilename("x"));
        symlink("/", $this->_cut->_getFilename("x"));
        @$this->_cut->set("x", 1);
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(10000, array($this, '_testSpeedNull'), array($this->_cut, "name", "value"));
        echo "\n";

        echo $timer->timeit(10000, "set", array($this, '_testSpeedSet'), array($this->_cut, "name", "value"));
        // 36k/s (50k/s lock-free)

        echo $timer->timeit(10000, "get", array($this, '_testSpeedGet'), array($this->_cut, "name", "value"));
        // 57k/s (73k/s lock-free)

        echo $timer->timeit(5000, "set+delete", array($this, '_testSpeedSetDelete'), array($this->_cut, "name", "value"));
        // 24k/s (because set is more expensive if the file does not already exist?) (but sometimes only 11k/s ??)
        // (but only 19k/s lock-free)
    }

    public function _testSpeedNull( $cut, $name, $value ) {
    }

    public function _testSpeedSet( $cut, $name, $value ) {
        $cut->set($name, $value);
    }

    public function _testSpeedGet( $cut, $name, $value ) {
        $cut->get($name);
    }

    public function _testSpeedSetDelete( $cut, $name, $value ) {
        $cut->set($name, $value);
        $cut->delete($name);
    }

}
