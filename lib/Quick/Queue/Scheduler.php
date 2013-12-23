<?

/**
 * The scheduler chooses the type and number of tasks to run.
 * The number of already running tasks can be factored into the selection,
 * as can any applicable jobtype-specific criteria with configure().
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Queue_Scheduler
{
    const SCHED_BATCHSIZE = 'batchsize';
    const SCHED_WEIGHT = 'weight';

    public function configure($what, $jobtype, $value);
    public function getJobtypeToRun();
    public function & getBatchToRun($jobtype, $limit = null);
    public function setBatchDone($jobtype, Array & $jobs);

    /*
     * Note: scheduling order, priorities, configuration etc. TBD
     */
}
