<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Fifo_Writer {
    public function fputs($line);
    public function write($lines);
    public function fflush();
}
