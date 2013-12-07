<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Db_Engine
    extends Quick_Db
{
    public function setLink($link);
    public function setProfiling(Quick_Data_Datalogger $profiler = null);
}
