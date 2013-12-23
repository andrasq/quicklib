<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_Bundle_Json
    extends Quick_Data_Bundle_Base
{
    public function __toString( ) {
        $json = json_encode($this->_values);
        if ($json === null) {
            // @FIXME: try harder!
            $json = "json error";
        }
        return $json;
    }
}
