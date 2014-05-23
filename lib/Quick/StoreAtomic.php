<?

/**
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_StoreAtomic
    extends Quick_Store
{
    // inherit set, get, delete

    public function add($key, $value);
    public function exists($key);
}
