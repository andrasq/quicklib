<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Scheduler_RandomExposer
    extends Quick_Queue_Scheduler_Random
{
    public $_batchcounts = array();
    public $_config, $_queueConfig;
    public $_joblistRefreshInterval;

    public function _refreshJobtypes( ) {
        parent::_refreshJobtypes();
    }
}

class Quick_Queue_Scheduler_RandomTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_store = new Quick_Queue_Store_Array();
        $this->_store->addJobs('a', array(1,2,3));
        $this->_config = array();
        $this->_queueConfig = new Quick_Queue_Config($this->_config);
        $this->_cut = new Quick_Queue_Scheduler_RandomExposer($this->_store, $this->_queueConfig);
    }

    public function testConstructorShouldProvidingMissingDefaults( ) {
        $this->assertContains("__default", array_keys($this->_cut->getConfig('batchsize')));
        $this->assertContains("__default", array_keys($this->_cut->getConfig('batchlimit')));
    }

    public function testConfigureBatchSizeShouldChangeNumberInBatch( ) {
        $this->_cut->configure(Quick_Queue_Scheduler::SCHED_BATCHSIZE, '__default', 2);
        $jobtype = 'a';
        $batch = $this->_cut->getBatchToRun($jobtype);
        $this->assertEquals(2, count($batch->jobs));
        $this->assertEquals(2, $batch->count);
    }

    public function testGetConfigShouldReturnConfiguration( ) {
        $this->_cut->configure(Quick_Queue_Scheduler::SCHED_BATCHSIZE, 'foo', 123);
        $batchsizes = $this->_cut->getConfig(Quick_Queue_Scheduler::SCHED_BATCHSIZE);
        unset($batchsizes['__default']);
        $this->assertEquals(array('foo' => 123), $batchsizes);
    }

    public function testSetConfigShouldSetConfiguration( ) {
        $this->_cut->setConfig(Quick_Queue_Scheduler::SCHED_BATCHSIZE, array('a' => 1, 'b' => 2));
        $this->assertEquals(array('a' => 1, 'b' => 2), $this->_cut->getConfig('batchsize'));
    }

    public function testGetBatchToRunShouldSelectFromAvailableJobtypes( ) {
        $this->_store->addJobs('b', array(11,22,33));

        $jobtype = $this->_cut->getJobtypeToRun();
        $this->assertContains($jobtype, array('a', 'b'));

        $batch = $this->_cut->getBatchToRun('a');
        $jobs = $batch->jobs;
        $this->assertEquals(1, $batch->count);
        $this->assertEquals(1, count($jobs));
        $this->assertEquals(1, current($jobs));
        $this->assertEquals(array(2,3), $this->_store->jobs['a']);

        $batch = $this->_cut->getBatchToRun('b');
        $jobs = $batch->jobs;
        $this->assertEquals(1, $batch->count);
        $this->assertEquals(1, count($jobs));
        $this->assertEquals(11, current($jobs));
        $this->assertEquals(array(22,33), $this->_store->jobs['b']);
    }

    public function testGetBatchShouldTrackRunningJobsCountByJobtype( ) {
        $jobtype = 'a';
        $jobs = $this->_cut->getBatchToRun($jobtype);
        $this->assertEquals(1, $this->_cut->_batchcounts[$jobtype]);
        $this->_cut->setBatchDone($jobtype, $jobs);
        $this->assertTrue(empty($this->_cut->_batchcounts[$jobtype]));
        $jobs2 = $this->_cut->getBatchToRun($jobtype);
        $this->assertEquals(1, $this->_cut->_batchcounts[$jobtype]);
    }

    public function testSetBatchDoneShouldClearJobsFromRunning( ) {
        $jobtype = 'a';
        $jobs = $this->_cut->getBatchToRun($jobtype);
        $this->_cut->setBatchDone($jobtype, $jobs);
        $this->assertTrue(empty($this->_cut->running[$jobtype]));
    }

    public function testGetJobtypeToRunShouldCallRefreshJobtypes( ) {
        // always called on the first call
        $cut = $this->getMockSkipConstructor('Quick_Queue_Scheduler_Random', array('_refreshJobtypes'));
        $cut->expects($this->once())->method('_refreshJobtypes');
        $cut->getJobtypeToRun();
    }

    public function testRefreshJobtypesShouldAdjustJoblistRefreshInterval( ) {
        $value = $this->getMockSkipConstructor('Quick_Data_AdaptiveValue_SlidingWindow', array('adjust'));
        $value->expects($this->once())->method('adjust')->will($this->returnValue(12345));
        $this->_cut->setJoblistRefreshValue($value);
        $this->_cut->_refreshJobtypes();
        $this->assertEquals(12345, $this->_cut->_joblistRefreshInterval);
    }
}
