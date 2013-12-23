<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Store_FileDirectoryTest
    extends Quick_Test_Case
{
    const DIR = "/tmp/test-q";

    static public function setUpBeforeClass( ) {
        $dir = self::DIR;
        `rm -rf $dir; mkdir $dir`;
    }

    static public function tearDownAfterClass( ) {
        $dir = self::DIR;
        `rm -rf $dir`;
    }

    public function setUp( ) {
        $dir = self::DIR;
        $files = "$dir/" . "*";
        `rm -rf $files`;
        $this->_store = new Quick_Store_FileDirectory($dir, "");
        $this->_cut = new Quick_Queue_Store_FileDirectory($this->_store);
    }

    public function testAddJobsShouldCreateLogfile( ) {
        $this->_cut->addJobs("jobtype-1", array("a", "b", "c"));
        $this->assertFileExists($this->_store->getFilename("jobtype-1"));
    }

    public function testAddJobsShouldNewlineTerminateTheDatasets( ) {
        $this->_cut->addJobs("jobtype-2", array("a", "b\n", "c"));
        $this->assertEquals("a\nb\nc\n", file_get_contents($this->_store->getFilename("jobtype-2")));
    }

    public function testGetJobsShouldRetrieveJobsAddedWithAddJobsElseEmptyArray( ) {
        $this->_cut->addJobs("jobtype-3", array("a", "b", "c"));
        $this->assertEquals(array("a\n", "b\n"), array_values($this->_cut->getJobs("jobtype-3", 2)));
        $this->assertEquals(array("c\n"), array_values($this->_cut->getJobs("jobtype-3", 2)));
        $this->assertEquals(array(), array_values($this->_cut->getJobs("jobtype-3", 2)));
    }

    public function testGetJobsShouldAssignUniqueKeysToTheDatasets( ) {
        $this->_cut->addJobs("jobtype-4", array("a", "b", "c"));
        $jobs = $this->_cut->getJobs("jobtype-4", 2);
        $keys = array_unique(array_keys($jobs));
        $this->assertEquals(count($jobs), count($keys));
    }

    public function testUngetJobsShouldRequeueTheJobs( ) {
        $this->_cut->addJobs("jobtype-5", array("a", "b"));
        $jobs = $this->_cut->getJobs("jobtype-5", 10);
        $this->assertFalse(file_exists($this->_store->getFilename("jobtype-5")));
        $this->_cut->ungetJobs("jobtype-5", array_keys($jobs));
        $this->assertTrue(file_exists($this->_store->getFilename("jobtype-5")));
        $this->assertEquals("a\nb\n", file_get_contents($this->_store->getFilename("jobtype-5")));
        $jobs2 = $this->_cut->getJobs("jobtype-5", 10);
        // the unique keys only persist while the job is running, not through a requeue
        $this->assertEquals(array_values($jobs), array_values($jobs2));
    }

    public function testRetryJobsShouldCallUnget( ) {
        $cut = $this->getMockSkipConstructor('Quick_Queue_Store_FileDirectory', array('ungetJobs'));
        $jobtype = uniqid();
        $jobs = array('a', 'b', 'c');
        $cut->expects($this->once())->method('ungetJobs')->with($jobtype, $jobs)->will($this->returnValue($cut));
        $cut->retryJobs($jobtype, $jobs);
    }

    public function testAddJobsShouldReturnThis( ) {
        $this->assertEquals($this->_cut, $this->_cut->addJobs("type", array()));
    }

    public function testUngetJobsShouldReturnThis( ) {
        $this->assertEquals($this->_cut, $this->_cut->UngetJobs("type", array()));
    }

    public function testDeleteJobsShouldReturnThis( ) {
        $this->assertEquals($this->_cut, $this->_cut->DeleteJobs("type", array()));
    }

    public function testRetryJobsShouldReturnThis( ) {
        $this->assertEquals($this->_cut, $this->_cut->RetryJobs("type", array()));
    }
}
