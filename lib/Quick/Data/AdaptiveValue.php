<?

/**
 * Adaptive values self-adjust to track environmental conditions.
 * The classic example is Van Jacobson's TCP/IP sliding Window procotol.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Data_AdaptiveValue
{
    public function get();
    public function set($current);
    public function adjust($backoffRequired);
}
