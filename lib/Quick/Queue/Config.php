<?

/**
 * Sparse column store for per-jobtype configuration settings.
 * The queue config file is in sparse row-major order, and is converted
 * into a per-column mapping for convenient access.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Config
{
    protected $_config = array();

    public function __construct( Array & $config = null ) {
        if ($config !== null) $this->_config = & $config;
    }

    public function configure( $type, $name, $value ) {
        return $this->set($type, $name, $value);
    }

    public function setConfig( $type, $namevals ) {
        $this->_config[$type] = $namevals;
        return $this;
    }

    public function getConfig( $type = null ) {
        if ($type === null)
            return $this->_config;
        elseif (isset($this->_config[$type]))
            return $this->_config[$type];
        else
            return array();
    }

    public function & shareConfig( ) {
        return $this->_config;
    }

    public function selectFieldFromBundles( $field, Array $bundles ) {
        $ret = array();
        foreach ($bundles as $name => $bundle) {
            if (isset($bundle[$field])) $ret[$name] = $bundle[$field];
        }
        return $ret;
    }

    public function selectFieldFromBundlesOrDefault( $field, Array $bundles, $default ) {
        $ret = array();
        foreach ($bundles as $name => $bundle) {
            if (isset($bundle[$field])) $ret[$name] = $bundle[$field];
            else $ret[$name] = $default;
        }
        return $ret;
    }

    public function set( $type, $name, $value ) {
        $this->_config[$type][$name] = $value;
    }

    public function get( $type, $name ) {
        if (isset($this->_config[$type][$name]))
            return $this->_config[$type][$name];
        elseif (isset($this->_config[$type]['__default']))
            return $this->_config[$type]['__default'];
        else
            return null;
    }
}
