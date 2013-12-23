<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Store_ArrayTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Queue_Store_Array();
    }

    public function testAddJobsShouldSaveInStore( ) {
        $this->_cut->addJobs('a', array(1));
        $this->_cut->addJobs('a', array(2,3));
        $this->assertEquals(array(1,2,3), $this->_cut->jobs['a']);
    }

    public function testGetJobsShouldReturnJobsOfJobtype( ) {
        $this->_cut->addJobs('a', array(1,2,3,4,5));
        $this->_cut->addJobs('b', array(11,22,33));
        $jobs = $this->_cut->getJobs('a', 3);
        $this->assertEquals(array(1,2,3), array_values($jobs));
        $this->assertEquals(array(4,5), $this->_cut->jobs['a']);
        $this->assertEquals(array(11,22,33), $this->_cut->jobs['b']);
    }

    public function testGetJobsShouldIndexJobsByUniqueKeys( ) {
        $this->_cut->addJobs('a', range(1, 1000));
        $jobs = $this->_cut->getJobs('a', 1000);
        $this->assertEquals(1000, count(array_unique(array_keys($jobs))));
    }

    public function testGetJobsShouldReturnEmptyArrayWhenNoJobs( ) {
        $this->assertEquals(array(), $this->_cut->getJobs('a', 1));
    }

    public function testDeleteJobsShouldRemoveJobs( ) {
        $this->_cut->addJobs('a', array(1,2,3,4,5));
        $jobs = $this->_cut->getJobs('a', 3);
        $jobs += $this->_cut->getJobs('a', 1);
        $this->assertEquals($jobs, $this->_cut->_pendingJobs['a']);
        $this->_cut->deleteJobs('a', array_keys($jobs));
        $this->assertTrue(empty($this->_cut->_pendingJobs['a']));
    }

    public function testUngetJobsShouldRequeueJobs( ) {
        $this->_cut->addJobs('a', array(1,2,3,4,5));
        $jobs = $this->_cut->getJobs('a', 3);
        $this->_cut->ungetJobs('a', array_keys($jobs));
        $this->assertEquals(array(4,5,1,2,3), $this->_cut->jobs['a']);
    }

    public function testRetryJobsShouldRequeueJobs( ) {
        $this->_cut->addJobs('a', array(1,2,3,4,5));
        $jobs = $this->_cut->getJobs('a', 3);
        $this->_cut->retryJobs('a', array_keys($jobs));
        $this->assertEquals(array(4,5,1,2,3), $this->_cut->jobs['a']);
    }
}
