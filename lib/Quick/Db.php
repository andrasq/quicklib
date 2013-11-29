<?

/**
 * Streamlined database access.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Db
{
    public function query($sql, $tag = '');
    public function select($sql, Array $values = null);
    public function execute($sql, Array $values = null);
    public function getQueryInfo();
}
