<?

/**
 * filter that tees the filter pipeline and feeds the message to another logger
 * Note that the contents of the message depend on the filter order of the first logger.
 * To get unfiltered messages, add the Tee before any other filters.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     quicklib
 *
 * 2013-02-09 - AR.
 */

class Quick_Logger_Filter_Tee
    implements Quick_Logger_Filter
{
    public function __construct( Quick_Logger $logger ) {
	$this->_logger = $logger;
    }

    public function filterLogline( $msg, $method ) {
	$this->_logger->$method($msg);
	return $msg;
    }
}
