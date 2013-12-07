<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_Datalogger_Array
    implements Quick_Data_Datalogger
{
    protected $_data = array();

    public function getData( ) {
        return $this->_data;
    }

    public function logData( Array $data ) {
        $this->_data[] = $data;
        return $this;
    }
}
