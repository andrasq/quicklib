<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Queue_Archiver_Stdout
    extends Quick_Queue_Archiver_Base
    implements Quick_Queue_Archiver
{
    protected function _archive( Array & $rows ) {
        foreach ($rows as $row)
            echo $row;
    }
}
