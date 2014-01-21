<?

/**
 * The Group runner runs batches in one or more other runners.
 * Each batch is done once all its runners are done.  Any problems
 * in any of the runners cause that job to be re-run by all runners.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Runner_Group
    implements Quick_Queue_Runner
{
    protected $_procs = array(), $_doneProcs = array();
    protected $_queueConfig, $_config = array();

    public function __construct( Quick_Queue_Config $queueConfig, Array $runners ) {
        $this->_queueConfig = $queueConfig;
        $this->_config = $queueConfig->shareConfig();
        $this->_runners = $runners;
    }

    public function configure( $type, $name, $value ) {
        $this->_queueConfig->set($type, $name, $value);
    }

    public function runBatch( $jobtype, Quick_Queue_Batch $batch ) {
        // runs only one batch at a time!  each sub-runner runs only one batch at a time!
        $ok = true;
        $this->_batch = $batch;
        foreach ($this->_runners as $runner) {
            // nb: cloning is very fast, even with huge arrays in the object
            // NOTE: could sequence runners for less cpu pressure, strobe w/ getDoneJobtypes
            if (! $runner->runBatch($jobtype, clone $batch))
                $ok = false;
        }
        return $ok;
    }

    public function getDoneJobtypes( ) {
        foreach ($this->_runners as $runner)
            if (! ($jobtypes = $runner->getDoneJobtypes()))
                return array();
        return $jobtypes;
    }

    public function getDoneBatch( $jobtype ) {
        $results = & $this->_batch->results;
        // pre-initialize job statuses to RUN_OK
        foreach ($this->_batch->jobs as $idx => & $data) {
            $results[$idx]['status'] = 0;
        }
        unset($data);

        foreach ($this->_runners as $runner) {
            $batch = $runner->getDoneBatch($jobtype);
            if (!$batch) throw new Quick_Queue_Exception("batch not done");
            foreach ($batch->results as $idx => $result) {
                // save the results and combine the separate statuses (ok=0, failed=1, error=2, unrun=3)
                $results[$idx]['results'] = $result;
                if ($result['status'] > $results[$idx]['status'])
                    $results[$idx]['status'] = $result['status'];
            }
        }
        return $this->_batch;
    }
}
