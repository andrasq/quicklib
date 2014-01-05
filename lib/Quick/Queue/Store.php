<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Queue_Store
    extends Quick_Queue_Client
{
    public function getJobtypes();

    // inherits from Client:
    //public function add($jobtype, $data);
    //public function addJobs($jobtype, Array $datasets);

    /*
     * Jobs are returned as a hash of key => data with keys that
     * will remain unique until the job finishes or is requeued.
     * Keys are unique per server, not globally.
     */
    public function getJobs($jobtype, $limit);
    public function getStatus($section, Quick_Queue_Status $status);

    // indicate that job is done (delete) or should be tried again (unget)
    public function deleteJobs($jobtype, Array $keys);          // done
    public function ungetJobs($jobtype, Array $keys);           // not run
    public function retryJobs($jobtype, Array $keys);           // try again
}
