<?

/**
 * General-purpose database credentials class
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Db_Credentials
{
    public function getHost();
    public function getPort();
    public function getSocket();
    public function getUser();
    public function getPassword();
    public function getDatabase();
}
