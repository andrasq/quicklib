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
    protected $_runningcounts = array();
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
            'batchsize',
            'weight',
        );
        foreach ($knownConfigs as $name) {
            if (empty($this->_configs[$name])) $this->_configs[$name] = array();
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
        // refreshing the list of jobtypes is a slow operation, only do it occasionally
        if (($now = microtime(true)) >= $this->_nextJoblistRefreshTime) {
            $this->_refreshJobtypes();
            $this->_nextJoblistRefreshTime = microtime(true) + $this->_joblistRefreshInterval;
        }

        if ($this->_jobtypes) {
            $max = count($this->_jobtypes) - 1;
            for ($i=0; $i<3; ++$i) {
                $jobtype = $this->_jobtypes[mt_rand(0, $max)];
                if (empty($this->_runningcounts[$jobtype])) {
                    // @FIXME: keep things simple, only allow one batch of a type at a time
                    // do not start a second batch while the first is still running
                    // this allows the task store to advance the checkpoint when the tasks finish
                    // NOTE: this cuts into performance by 20% or so
                    return $jobtype;
                }
            }
        }
        return false;
    }

    public function & getBatchToRun( $jobtype, $limit = null ) {
        $batchsize = $this->_getConfiguredBatchsize($jobtype);
        $limit = isset($limit) ? min($limit, $batchsize) : $batchsize;
        $jobs = $this->_store->getJobs($jobtype, $limit);
        if (isset($this->_runningcounts[$jobtype]))
            $this->_runningcounts[$jobtype] += count($jobs);
        else
            $this->_runningcounts[$jobtype] = count($jobs);
        return $jobs;
    }

    public function setBatchDone( $jobtype, Array & $jobs ) {
        if (isset($this->_runningcounts[$jobtype])) {
            $this->_runningcounts[$jobtype] -= count($jobs);
            if ($this->_runningcounts[$jobtype] <= 0)
                unset($this->_runningcounts[$jobtype]);
        }
    }

    protected function _refreshJobtypes( ) {
        $tm = microtime(true);
        $this->_jobtypes = $this->_store->getJobtypes();
        $tm = ($now = microtime(true)) - $tm;
        // adjust the refresh interval for very many or very few jobtypes
        $this->_joblistRefreshInterval = $this->_joblistRefreshValue->adjust($tm >= $this->_joblistRefreshInterval);
    }

    protected function _getConfiguredBatchsize( $jobtype ) {
        if (isset($this->_config['batchsize'][$jobtype]))
            return $this->_config['batchsize'][$jobtype];
        elseif (isset($this->_config['batchsize']['__default']))
            return $this->_config['batchsize']['__default'];
        else
            return 1;
    }
}
