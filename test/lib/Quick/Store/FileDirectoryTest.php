<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Store_FileDirectoryTest
    extends Quick_Test_Case
{
    protected $_testdir = "/tmp/test-fd";
    protected $_prefix = 'test-';

    public function setUp( ) {
        system("rm -rf $this->_testdir; mkdir $this->_testdir");
        $this->_cut = new Quick_Store_FileDirectory($this->_testdir, $this->_prefix);
        touch("$this->_testdir/omit");
    }

    public function tearDown( ) {
        system("rm -rf $this->_testdir");
    }

    public function testSetShouldSetFileContentsPrefixedWithPrefix( ) {
        $id = uniqid();
        $this->_cut->set($id, $id);
        $this->assertFileExists("$this->_testdir/$this->_prefix$id");
        $this->assertEquals($id, file_get_contents("$this->_testdir/$this->_prefix$id"));
    }

    public function testGetShouldReturnFileContents( ) {
        $id = uniqid();
        $this->_cut->set($id, $id);
        $this->_cut->set("$id-2", "$id-2");
        $this->assertEquals($id, $this->_cut->get($id));
    }

    public function testAddShouldSetValueAndReturnTrueIfFileNotExists( ) {
        $ok = $this->_cut->add('a', 123);
        $this->assertTrue($ok);
        $this->assertEquals(123, $this->_cut->get('a'));
    }

    public function testAddShouldReturnFalseAndNotChangeContentsIfFileAlreadyExists( ) {
        $this->_cut->add('a', 1);
        $ok = $this->_cut->add('a', 2);
        $this->assertFalse($ok);
        $this->assertEquals(1, $this->_cut->get('a'));
    }

    public function testDeleteShouldRemoveFileAndReturnTrue( ) {
        $this->_cut->set('a', 1);
        $ok = $this->_cut->delete('a');
        $this->assertFileNotExists("$this->_testdir/{$this->_prefix}a");
        $this->assertTrue($ok);
    }

    public function testDeleteShouldReturnFalseIfFileNotExists( ) {
        $ok = $this->_cut->delete('a');
        $this->assertFalse($ok);
    }

    public function testGetNamesShouldReturnNames( ) {
        $this->_cut->set('a', 1);
        $this->_cut->set('aa', 2);
        $ret = $this->_cut->getNames();
        sort($ret);
        $this->assertEquals(array('a', 'aa'), $ret);
    }

    public function testExistsShouldReturnExistence( ) {
        $this->assertFalse($this->_cut->exists('a'));
        $this->_cut->set('a', 1);
        $this->assertTrue($this->_cut->exists('a'));
        $this->_cut->delete('a');
        $this->assertFalse($this->_cut->exists('a'));
    }

    public function testGetFilenameShouldReturnNameOfFileWithContents( ) {
        $this->_cut->set("foo", 1234);
        $filename = $this->_cut->getFilename("foo");
        $this->assertContains("foo", $filename);
        $this->assertEquals("1234", file_get_contents($filename));
    }

    public function testGetFileShouldReturnNameOfFileWithContents( ) {
        $this->_cut->set("foo", 1);
        $this->assertEquals($this->_cut->getFilename("foo"), $this->_cut->getFile("foo"));
    }

    public function testGetFileShouldPlaceContentsIntoFile( ) {
        $this->_cut->set("foo", 1234);
        $filename = tempnam("/tmp", "unit-");
        $this->_cut->getFile("foo", $filename);
        $contents = file_get_contents($filename);
        unlink($filename);
        $this->assertEquals("1234", $contents);
    }

    public function xx_testSpeed( ) {
        $testdir = "/mnt/test-fd";
        system("rm -rf $testdir; mkdir $testdir");
        $cut = new Quick_Store_FileDirectory($testdir, $this->_prefix);
        $timer = new Quick_Test_Timer();
        $timer->calibrate(2000, array($this, '_testSpeedNull'), array($cut, 'foo', 1));
        $tm = microtime(true);
        for ($i=0; $i<10000; ++$i) $cut->set("a-$i", $i);
        $tm = microtime(true) - $tm;
        echo "AR: FileDirectory: set 10k items in $tm sec\n";
        // ext3: 1k in 0.045 sec, 10k in 0.51 sec, 100k in 6.43 sec
        // ext3: 1k in 0.023 sec, 10k in 0.238 sec, 100k in >5 sec
        // shm: 10k in .12 sec
        // ext2: 10k in 1.22 sec (much slower than ext3/ext4)
        // ext4: 10k in .30 sec; 10k in 1.22 sec w/o journal
        // xfs: 10k in .55 sec
        // reiserfs: 10k in .40 sec (very repeatable!)
        // jfs: 10k in .53 sec
        echo $timer->timeit(10000, 'set', array($this, '_testSpeedSet'), array($cut, 'foo', 1));
        // ext3: 55k/sec in bursts, 30k/sec sustained (down to 20k/sec if 10k other files in dir)
        // ext2: 95k/sec, sustained (much faster for appends... journal overhead? try ext4 w/o journal?)
        // ext4: 95k/sec w/o journal; 100/sec w/ journal (EEEK!... how to mkfs a usable ext4 fs??)
        // xfs: 82/sec
        // reiserfs: 28k/sec (very repeatable!)
        // jfs: 10k-60k/sec, varies a lot run to run (?timed only 100 runs?)
        // nb: journal contention?  nb: 133k/sec (vs 30k/s) to append not overwrite! (ie, for queues, fifos)
        echo $timer->timeit(100, 'getNames', array($this, '_testSpeedGetNames'), array($cut, 'foo', 1));
        // ext3: 10k names @ 73/sec; w/ glob: 75.5/sec; shm: @ 84/sec, shm w/ glob: @ 94/sec
        // ext2: 10k names @ 89/sec
        // ext4: 10k names @ 73/sec; @ 91/sec w/o journal
        // xfs: 10k names @ 88/sec
        // resierfs: 10k names @ 82/sec
        // jfs: 10k names @ 73/sec
        // nb: ext3: 1 file in 123k/sec
        system("rm -rf $testdir");

        // conclusion:  for many items, use a journal.  For many touches to the same file, do not.
    }

    public function _testSpeedNull( $cut, $a, $b ) {
    }

    public function _testSpeedSet( $cut, $name, $value ) {
        $cut->set($name, $value);
    }

    public function _testSpeedGetNames( $cut, $a, $b ) {
        return $cut->getNames();
    }
}
