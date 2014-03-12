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
    protected $_store;                          // directory with jobs in fifos
    protected $_keys;                           // unique number generator
    protected $_fifos = array();                // fifo cache
    protected $_fifoCount = 0;
    protected $_pendingBatches = array();       // currently running batches of jobs by type
    protected $_shareFifos = FALSE;

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
        if (!$fifo = $this->_getFifo($jobtype)) return array();
        $data = $this->_fetchJobs($fifo, $jobtype, $limit);
        $jobs = & $this->_assignKeysToJobs($data);
        $this->_trackJobs($fifo, $jobtype, $jobs);
        return $jobs;
    }

    // successful jobs are removed from the queue
    public function deleteJobs( $jobtype, Array $keys ) {
        if ($keys) {
            $this->_untrackJobs($jobtype, $keys);
        }
        return $this;
    }

    // failed jobs are retried (requeued, but with fewer retries left)
    // NOTE: the FileDirectory store does not limit retries
    public function retryJobs( $jobtype, Array $keys ) {
        return $this->ungetJobs($jobtype, $keys);
    }

    // unrun jobs are un-gotten (requeued)
    public function ungetJobs( $jobtype, Array $keys ) {
        if ($keys) {
            $keyvals = array();
            $mykeys = $keys;
            while ($mykeys) {
                $info = $this->_findBatchWithKey($jobtype, $key = current($mykeys));
                if (!$info) throw new Queue_Exception("internal error: cannot unget, key $key not in known batches");
                foreach ($mykeys as $ix => $key) {
                    $keyvals[$key] = $info['keys'][$key];
                    unset($mykeys[$ix]);
                }
            }
            $this->addJobs($jobtype, $keyvals);
            $this->_untrackJobs($jobtype, $keys);
        }
        return $this;
    }

    public function add( $jobtype, $data ) {
        return $this->addJobs($jobtype, array($data));
    }

    public function addJobs( $jobtype, Array $datasets ) {
        $filename = $this->_store->getFilename($jobtype);
        foreach ($datasets as & $data)
            if (substr($data, -1) !== "\n") $data .= "\n";
        $ok = file_put_contents($filename, (implode('', $datasets)), FILE_APPEND | LOCK_EX);
        return $this;
    }

    // for unit test introspection:
    public function getPendingJobsByJobtype( $jobtype ) {
        if (empty($this->_pendingBatches[$jobtype]))
            return array();
        $ret = array();
        foreach ($this->_pendingBatches[$jobtype] as $info) {
            $ret += $info['keys'];
        }
        return $ret;
    }

    public function getStatus( $section, Quick_Queue_Status $status ) {
        $status->set($section, 'jobtypes', $this->getJobtypes());
        $batches = array();
        foreach ($this->_pendingBatches as $jobtype => $info) {
            $batches[$jobtype] = count($info['keys']);
        }
        $status->set($section, 'batches', $batches);
        // exact status TBD
    }

    protected function _fetchJobtypes( ) {
        return $this->_store->getNames();
    }

    protected function & _fetchJobs( $fifo, $jobtype, $limit ) {
        $jobs = array();
        if (!$this->_shareFifos || $fifo->acquire()) {
            for ($i=0; $i<$limit; ++$i) {
                // read fifo until empty.  When all jobs of this type finish, rysnc() then to clear EOF.
                if (($job = $fifo->fgets()) !== false)
                    $jobs[] = $job;
                elseif (empty($this->_pendingBatches[$jobtype]) && !$jobs) {
                    // if no more jobs of this jobtype, rsync to clear EOF
                    // note: none running and none read during this pass either
                    if ($fifo->feof()) $fifo->rsync();
                }
                else {
                    if ($this->_shareFifos) $fifo->release();
                    break;
                }
            }
        }
        return $jobs;
    }

    protected function & _assignKeysToJobs( Array & $datasets ) {
        $jobs = array();
        foreach ($datasets as $data) {
            $jobs[$this->_keys->fetchHex()] = $data;
        }
        return $jobs;
    }

    protected function _trackJobs( $fifo, $jobtype, Array & $keyvals ) {
        // track each fifo-continguous bunch of jobs as a separate batch, to know when to advance the fifo
        if ($keyvals)
            $this->_pendingBatches[$jobtype][$batchId = "b-" . $this->_keys->fetchHex()] = array(
                'offset' => $fifo->ftell(),
                'keys' => & $keyvals,
                'fifo' => $fifo,
                'batchid' => $batchId,
            );
    }

    // unset the jobs from the pending batches.
    // once a pending batch is empty, the fifo read checkpoint can be advanced past it
    protected function _untrackJobs( $jobtype, Array & $keys ) {
        // typically all jobs are from the same batch (but is not required)
        // find the presumptive batch that has $key, we hope it has all other jobs as well
        // note that $info is returned by reference, so we can update its contents
        $key = current($keys);

        // NOTE: must assign with & even though method is declared as returning a reference.
        // Php will generate an E_STRICT warning if the method does not return a reference.
        // No, there is no other way to know which form to use.
        if ($key) {
            $info = & $this->_findBatchWithKey($jobtype, $key);
            if (!$info || !$this->_isBatchContainsKeys($info['keys'], $keys)) {
                // some jobs not from the presumptive batch, look everywhere
                $this->_untrackJobsFromAllBatches($jobtype, $keys);
            }
            else {
                // these jobs are just part of the batch, remove them leaving the others
                foreach ($keys as $key) unset($info['keys'][$key]);
            }
        }

        // once batches are adjusted, we can checkpoint the fifo for the empty batches at the head
        $this->_checkpointFifo($jobtype);
    }

    protected function _checkpointFifo( $jobtype ) {
        foreach ($this->_pendingBatches[$jobtype] as $batchId => & $info) {
            if (!$info['keys']) {
//$pid = getmypid();
//echo "BATCH DONE $pid\n";
                // when a batch frame is empty all we need is to checkpoint the fifo read offset
                // note: if fifos all the same, could combine the checkpoints into a single sync
                $readOffset = $info['offset'];
                unset($this->_pendingBatches[$jobtype][$batchId]);
            }
            else break;
        }

        if (isset($readOffset)) {
//echo "BATCH SYNCED $pid\n";
            // rsync offsets are in contiguous ascending batch order, only need to sync the last one
            // Note: Fifos can not be shared while pending tasks remain outstanding.
            $info['fifo']->rsync($readOffset);
        }

        if (empty($this->_pendingBatches[$jobtype])) {
            // Note: Fifos can not be shared while pending tasks remain outstanding.
            // We have exclusive ownership of a shared fifo until we release it,
            // and we only release it once all batches have finished.
//echo "BATCH RELEASED $pid\n";
            $info['fifo']->release();
            unset($this->_pendingBatches[$jobtype]);
        }
    }

    protected function _isBatchContainsKeys( Array & $batch, Array & $keys ) {
        foreach ($keys as $key) {
            if (isset($batch[$key])) continue;
            else return false;
        }
        return true;
    }

    protected function & _findBatchWithKey( $jobtype, $key ) {
        if ($key) foreach ($this->_pendingBatches[$jobtype] as $batchId => & $info) {
            if (isset($info['keys'][$key]))
                return $info;
        }
        $info = array();
        return $info;
    }

    protected function _untrackJobsFromAllBatches( $jobtype, Array & $keys ) {
        // note: O(n*m) in the number of batches and keys
        // only call if the presumptive batch does not contain all keyvals
        if (isset($this->_pendingBatches[$jobtype])) {
            $keyvals = array_flip($keys);
            foreach ($this->_pendingBatches[$jobtype] as $batchId => & $info) {
                $info['keys'] = array_diff_key($info['keys'], $keyvals);
            }
        }
    }

    protected function _getFifo( $jobtype ) {
        if (isset($this->_fifos[$jobtype])) {
            // maintain an LRU list: shuffle the fifo to the end
            $fifo = $this->_fifos[$jobtype];
            unset($this->_fifos[$jobtype]);
            return $this->_fifos[$jobtype] = $fifo;
        }
        else {
            // garbage collect fifos, keep a finite LRU list instead of caching all
            // running with more than 500 jobtypes could run much slower
            if ($this->_fifoCount > 500) {
                array_splice($this->_fifos, 0, 200);
                $this->_fifoCount -= 200;
            }
            $fifo = new Quick_Fifo_FileReader($this->_store->getFilename($jobtype));
            try {
                $fifo->setSharedMode($this->_shareFifos);
                $fifo = $fifo->open();
                ++$this->_fifoCount;
                return $this->_fifos[$jobtype] = $fifo;
            }
            catch (Exception $e) { return null; }
        }
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
