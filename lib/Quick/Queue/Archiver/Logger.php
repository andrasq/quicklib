<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Archiver_Logger
    extends Quick_Queue_Archiver_Base
    implements Quick_Queue_Archiver
{
    protected $_logger;

    public function __construct( Quick_Logger $logger ) {
        $this->_logger = $logger;
    }

    protected function _archive( Array & $rows ) {
        foreach ($rows as $row)
            $this->_logger->info($row);
    }
}
