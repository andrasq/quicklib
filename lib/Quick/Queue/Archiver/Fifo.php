<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Archiver_Fifo
    extends Quick_Queue_Archiver_Base
    implements Quick_Queue_Archiver
{
    protected $_fifo;

    public function __construct( Quick_Fifo_Writer $fifo ) {
        $this->_fifo = $fifo;
    }

    protected function _archive( Array & $rows ) {
        $this->_fifo->write(implode("", $rows));
        $this->_fifo->fflush();
    }
}
