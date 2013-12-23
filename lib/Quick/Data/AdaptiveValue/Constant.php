<?

/**
 * Unchanging adaptive value, for testing.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_AdaptiveValue_Constant
    implements Quick_Data_AdaptiveValue
{
    protected $_current;

    public function __construct( $current ) {
        $this->_current = $current;
    }

    public function set( $current ) {
        $this->_current = $current;
    }

    public function get( ) {
        return $this->_current;
    }

    public function adjust( $triggerBackoff ) {
        return $this->_current;
    }
}
