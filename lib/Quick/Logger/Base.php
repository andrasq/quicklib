<?

/**
 * Base logger functionality, does not write messages.
 * Extend the class to actually write the logline.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     quicklib
 *
 * 2013-02-09 - AR.
 */

class Quick_Logger_Base
    implements Quick_Logger, Quick_Logger_Filter
{
    protected $_filters = array();
    protected $_loglevel;

    public function __construct( $loglevel = self::INFO ) {
        // override in derived class as necessary
        $this->_loglevel = $loglevel;
    }

    public function debug( $msg ) {
        if ($this->_loglevel >= self::DEBUG) {
            $this->_logit($this->_filters ? $this->_filterit($msg, __FUNCTION__) : $msg);
        }
    }

    public function info( $msg ) {
        if ($this->_loglevel >= self::INFO) {
            $this->_logit($this->_filters ? $this->_filterit($msg, __FUNCTION__) : $msg);
        }
    }

    public function err( $msg ) {
        if ($this->_loglevel >= self::ERR) {
            $this->_logit($this->_filters ? $this->_filterit($msg, __FUNCTION__) : $msg);
        }
    }

    public function addFilter( Quick_Logger_Filter $filter ) {
        $this->_filters[] = $filter;
        return $this;
    }

    public function filterLogline( $msg, $method ) {
        return $this->_filterit($msg, $method);
    }

    protected function _filterit( $msg, $method ) {
        foreach ($this->_filters as $filter)
            $msg = $filter->filterLogline($msg, $method);
        return $msg;
    }

    protected function _logit( $msg ) {
        // override in derived class
    }
}
