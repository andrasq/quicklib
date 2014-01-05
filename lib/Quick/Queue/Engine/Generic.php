<?

/**
 * Queue engine for running tasks.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Engine_Generic
    implements Quick_Queue_Engine
{
    protected $_store, $_scheduler, $_runner;
    protected $_queueConfig, $_archiver;

    protected $_totalTime = 0;          // time the engine spent running jobs
    protected $_totalCount = 0;         // number of jobs launched
    protected $_runCount = 0;           // number of jobs that actually started

    protected $_capCount = 128;         // task count limit on concurrent tasks
    protected $_capWeight = 16;         // cpu limit on concurrent tasks
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
        return $this;
    }

    public function setArchiver( Quick_Queue_Archiver $archiver ) {
        $this->_archiver = $archiver;
        return $this;
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

        $this->_totalTime += (microtime(true) - $startTime);
        return $this;
    }

    public function finish( ) {
        // wait for still running tasks
        $startTime = microtime(true);
        $this->_waitForRunningJobs();
        $this->_totalTime += (microtime(true) - $startTime);
    }

    public function getStatus( Quick_Queue_Status $status ) {
        /**
         server, load, procs/threads, date
         procs forked/sec, http calls / sec, messages sent / sec (? gearman?)
         **/
        //$status->set('system', 'name', php_uname('n'));
        //$status->set('system', 'date', date("Y-m-d H:i:s"));

        $la = explode(" ", trim(file_get_contents("/proc/loadavg")));
        $status->set('system', 'load', array($la[0], $la[1], $la[2]));
        $status->set('system', 'threads', $la[3]);

        $status->set('queue', 'runtime', $this->_totalTime);
        $status->set('queue', 'started', $this->_totalCount);
        $status->set('queue', 'finished', $this->_runCount);
        $status->set('queue', 'running', $this->_runningCount);

        $sts = new Quick_Queue_Status();
        $this->_store->getStatus('store', $sts);
        $status->set('jobs', 'jobcount', count($sts->get('store', 'jobtypes')));
        $status->set('jobs', 'jobtypes', $sts->get('store', 'jobtypes'));
        $status->set('jobs', 'batchcount', count($sts->get('store', 'batches')));
        $status->set('jobs', 'batchtypes', array_unique(array_keys($sts->get('store', 'batches'))));
        $status->set('jobs', 'batches', $sts->get('store', 'batches'));

        return $status;
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
            $batch = $this->_runner->getDoneBatch($jobtype);
            $this->_scheduler->setBatchDone($jobtype, $batch);
            $this->_processResults($jobtype, $batch);
            $n = $batch->count;
            $this->_runningCount -= $n;
            $this->_runningWeight -= $this->_queueConfig->get('weight', $jobtype);
            // note: only retire 1 batch at a time, faster to interleave retiring and launching
            break;
        }
        return true;
    }

    protected function _waitForRunningJobs( ) {
        do {
            $this->_retireDoneJobs();
        } while ($this->_runningCount > 0 && (1 + usleep(2000)));
    }

    protected function _launchNewJobs( $limit ) {
        $jobtype = $this->_scheduler->getJobtypeToRun();
        if ($jobtype && ($batch = $this->_scheduler->getBatchToRun($jobtype, $limit))) {
            if ($this->_runner->runBatch($jobtype, $batch)) {
                // jobs started, count them and keep a copy for archival
                $n = count($batch->jobs);
                $this->_totalCount += $n;
                $this->_runningCount += $n;
                $this->_runningWeight = $this->_queueConfig->get('weight', $jobtype);
                return true;
            }
            else {
                // if unable to start the jobs, try later
                $this->_store->ungetJobs($jobtype, array_keys($batch->jobs));
            }
        }
        return false;
    }

    protected function _processResults( $jobtype, Quick_Queue_Batch $batch ) {
        $jobresults = & $batch->results;
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
        
        if ($this->_archiver) {
            $jobs = & $batch->jobs;
            $this->_archiver->archiveJobResults($jobtype, $jobs, $jobresults);
        }

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
