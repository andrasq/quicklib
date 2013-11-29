<?

/**
 * No-op logger, it does nothing.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     quicklib
 *
 * 2013-02-17 - AR.
 */

class Quick_Logger_Null
    implements Quick_Logger, Quick_Logger_Filter
{
    protected $_filters = array();

    public function err( $line ) { }
    public function info( $line ) { }
    public function debug( $line ) { }
    public function addFilter( Quick_Logger_Filter $filter ) { $this->_filters[] = $filter; }
    public function filterLogline( $msg, $method ) { foreach ($this->_filters as $filter) $filter->filterLogline($msg, $method); }
}
