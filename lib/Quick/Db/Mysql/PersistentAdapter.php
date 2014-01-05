<?

/**
 * Simple little wrapper to encapsulate mysql access functions.
 * Handy for unit testing, but critical loops (result fetchers) bypass this.
 *
 * Note that it's 5% faster to use $this->_link than to pass it via func arg.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Mysql_PersistentAdapter
    extends Quick_Db_Mysql_Adapter
    implements Quick_Db_Adapter
{
    public function mysql_connect( $host, $user, $password, $newConnection ) {
        return mysql_pconnect($host, $user, $password, $newConnection);
    }
}
