<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Mysqli_PersistentAdapter
    extends Quick_Db_Mysqli_Adapter
    implements Quick_Db_Adapter
{
    public function mysqli_connect( $host, $user, $password, $dbname, $port, $socket ) {
        return mysqli_connect("p:$host", $user, $password, $dbname, (int)$port, $socket);
    }
}
