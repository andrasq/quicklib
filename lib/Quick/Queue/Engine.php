<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Queue_Engine
{
    public function run();
    public function getStatus(Quick_Queue_Status $status);
    // setConfig ?
    public function setArchiver(Quick_Queue_Archiver $archiver);
}
