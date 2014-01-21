<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Router_RunnerFactory
    implements Quick_Queue_Router
{
    protected $_queueConfig;
    protected $_config;
    protected $_runners = array();
    protected $_runnerCache = array();

    public function __construct( Quick_Queue_Config $queueConfig ) {
        $this->_queueConfig = $queueConfig;
        $this->_config = & $queueConfig->shareConfig();

        // expects config section 'runner' to have jobtype => array(runners) array
        if (!isset($this->_config['runner']))
            $this->_config['runner'] = array();
    }

    public function getRunner( $jobtype ) {
        // only shareable runners are cached, so reuse if possible
        if (isset($this->_runnerCache[$jobtype]))
            return $this->_runnerCache[$jobtype];

        if (! $configs = $this->_config['runner'][$jobtype])
            throw new Quick_Queue_Exception("job $jobtype does not have any runners defined");

        if (is_array($configs) && isset($configs[1])) {
            // if multiple runners specified, use a group runner.  Group runners cannot be shared.
            foreach ($this->_getTypesForConfigs($configs) as $type)
                $runners[] = $this->_createRunnerForType($type);
            return new Quick_Queue_Runner_Group($this->_queueConfig, $runners);
        }
        else {
            // all single-runner runners may be shared
            $types = $this->_getTypesForConfigs(is_array($configs) ? $configs : array($configs));
            $type = $types[0];
            if (!isset($this->_runners[$type]))
                $this->_runners[$type] = $this->_createRunnerForType($type);
            // cache up to 200 jobtype-to-shared-runner mappings, for faster access
            if (empty($this->_runnerCache[$jobtype])) {
                $this->_runnerCache[$jobtype] = $this->_runners[$type];
                if (count($this->_runnerCache) >= 200)
                    array_splice($this->_runnerCache, 0, 100);
            }
            return $this->_runners[$type];
        }
    }


    protected function _createRunnerForType( $type ) {
        switch ($type) {
        case 'http':
            $runner = new Quick_Queue_Runner_Http($this->_queueConfig);
            break;
        case 'sh':
            $runner = new Quick_Queue_Runner_Shell($this->_queueConfig);
            break;
        case 'fake':
            $runner = new Quick_Queue_Runner_Fake($this->_queueConfig);
            break;
        default:
            throw new Quick_Queue_Exception("unrecognized runner spec ``$conf''");
            break;
        }
        // annotate the runner with the type.  Runners with a type can be shared.
        $runner->sharedType = $type;
        return $runner;
    }

    protected function _getTypesForConfigs( Array $configs ) {
        if (!$configs) return array();
        foreach ($configs as $spec) {
            switch (true) {
            // testing the first char is faster to eliminate non-matches
            case ($spec[0] === 'h' && (strncmp("http:", $spec, 5) === 0 ||
                                       strncmp("https:", $spec, 5) === 0)):
                $ret[] = 'http';
                break;
            case ($spec[0] === '!'):
                $ret[] = 'sh';
                break;
            case ($spec[0] === 'f' && strncmp("fake:", $spec, 5) === 0):
                $ret[] = 'fake';
            default:
                throw new Quick_Queue_Exception("unrecognized runner spec ``$spec''");
                break;
            }
        }
        return $ret;
    }
}
