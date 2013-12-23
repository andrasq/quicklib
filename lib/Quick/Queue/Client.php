<?

/**
 * Clients adding tasks to the queue use a Queue_Client interface.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Queue_Client
{
    public function add($jobtype, $data);
    public function addJobs($jobtype, Array $datasets);
}
