<?

/**
 * Base test case for using PHPUnit.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Test_Case
    extends PHPUnit_Framework_TestCase
{
    public function getMockSkipConstructor( $class, Array $mockMethods ) {
        return $this->getMock($class, $mockMethods, array(), "", false);
    }

    public function getTestDb( ) {
        global $phpunitDbCreds;
        if (!isset($phpunitDbCreds))
            throw new Quick_Db_Exception("Quick_Test_Case::getDb: \$phpunitDbCreds not set");
        $mysql = new Quick_Db_Mysql_Adapter();
        $conn = new Quick_Db_Mysql_Connection($phpunitDbCreds, $mysql);
        return new Quick_Db_Mysql_Db($conn->createLink(), $mysql);
    }
}
