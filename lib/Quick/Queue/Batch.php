<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Batch
{
    public $jobtype;
    public $jobs = array();
    public $results = array();
    public $count = 0;                  // number of jobs in this batch
    public $concurrency = 1;            // how many jobs will run at once

    public function __construct( $jobtype, Array & $jobs = null ) {
        $this->jobtype = $jobtype;
        if ($jobs) {
            $this->count = count($jobs);
            $this->jobs = $jobs;
        }
    }
}
