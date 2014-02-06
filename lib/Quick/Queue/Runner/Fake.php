<?

/**
 * Pretend queue job runner, for testing.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Runner_Fake
    implements Quick_Queue_Runner
{
    protected $_queueConfig, $_config = array();
    protected $_batches = array();

    public function __construct( Quick_Queue_Config $queueConfig ) {
        $this->_queueConfig = $queueConfig;
        $this->_config = & $queueConfig->shareConfig();
    }

    public function configure( $type, $name, $value ) {
        $this->_queueConfig->set($type, $name, $value);
    }

    public function runBatch( $jobtype, Quick_Queue_Batch $batch ) {
        // jobs are immediately done!
        $this->_batches[$jobtype][] = $batch;
        $batch->width = 1;
        return true;
    }

    public function getDoneJobtypes( ) {
        return array_keys($this->_batches);
    }

    public function getDoneBatch( $jobtype ) {
        $ret = array();
        $batch = array_pop($this->_batches[$jobtype]);
        if (!$this->_batches[$jobtype]) unset($this->_batches[$jobtype]);

        // fake runner echoes its input
        $pid = getmypid();
        foreach ($batch->jobs as $key => $input) {
            // for each done job, fake a result
            $ret[$key] = array(
                'status' => 0,
                'pid' => $pid,
                'result' => $input,
            );
        }
        $batch->results = & $ret;

        return $batch;
    }
}
