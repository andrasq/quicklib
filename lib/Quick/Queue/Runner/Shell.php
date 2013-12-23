<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Runner_Shell
    implements Quick_Queue_Runner
{
    protected $_procs = array(), $_doneProcs = array();
    protected $_queueConfig, $_config = array();
    protected $_jobs = array();

    public function __construct( Quick_Queue_Config $queueConfig ) {
        $this->_queueConfig = $queueConfig;
        $this->_config = $queueConfig->shareConfig();
    }

    public function setQueueConfig( Quick_Queue_Config $queueConfig ) {
        $this->_queueConfig = $queueConfig;
        $this->_config = $queueConfig->shareConfig();
    }

    public function configure( $type, $name, $value ) {
        $this->_queueConfig->set($type, $name, $value);
    }

    public function runJobs( $jobtype, Array $datasets ) {
        $inputFile = new Quick_Test_Tempfile("/tmp", "qq-job-");
        $outputFile = new Quick_Test_Tempfile("/tmp", "qq-ret-");
        $proc = $this->_getProcForJobtype($jobtype, $inputFile, $outputFile);
        if (!$proc) return false;

        // attach the tempfiles to the process, so automatically remove when done
        $proc->inputFile = $inputFile;
        $proc->outputFile = $outputFile;
        $proc->batchKeys = array_keys($datasets);

        if (substr(end($datasets), -1) !== "\n")
            // synthetic data may not be newline-terminated, do so now
            foreach ($datasets as & $data) $data .= "\n";
        file_put_contents($proc->inputFile, implode("", $datasets));

        $proc->open();

        // launch the process to run the batch
        $cmdline = $this->_getCmdlineForJobtype($jobtype, $proc->inputFile, $proc->outputFile);
        $proc->putInput($cmdline . "; echo ok\n");
        $this->_procs[$jobtype][] = $proc;

        return true;
    }

    public function getDoneJobtypes( ) {
        $this->_checkForDoneProcs();
        return array_keys($this->_doneProcs);
    }

    public function & getDoneJobs( $jobtype ) {
        if (empty($this->_doneProcs[$jobtype]))
            return array();

        $ret = array();
        foreach ($this->_doneProcs[$jobtype] as $proc) {
            $batchResults = array();
            foreach (file($proc->outputFile) as $line) {
                // recover the json bundle from the results, this also validates the json formatting
                if (is_array($batchResults[] = json_decode($line, true))) {
                    // we expected a json array, and we got one
                    ;
                }
                else {
                    // not a json array, figure out what happened
                    $val = array_pop($batchResults);
                    if (is_numeric(trim($line))) {
                        // test data is often integers, handy to treat numeric responses as valid
                        $batchResults[] = array('status' => 0, 'response' => trim($line), 'proc' => $proc->getPid());
                    }
                    /**
                    elseif (($x = end($batchResults)) || isset($x)) {
                        // response was a valid json value, but not an array, assume test data again
                        $batchResults[] = array('status' => 0, 'response' => $val, 'proc' => $proc->getPid());
                    }
                    **/
                    else {
                        // ERROR: invalid json in results... job died with fatal error?
                        // @FIXME: should capture the entire invalid message!
                        //         gather up all following lines and concatenate them into this response
                        $batchResults[] = array(
                            'status' => Quick_Queue_Runner::RUN_ERROR,
                            'message' => 'invalid response',
                            'response' => $line,
                        );
                        break;
                    }
                }
            }
            // all missing values are from jobs presumably not run, mark them as such
            $n = count($proc->batchKeys) - count($batchResults);
            for ($i=0; $i<$n; ++$i) {
                $batchResults[] = array(
                    'status' => Quick_Queue_Runner::RUN_UNRUN,
                    'message' => 'unrun',
                );
            }
            $ret += array_combine($proc->batchKeys, $batchResults);
        }

        unset($this->_doneProcs[$jobtype]);
        return $ret;
    }


    // create a new process to run the batch of jobs
    // the process is a generic shell that can run any command;
    // job runners read datasets from stdin and write result bundles to stdout
    protected function _getProcForJobtype( $jobtype, $input, $output ) {
        return new Quick_Proc_Process("exec /bin/sh", false);
    }

    // return the command to run the batch.
    // The command must read job datasets from stdin and write results to stdout.
    protected function _getCmdlineForJobtype( $jobtype, $input, $output ) {
        if (($runner = $this->_queueConfig->get('runner', $jobtype)) && $runner[0] === '!') {
            $runner = substr($runner, 1);
            return "$runner < $input > $output";
        }
        else {
            throw new Quick_Queue_Exception("shell runner: type $jobtype does not have a shell command configured");
        }
    }

    protected function _checkForDoneProcs( ) {
        // scan all running procs, see which returned
        $doneJobtypes = array();
        foreach ($this->_procs as $jobtype => & $procs) {
            $doneSlots = array();
            foreach ($procs as $slot => $proc) {
                if ($line = $proc->getOutput()) {
                    if ($line !== "ok\n") throw new Quick_Queue_Exception("unexpected response from shell job: $line");
                    $this->_doneProcs[$jobtype][] = $proc;
                    $doneSlots[] = $slot;
                }
            }
            foreach ($doneSlots as $slot) unset($procs[$slot]);
            if (empty($procs)) $doneJobtypes[] = $jobtype;
        }
        foreach ($doneJobtypes as $jobtype) unset($this->_procs[$jobtype]);
    }
}
