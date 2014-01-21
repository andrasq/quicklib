<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_TempfileTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_id = uniqid();
        $this->_cut = new Quick_Data_Tempfile("/tmp", "test-{$this->_id}-");
    }

    public function testConstructShouldCreateFileInDirectoryWithPrefixAndGetPathnameShouldReturnFilename( ) {
        $filename = $this->_cut->getPathname();
        $this->assertContains("/tmp/test-{$this->_id}-", $filename);
        $this->assertTrue(file_exists($filename));
    }

    public function testDestructShouldRemoveFile( ) {
        $filename = (string)$this->_cut;
        unset($this->_cut);
        $this->assertFalse(file_exists($filename));
    }

    public function testStringCastShouldReturnFilename( ) {
        $this->assertEquals($this->_cut->getPathname(), (string)$this->_cut);
    }

    public function testGetContentsShouldReturnContents( ) {
        $contents = uniqid();
        file_put_contents($this->_cut->getPathname(), $contents);
        $this->assertEquals($contents, $this->_cut->getContents());
    }

    public function testPutContentsShouldOverwriteContents( ) {
        $contents = uniqid();
        $this->_cut->putContents($contents);
        $this->_cut->putContents($contents);
        $this->assertEquals($contents, $this->_cut->getContents());
    }

    public function testAppendContentsShouldAppendContents( ) {
        $contents = uniqid();
        $this->_cut->appendContents($contents);
        $this->_cut->appendContents($contents);
        $this->assertEquals($contents . $contents, $this->_cut->getContents());
    }

    public function xx_testSpeed( ) {
        error_reporting(E_ALL);
        $tm = microtime(true);
        for ($i=0; $i<10000; ++$i) {
            $file = new Quick_Data_Tempfile();
            unset($file);
        }
        $tm = microtime(true) - $tm;
        echo "AR: 10k new tempfiles in $tm sec\n";
        // 55k/s tempfiles created, /tmp ext3 noatime
        // we can build our own tempfiles, but only about 44k/sec (62.5k/s create, + unlink):
        //   do { $name = sprintf("%s/%s%08x", $dir, $prefix, mt_rand(1, 2000000000)); }
        //   while (file_exists($name) || !($fp = fopen($name, 'x')));
    }
}
