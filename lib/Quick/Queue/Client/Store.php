<?

/**
 * Queue client implemented by decorating a Queue_Store.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Client_Store
    implements Quick_Queue_Client
{
    protected $_store;

    public function __construct( Quick_Queue_Store $store ) {
        $this->_store = $store;
    }

    public function add( $jobtype, $data ) {
        return $this->addJobs($jobtype, array($data));
    }

    public function addJobs( $jobtype, Array $datasets ) {
        $this->_store->addJobs($jobtype, $datasets);
        return $this;
    }
}
