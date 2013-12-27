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
    protected $_jobs = array();

    public function __construct( Quick_Queue_Config $queueConfig ) {
        $this->_queueConfig = $queueConfig;
        $this->_config = & $queueConfig->shareConfig();
    }

    public function configure( $type, $name, $value ) {
        $this->_queueConfig->set($type, $name, $value);
    }

    public function runBatch( $jobtype, Array $datasets ) {
        // jobs are immediately done!
        if (isset($this->_jobs[$jobtype])) $this->_jobs[$jobtype] += $datasets;
        else $this->_jobs[$jobtype] = $datasets;
        return true;
    }

    public function getDoneJobtypes( ) {
        return array_keys($this->_jobs);
    }

    public function & getDoneBatch( $jobtype ) {
        $ret = array();
        foreach ($this->_jobs[$jobtype] as $key => $input) {
            // for each done job, fake a result
            $ret[$key] = array(
                'status' => 0,
                'result' => $input,
            );
        }
        unset($this->_jobs[$jobtype]);
        return $ret;
    }
}
