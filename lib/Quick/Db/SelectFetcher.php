<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Db_SelectFetcher
    extends Quick_Db_Fetchable
{
    // inherit fetch, reset
    public function fetchAll($limit = null);
    public function getIterator();
}
