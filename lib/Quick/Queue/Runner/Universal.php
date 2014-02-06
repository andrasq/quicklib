<?

/**
 * Universal job runner, can run jobs of any type.
 * Uses the passed-in runner factory to build jobtype-specific runners.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Runner_Universal
    implements Quick_Queue_Runner
{
    protected $_queueConfig, $_config;
    protected $_runnerFactory;
    protected $_sharedRunners = array(), $_runners = array();
    protected $_doneRunners = array();

    public function __construct( Quick_Queue_Config $queueConfig, Quick_Queue_Router_RunnerFactory $factory ) {
        $this->_queueConfig = $queueConfig;
        $this->_config = & $queueConfig->shareConfig();
        $this->_runnerFactory = $factory;
    }

    public function configure( $what, $jobtype, $value ) {
        $this->_queueConfig->set($what, $jobtype, $value);
    }

    public function runBatch( $jobtype, Quick_Queue_Batch $batch ) {
        $runner = $this->_runnerFactory->getRunner($jobtype);
        $ok = $runner->runBatch($jobtype, $batch);
        if (isset($runner->sharedType) && empty($this->_runners[$runner->sharedType]))
            $this->_sharedRunners[$runner->sharedType] = $runner;
        else
            $this->_runners[] = $runner;
        // $batch->width set by actual runner
        return $ok;
    }

    public function getDoneJobtypes( ) {
        // first retrieve all known done jobtypes
        if ($this->_doneRunners)
            return array_keys($this->_doneRunners);

        // then scan all runners and ask about any newly done types
        foreach ($this->_sharedRunners as $runner) {
            if ($types = $runner->getDoneJobtypes()) foreach ($types as $jobtype) {
                $this->_doneRunners[$jobtype][] = $runner;
            }
        }
        if ($this->_runners) foreach ($this->_runners as $idx => $runner) {
            if ($types = $runner->getDoneJobtypes()) foreach ($types as $jobtype) {
                $this->_doneRunners[$jobtype][] = $runner;
                // discrete runners run only one batch at a time, remove when done
                unset($this->_runners[$idx]);
            }
        }
        return array_keys($this->_doneRunners);
    }

    public function getDoneBatch( $jobtype ) {
        if (empty($this->_doneRunners[$jobtype]))
            throw new Quick_Queue_Exception("no done batches of jobtype $jobtype");
        $runner = array_pop($this->_doneRunners[$jobtype]);
        if (!$this->_doneRunners[$jobtype]) unset($this->_doneRunners[$jobtype]);
        $batch = $runner->getDoneBatch($jobtype);
        return $batch;
    }
}
