<?

/**
 * Queue task data stored in a FileDirectory of Fifo_Files,
 * with fifos named for the jobtype.  Jobtypes must not contain '/'.
 *
 * Newer php versions (5.4.9) allow the same-named method to exist
 * in multiple implemented interfaces; older versions of php do not
 * so Queue_Store and Queue_Client should be 
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Store_FileDirectory
    // implements must list intefaces in inheritance order
    implements Quick_Queue_Client, Quick_Queue_Store
{
    protected $_store;
    protected $_fifos = array(),$_pendingFifos = array();
    protected $_pendingJobs = array();
    protected $_jobCount = 0;

    public function __construct( Quick_Store_FileDirectory $store ) {
        $this->_store = $store;
        $this->_keys = new Quick_Data_UniqueNumber();
    }

    public function getJobtypes( ) {
        $filenames = $this->_fetchJobtypes();
        $jobtypes = $this->_omitFifoMetafiles($filenames);
        return $jobtypes;
    }

    public function getJobs( $jobtype, $limit ) {
        $jobs = $this->_assignKeysToJobs($this->_fetchJobs($jobtype, $limit));
        $this->_trackJobs($jobtype, $jobs);
        return $jobs;
    }

    public function deleteJobs( $jobtype, Array $keys ) {
        $this->_untrackJobs($jobtype, $keys);
        return $this;
    }

    public function ungetJobs( $jobtype, Array $keys ) {
        if ($keys) {
            foreach ($keys as $key)
                $keyvals[$key] = $this->_pendingJobs[$jobtype][$key];
            $this->addJobs($jobtype, $keyvals);
            $this->_untrackJobs($jobtype, $keys);
        }
        return $this;
    }

    public function retryJobs( $jobtype, Array $keys ) {
        return $this->ungetJobs($jobtype, $keys);
    }

    public function add( $jobtype, $data ) {
        return $this->addJobs($jobtype, array($data));
    }

    public function addJobs( $jobtype, Array $datasets ) {
        $fifo = $this->_getFifo($jobtype);
        foreach ($datasets as & $data)
            if (substr($data, -1) !== "\n") $data .= "\n";
        $fifo->fputs(implode('', $datasets));
        return $this;
    }


    protected function _fetchJobtypes( ) {
        return $this->_store->getNames();
    }

    protected function & _fetchJobs( $jobtype, $limit ) {
        $jobs = array();
        $fifo = $this->_getFifo($jobtype);
        for ($i=0; $i<$limit; ++$i) {
            $job = $fifo->fgets();
            if ($job === false) {
                $fifo->clearEof();
                $job = $fifo->fgets();
            }
            if ($job) {
                $jobs[] = $job;
            }
            else {
                $fifo->clearEof();
                break;
            }
        }
        return $jobs;
    }

    protected function _assignKeysToJobs( Array & $datasets ) {
        $jobs = array();
        foreach ($datasets as $data) {
            $jobs[$this->_keys->fetchHex()] = $data;
        }
        return $jobs;
    }

    protected function _trackJobs( $jobtype, Array $keyvals ) {
        $this->_jobCount += count($keyvals);
        foreach ($keyvals as $key => $data) {
            $this->_pendingJobs[$jobtype][$key] = $data;
        }
        $this->_pendingFifos[$jobtype] = $this->_fifos[$jobtype];
    }

    protected function _untrackJobs( $jobtype, Array $keys ) {
        $this->_jobCount -= count($keys);
        foreach ($keys as $key) {
            unset($this->_pendingJobs[$jobtype][$key]);
        }
        if (empty($this->_pendingJobs[$jobtype])) {
            if (isset($this->_fifos[$jobtype])) $this->_checkpointFifo($this->_fifos[$jobtype]);
            unset($this->_pendingJobs[$jobtype]);
            unset($this->_pendingFifos[$jobtype]);
        }
    }

    protected function _getFifo( $jobtype ) {
        if (!isset($this->_fifos[$jobtype])) {
            $fifo = new Quick_Fifo_FileReader($this->_store->getFilename($jobtype));
            return $this->_fifos[$jobtype] = $fifo->open();
        }
        else
            return $this->_fifos[$jobtype];
    }

    protected function _checkpointFifo( $fifo ) {
        $fifo->rsync();
    }

    protected function & _omitFifoMetafiles( & $names ) {
        $types = array();
        foreach ($names as $name) {
            $len = strlen($name);
            if ($name[$len-1] === ')') {
                if (substr($name, -7) === '.(data)') {
                    $type = substr($name, 0, -7);
                    $types[$type] = $type;
                }
            }
            else
                $types[$name] = $name;
        }
        return $types;
    }
}
