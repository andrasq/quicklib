<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     quicklib
 */

interface Quick_Logger_Filter
{
    public function filterLogline($msg, $method);
}
