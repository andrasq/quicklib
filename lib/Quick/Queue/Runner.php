<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Queue_Runner
{
    const RUN_OK = 0;           // good run, successful
    const RUN_FAILED = 1;       // good run, unsuccessful
    const RUN_ERROR = 2;        // failed run or errored out, retry
    const RUN_UNRUN = 3;        // not run, schedule again

    public function configure($what, $jobtype, $value);
    public function runBatch($jobtype, Quick_Queue_Batch $batch);
    public function getDoneJobtypes();
    public function getDoneBatch($jobtype);
}
