<?

/**
 * Database connection session management.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Db_Connection
{
    // Execute the sql statement when a db connection is opened.
    // Call again with different SQL to run multiple statements.
    public function configure($sql);

    // create a new db link
    public function createLink();

    // apply the config sqls to the link
    public function configureLink($link);
}
