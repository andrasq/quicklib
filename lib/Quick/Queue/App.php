<?php

/**
 * Generic little queue application, for embedding within web pages.
 * Queue_App is Rest_App compatible, but does not create dependencies.
 *
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */


class Quick_Queue_App
{
    protected $_instances = array();

    public function __construct( Array & $queueConfig ) {
        $this->_queueConfig = & $queueConfig;
    }

    static public function create( & $queueConfig ) {
        $queue = new Quick_Queue_App($queueConfig);
        $queue->initStore($queueConfig);
        $queue->initArchiver($queueConfig);
        $queue->initRunner($queueConfig);
        return $queue;
    }

    public function initStore( & $queueConfig ) {
        if (isset($this->_store))
            return $this;

        $this->_config = new Quick_Queue_Config($queueConfig);
        $this->_config->loadFromFile($queueConfig['queuedir'] . '/queue_config.inc.php', 'queueConfig');

        // build column-oriented sparse config vectors
        foreach (array('runner', 'batchsize', 'batchlimit') as $column)
            $queueConfig[$column] = $this->_config->selectFieldFromBundles($column, $queueConfig['jobs']);

        // FIXME: determine store type from config
        $type = 'FileStore';
        switch ($type) {
        case 'FileStore':
            if (!is_dir($queueConfig['queuedir'])) `mkdir -p {$queueConfig['queuedir']}`;
            if (!is_dir($queueConfig['jobsdir'])) `mkdir -p {$queueConfig['jobsdir']}`;
            $this->_store = new Quick_Queue_Store_FileDirectory(new Quick_Store_FileDirectory($queueConfig['jobsdir']));
            break;
        case 'DbStore':
            throw new Exception(__METHOD__ . ": writeme!");
            break;
        default:
            throw new Exception("$type: unknown Queue_Store");
        }
        return $this;
    }

    public function initArchiver( $queueConfig ) {
        if (isset($this->_archiver))
            return $this;

        // FIXME: determine archiver type from config
        $type = 'StdoutArchiver';
        switch ($type) {
        case 'StdoutArchiver':
            $this->_archiver = new Quick_Queue_Archiver_Stdout();
            break;
        case 'FileArchiver':
            $this->_taskLogger = new Quick_Logger_FileAtomic($queueConfig['tasks']);
            //$this->_taskLogger = new Quick_Logger_FileAtomicBuffered($queueConfig['tasks']);
            //$this->_archiver = new Quick_Queue_Archiver_Logger($this->_taskLogger);
            break;
        default:
            throw new Exception("$type: unknown Queue_Archiver");
            break;
        }
        return $this;
    }

    public function initRunner( $queueConfig ) {
        if (isset($this->_runner))
            return $this;

        if (!$this->_store)
            throw new Exception("Store must be initialized before Runner");

        $this->_scheduler = new Quick_Queue_Scheduler_Random($this->_store, $this->_config);
        $this->_router = new Quick_Queue_Router_RunnerFactory($this->_config);

        foreach (array('runner', 'batchsize', 'batchlimit') as $column)
            $queueConfig[$column] = $this->_config->selectFieldFromBundles($column, $queueConfig['jobs']);

        $this->_runner = new Quick_Queue_Runner_Universal($this->_config, $this->_router);
        $this->_engine = new Quick_Queue_Engine_Generic($this->_store, $this->_scheduler, $this->_runner);
        if (isset($this->_archiver))
            $this->_engine->setArchiver($this->_archiver);
        return $this;
    }


    public function addAction( Quick_Rest_Request $request, Quick_Rest_Response $response, $app ) {
        // .00035 to init store and save 1 job, .00039 to save 5 (100k/s) (GET)
        $this->initStore($this->_queueConfig);

        // 50% faster to just append to the job fifo, but jobs may not always be fifos:
        // file_put_contents($appConfig['job.store.dir'] . "/$type", (string)$data . "\n");
        $jobtype = $request->requireParam('jobtype');
        $data = $request->getParam('data');

        if ($data === null) $data = explode("\n", $request->getPostBody());
        elseif (!is_array($data)) $data = array($data);
        $this->_store->addJobs($jobtype, $data);
        $response->appendContent("added jobtype $jobtype job\n");
    }

    public function runAction( Quick_Rest_Request $request, Quick_Rest_Response $response, $app ) {
        $this->initStore($this->_queueConfig);
        $this->initRunner($this->_queueConfig);

        $time = $request->getParam('time'); if ($time === null) $time = 1;
        $count = $request->getParam('count'); if ($count === null) $count = 100;
        $this->_engine->run($time, $count);
        $this->_engine->finish();
    }

    public function statusAction( Quick_Rest_Request $request, Quick_Rest_Response $response, $app ) {
        $this->initStore($this->_queueConfig);
        $this->initRunner($this->_queueConfig);
        $vars = array();
        // httpd: .0001 to get status, .0006 total runtime
        $status = $this->_engine->getStatus(new Quick_Queue_Status($vars));

        $response->appendContent(json_encode($vars) . "\n");

        $response->appendContent("Server: " . php_uname('n') . "\n");
        $response->appendContent("Date: " . date('Y-m-d H:i:s') . "\n");
        $n = count($vars['jobs']['batchtypes']);
        $response->appendContent("Jobtypes: {$vars['jobs']['jobcount']} waiting, $n running\n");
        $response->appendContent("\n");

        // FIXME: this prints plaintext, but might want a json blob instead
        foreach ($vars as $type => $namevals) {
            foreach ($namevals as $name => $value) {
                if (is_array($value)) $value = implode(", ", $value);
                $response->appendContent(ucwords($name) . ": " . $value . "\n");
            }
        }
    }
}
