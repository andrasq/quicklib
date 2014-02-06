<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Runner_Http
    implements Quick_Queue_Runner
{
    protected $_callers = array();
    protected $_queueConfig, $_config = array();
    protected $_batches = array();
    protected $_batchRunners = array();

    public function __construct( Quick_Queue_Config $queueConfig ) {
        $this->_queueConfig = $queueConfig;
        $this->_config = $queueConfig->shareConfig();
    }

    public function configure( $section, $name, $value ) {
        // WRITEME
    }

    public function runBatch( $jobtype, Quick_Queue_Batch $batch ) {
        $url = $this->_getUrlForJobtype($jobtype);
        $runner = $this->_getBatchRunner($jobtype, $url);
        if ($runner->runBatch($jobtype, $batch)) {
            $this->_batches[$jobtype][] = $runner;
            // batch->width set by actual jobtype-specific runner
            return true;
        }
    }

    public function getDoneJobtypes( ) {
        $ret = array();
        foreach ($this->_batches as $jobtype => $batches) {
            foreach ($batches as $batch)
                if ($batch->getDoneJobtypes()) $ret[] = $jobtype;
        }
        return $ret;
    }

    public function getDoneBatch( $jobtype ) {
        if (isset($this->_batches[$jobtype])) {
            foreach ($this->_batches[$jobtype] as $ix => $runner) {
                if ($runner->isDone()) {
                    unset($this->_batches[$jobtype][$ix]);
                    $this->_saveBatchRunner($jobtype, $runner);
                    if ($batch = $runner->getDoneBatch("dummy")) {
                        return $batch;
                        break;
                    }
                    else
                        throw new Quick_Queue_Exception("internal error: batch $ix is done, but no results\n" . print_r($runner, true));
                }
            }
        }
        return null;;
    }


    protected function _getUrlForJobtype( $jobtype ) {
        $runner = $this->_queueConfig->get('runner', $jobtype);
        if (substr($runner, 0, 4) !== 'http')
            throw new Quick_Queue_Exception("http runner: type $jobtype does not have an http runner, is $runner");
        return $runner;
    }

    protected function _saveBatchRunner( $jobtype, $runner ) {
        $this->_batchRunners[$jobtype][] = $runner;
        // cache no more than 100 batch runners of any one type
        if (isset($this->_batchRunners[$jobtype][100]))
            array_splice($this->_batchRunners[$jobtype], 0, 50);
    }

    protected function _getBatchRunner( $jobtype, $url ) {
        if (!empty($this->_batchRunners[$jobtype]))
            return array_pop($this->_batchRunners[$jobtype]);
        else
            return new Quick_Queue_Runner_HttpMulti('GET', $url);
    }
}
