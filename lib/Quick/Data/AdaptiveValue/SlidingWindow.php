<?

/**
 * Simple exponential backoff similar to Van Jacobsen's TCP/IP sliding window protocol.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * Feedback is via the adjust() method, which changes the current window by either
 * growing it a bit by $adjustStep or shrinking it by a factor $backoffFactor.
 *
 * The adjust step is additive and the backoff step is multiplicative; a sliding
 * quantity would use a positive step and 1/2 for the factor, a sliding timeout
 * would use a negative step and 2.0 for the factor.
 */

class Quick_Data_AdaptiveValue_SlidingWindow
    implements Quick_Data_AdaptiveValue
{
    protected $_current, $_min, $_max, $_adjustStep, $_backoffFactor;

    public function __construct( $initial, $min, $max, $adjustStep, $backoffFactor ) {
        $this->_current = $initial;
        $this->_min = $min;
        $this->_max = $max;
        $this->_adjustStep = $adjustStep;
        $this->_backoffFactor = $backoffFactor;
    }

    public function set( $current ) {
        $this->_current = $current;
    }

    public function get( ) {
        return $this->_current;
    }

    public function adjust( $triggerBackoff ) {
        if ($triggerBackoff) {
            // operation took too long, trigger exponential backoff
            return $this->_current = min($this->_max, $this->_current * $this->_backoffFactor);
        }
        else {
            // operation finished quick, try for more the next time
            return $this->_current = max($this->_min, $this->_current + $this->_adjustStep);
        }
    }
}
