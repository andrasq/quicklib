<?

/**
 * Dataloggers are unattended devices that record bits of information
 * for later retrieval and analysis.  Usually each logger captures
 * one particular data stream.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Data_Datalogger
{
    public function logData(Array $info);
}
