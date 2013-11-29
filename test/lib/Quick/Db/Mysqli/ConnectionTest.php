<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

require_once dirname(__FILE__) . '/../Mysql/ConnectionTest.php';

class Quick_Db_Mysqli_ConnectionTest
    extends Quick_Db_Mysql_ConnectionTest
{
    public function setUp( ) {
        if (!function_exists('mysqli_connect')) $this->markTestSkipped();
        parent::setUp();
    }

    protected function _createCut( ) {
        global $phpunitDbCreds;
        $adapter = new Quick_Db_Mysqli_Adapter(null);
        return new Quick_Db_Mysqli_Connection($phpunitDbCreds, $adapter);
    }

    protected function _createDb( $link ) {
        return new Quick_Db_Mysqli_Db($link, new Quick_Db_Mysqli_Adapter($link));
    }
}
