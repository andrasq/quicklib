<?

/**
 * The scheduler decides which job to run next.
 * It tracks the count of currently running jobs by type to
 * help make its scheduling decisions.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Scheduler_Random
    implements Quick_Queue_Scheduler
{
    protected $_store;
    protected $_jobtypes = array();
    protected $_batchcounts = array();
    protected $_config = array(
        // __default is used for unspecified types
        'batchsize' => array(),
    );
    protected $_nextJoblistRefreshTime = 0;
    protected $_joblistRefreshValue, $_joblistRefreshInterval;

    public function __construct( Quick_Queue_Store $store, Quick_Queue_Config $queueConfig ) {
        $this->_store = $store;
        $this->_queueConfig = $queueConfig;
        $this->_config = & $queueConfig->shareConfig();
        $knownConfigs = array(
            'batchsize' => 1,
            'batchlimit' => 1,
            'weight' => 1,
        );
        foreach ($knownConfigs as $name => $default) {
            if ($queueConfig->get($name, '__default') === null)
                $queueConfig->set($name, '__default', $default);
        }
        //$this->setJoblistRefreshValue(new Quick_Data_AdaptiveValue_SlidingWindow(.004, .001, 10.000, -.01, 2));
        $this->setJoblistRefreshValue(new Quick_Data_AdaptiveValue_Constant(.05));
    }
    
    public function setJoblistRefreshValue( Quick_Data_AdaptiveValue $value ) {
        $this->_joblistRefreshValue = $value;
        $this->_joblistRefreshInterval = $this->_joblistRefreshValue->get();
        return $this;
    }

    public function getJoblistRefreshValue( ) {
        return $this->_joblistRefreshValue;
    }

    public function configure( $what, $name, $value ) {
        // if ($name === '*') $name = '__default';
        switch ($what) {
        case self::SCHED_BATCHSIZE:
            $this->_queueConfig->configure($what, $name, $value);
            break;
        default:
            throw new Quick_Queue_Exception("$what: unknown config setting");
        }
        return $this;
    }

    public function setConfig( $what, $jobtypeValues ) {
        $this->_queueConfig->setConfig($what, $jobtypeValues);
        return $this;
    }

    public function getConfig( $what = null ) {
        return $this->_queueConfig->getConfig($what);
    }

    public function getJobtypeToRun( ) {
        // refreshing the list of jobtypes is a slow operation, so reuse the list if possible
        if (($now = microtime(true)) >= $this->_nextJoblistRefreshTime) {
            $this->_refreshJobtypes();
            $this->_nextJoblistRefreshTime = microtime(true) + $this->_joblistRefreshInterval;
        }

        if ($this->_jobtypes) {
            $max = count($this->_jobtypes) - 1;
            for ($i=0; $i<5; ++$i) {
                $jobtype = $this->_jobtypes[mt_rand(0, $max)];
                if (empty($this->_batchcounts[$jobtype]) ||
                    $this->_batchcounts[$jobtype] < $this->_queueConfig->get('batchlimit', $jobtype))
                {
                    return $jobtype;
                }
                // running multiple concurrent batches bumps throughput (batchlimit=4):
                // batchsize=1: from 300 to 900/sec
                // batchsize=5: from 1500 to 4100/sec
                // batchsize=20: from 5400 to 12500/sec
            }
        }
        return false;
    }

    public function getBatchToRun( $jobtype, $limit = null ) {
        if ($limit === null) $limit = $this->_queueConfig->get('batchsize', $jobtype);
        $batchsize = $this->_queueConfig->get('batchsize', $jobtype);
        $limit = isset($limit) ? min($limit, $batchsize) : $batchsize;
        $jobs = $this->_store->getJobs($jobtype, $limit);
        if (isset($this->_batchcounts[$jobtype]))
            ++$this->_batchcounts[$jobtype];
        else
            $this->_batchcounts[$jobtype] = 1;
        return new Quick_Queue_Batch($jobtype, $jobs);
    }

    public function setBatchDone( $jobtype, Quick_Queue_Batch $batch ) {
        $jobtype = $batch->jobtype;
        if (isset($this->_batchcounts[$jobtype])) {
            if (--$this->_batchcounts[$jobtype] <= 0)
                unset($this->_batchcounts[$jobtype]);
        }
    }

    protected function _refreshJobtypes( ) {
        $tm = microtime(true);
        // make jobtypes numerically indexed [0..N-1] for random selection
        $this->_jobtypes = array_values($this->_store->getJobtypes());
        $tm = microtime(true) - $tm;
        // adjust the refresh interval for very many or very few jobtypes
        $this->_joblistRefreshInterval = $this->_joblistRefreshValue->adjust($tm >= $this->_joblistRefreshInterval);
    }
}
