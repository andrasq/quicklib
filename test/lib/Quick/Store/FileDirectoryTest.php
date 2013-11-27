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
//        system("rm -rf $this->_testdir");
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

    public function testGetFileShouldReturnNameOfFileWithContents( ) {
        $this->_cut->set("foo", 1234);
        $filename = $this->_cut->getFile("foo");
        $this->assertContains("foo", $filename);
        $this->assertEquals("1234", file_get_contents($filename));
    }

    public function testGetFileShouldPlaceContentsIntoFile( ) {
        $this->_cut->set("foo", 1234);
        $filename = tempnam("/tmp", "unit-");
        $this->_cut->getFile("foo", $filename);
        $contents = file_get_contents($filename);
        unlink($filename);
        $this->assertEquals("1234", $contents);
    }
}
