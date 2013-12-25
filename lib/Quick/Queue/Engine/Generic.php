<?

/**
 * Queue engine for running tasks.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Engine_Generic
{
    protected $_store, $_scheduler, $_runner;
    protected $_queueConfig, $_archiver;

    protected $_totalTime = 0;          // time the engine spent running jobs
    protected $_totalCount = 0;         // number of jobs launched
    protected $_runCount = 0;           // number of jobs that actually started

    protected $_capCount = 128;         // task count limit on concurrent tasks
    protected $_capWeight = 16;         // cpu limit on concurrent tasks
    protected $_runningTasks = array(); // image of each running tasks, kept for archival
    protected $_runningCount = 0;       // number of concurrent tasks currently running
    protected $_runningWeight = 0;      // total cpu usage density currently running

    protected $_runStopTime;
    protected $_runStopCount;

    public function __construct( Quick_Queue_Store $store, Quick_Queue_Scheduler $scheduler, Quick_Queue_Runner $runner ) {
        $this->_store = $store;
        $this->_scheduler = $scheduler;
        $this->_runner = $runner;
        $this->_queueConfig = new Quick_Queue_Config();
        $this->_archiver = false;
    }

    public function setConfig( Array $jobtypeConfig ) {
        // TBD
    }

    public function setArchiver( Quick_Queue_Archiver $archiver ) {
        $this->_archiver = $archiver;
    }

    public function run( $runDurationLimit = 1.00, $runTaskcountLimit = 1000000000 ) {
        $startTime = microtime(true);
        $this->_runStopTime = $startTime + $runDurationLimit;
        $this->_runStopCount = $this->_totalCount + $runTaskcountLimit;

        while ($this->_shouldContinueToRun()) {
            $this->_retireDoneJobs();
            if ($this->_shouldLaunchNewJobs()) {
                if (!$this->_launchNewJobs($this->_runStopCount - $this->_totalCount)) {
                    // no suitable jobs available to run, check again in a bit
                    usleep(2000);
                }
            }
            else {
                // resource constrained, try to run jobs in a little bit
                usleep(2000);
            }
        }

        // wait for still running tasks
        $this->_retireRunningJobs();

        $this->_totalTime += (microtime(true) - $startTime);
    }


    protected function _shouldContinueToRun( ) {
        return (
            microtime(true) < $this->_runStopTime &&
            $this->_totalCount < $this->_runStopCount &&
            true
        );
    }

    protected function _shouldLaunchNewJobs( ) {
        return (
            $this->_runningCount < $this->_capCount &&
            $this->_runningWeight < $this->_capWeight &&
            true
        );
    }

    protected function _retireDoneJobs( ) {
        if (! $jobtypes = $this->_runner->getDoneJobtypes())
            return false;
        foreach ($jobtypes as $jobtype) {
            $jobresults = $this->_runner->getDoneJobs($jobtype);
            $this->_scheduler->setBatchDone($jobtype, $jobresults);
            $this->_processResults($jobtype, $jobresults);
            if ($this->_archiver)
                $this->_runningTasks = array_diff_key($this->_runningTasks, $jobresults);
            $n = count($jobresults);
            $this->_runningCount -= $n;
            $this->_runningWeight -= $this->_queueConfig->get('weight', $jobtype);
            // note: only retire 1 batch at a time, faster to interleave retiring and launching
            break;
        }
        return true;
    }

    protected function _retireRunningJobs( ) {
        do {
            $this->_retireDoneJobs();
        } while ($this->_runningCount > 0 && (1 + usleep(2000)));
    }

    protected function _launchNewJobs( $limit ) {
        $jobtype = $this->_scheduler->getJobtypeToRun();
        if ($jobtype && ($jobs = $this->_scheduler->getBatchToRun($jobtype, $limit))) {
            if ($this->_runner->runJobs($jobtype, $jobs)) {
                // jobs started, count them and keep a copy for archival
                $n = count($jobs);
                $this->_totalCount += $n;
                $this->_runningCount += $n;
                $this->_runningWeight = $this->_queueConfig->get('weight', $jobtype);
                if ($this->_archiver)
                    $this->_runningTasks += $jobs;
                return true;
            }
            else {
                // if unable to start the jobs, try later
                $this->_store->ungetJobs($jobtype, array_keys($jobs));
            }
        }
        return false;
    }

    protected function _processResults( $jobtype, Array & $jobresults ) {
        $succeeded = $unrun = $failed = array();
        foreach ($jobresults as $key => $result) {
            if (!is_array($result))
                throw new Quick_Queue_Exception("invalid task result, not an array:\n" . var_export($result, true));

            /**
            Job results hash expected to contain eg:
                array('status' => 0, 'result' => "") for voluntary returns
                array('status' => 2, 'message' => "php error: division by zero") for errors and exceptions
                array('status' => -1, 'message' => 'notrun') for tasks not run
            All task keys must be present to know which tasks were candidates for running.
            **/

            if (!isset($result['status'])) $succeeded[] = $key;
            else switch($result['status']) {
            case Quick_Queue_Runner::RUN_OK:
            case Quick_Queue_Runner::RUN_FAILED:
                $succeeded[] = $key;
                break;
            case Quick_Queue_Runner::RUN_ERROR:
            default:
                $failed[] = $key;
                break;
            case Quick_Queue_Runner::RUN_UNRUN:
                $unrun[] = $key;
                break;
            }
        }
        
        if ($this->_archiver)
            $this->_archiver->archiveJobResults($jobtype, array_intersect_key($this->_runningTasks, $jobresults), $jobresults);

        if ($succeeded) {
            $this->_store->deleteJobs($jobtype, $succeeded);
            $this->_runCount += count($succeeded);
        }
        if ($failed) {
            $this->_store->retryJobs($jobtype, $failed);
            $this->_runCount += count($failed);
        }
        if ($unrun) {
            $this->_store->ungetJobs($jobtype, $unrun);
        }
    }
}
