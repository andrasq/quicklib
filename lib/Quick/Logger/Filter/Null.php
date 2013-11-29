<?

/**
 * empty filter, does nothing
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     quicklib
 */

class Quick_Logger_Filter_Null
    implements Quick_Logger_Filter
{
    public function filterLogline( $msg, $method ) {
	return $msg;
    }
}
