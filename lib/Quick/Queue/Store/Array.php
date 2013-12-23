<?

/**
 * Memory-only queue store for testing.
 * This is a quick hack on top of Queue_Store_FileDirectory,
 * replacing the file fifos with arrays.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Store_Array
    extends Quick_Queue_Store_FileDirectory
    implements Quick_Queue_Client, Quick_Queue_Store
{
    public $jobs;
    public $_pendingJobs = array();

    public function __construct( Array $store = array() ) {
        $this->jobs = $store;
        parent::__construct(new Quick_Store_FileDirectory("/nonesuch"));
    }

    protected function _fetchJobtypes( ) {
        return array_keys($this->jobs);
    }

    protected function & _fetchJobs( $jobtype, $limit ) {
        // parent class expects the fifo to get set by fetching
        $this->_fifos[$jobtype] = $jobtype;

        if (isset($this->jobs[$jobtype])) {
            $jobs = array_splice($this->jobs[$jobtype], 0, $limit);
            if (!$this->jobs[$jobtype]) unset($this->jobs[$jobtype]);
            return $jobs;
        }
        else {
            $ret = array();
            return $ret;
        }
    }

    public function addJobs( $jobtype, Array $datasets ) {
        foreach ($datasets as $data)
            $this->jobs[$jobtype][] = $data;
        return $this;
    }

    protected function & _omitFifoMetafiles( & $names ) {
        // override filename screening
        return $names;
    }

    protected function _getFifo( $jobtype ) {
        throw new Quick_Queue_Exception("error: fifos should not be accessed from Queue_Store_Array");
    }

    protected function _checkpointFifo( $fifo ) {
    }
}
