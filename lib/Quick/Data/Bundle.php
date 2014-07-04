<?

/**
 * Data store of hierarchical data with a unified naming scheme.
 *
 * Copyright (C) 2013-2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Data_Bundle
    extends Quick_Store
{
    public function withSeparator($separatorString);
    public function & shareValues(Array & $values = null);
    public function __toString();

    // inherited from store:
    // public function set($name, $value);
    // public function get($name);
    // public function delete($name);
}
