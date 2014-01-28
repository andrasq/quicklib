<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Fifo_Reader {
    public function fgets();
    public function read($nbytes);
    public function ftell();
    public function feof();
    public function rsync();
    public function clearEof();
}
