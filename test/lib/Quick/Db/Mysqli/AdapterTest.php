<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Mysqli_AdapterTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        if (!function_exists('mysqli_connect')) $this->markTestSkipped("mysqli not available");
        $this->_cut = new Quick_Db_Mysqli_Adapter();
        $this->_link = $this->_openLink();
    }

    public function testGetLinkShouldReturnConstructorArgument( ) {
        $cut = new Quick_Db_Mysqli_Adapter($id = uniqid());
        $this->assertEquals($id, $cut->getLink());
    }

    public function testGetLinkShouldReturnSetLinkArgument( ) {
        $cut = new Quick_Db_Mysqli_Adapter($id1 = uniqid());
        $cut->setLink($id2 = uniqid());
        $this->assertEquals($id2, $cut->getLink());
    }

    public function testMysqlConnectShouldReturnMysqli( ) {
        $link = $this->_openLink();
        $this->assertType('mysqli', $link);
    }

    public function testQueryShouldReturnMysqliResult( ) {
        $cut = new Quick_Db_Mysqli_Adapter($this->_link);
        $rs = $cut->mysql_query("SELECT NOW()", $this->_link);
        $this->assertType('mysqli_result', $rs);
    }

    public function testErrnoAndErrorShouldReturnValues( ) {
        $cut = new Quick_Db_Mysqli_Adapter($this->_link);
        $rs = $cut->mysql_query("SELECT 1 2 3 FROM t", $this->_link);
        $errno = $cut->mysql_errno($this->_link);
        $error = $cut->mysql_error($this->_link);
        $this->assertEquals(1064, $errno);
        $this->assertContains("SQL syntax", $error);
    }

    public function testFreeResultShouldBeCallable( ) {
        $cut = new Quick_Db_Mysqli_Adapter($this->_link);
        $rs = $cut->mysql_query("SELECT 1, 2, 3", $this->_link);
        $cut->mysql_free_result($rs);
    }

    public function testCloseShouldMakeLinkUnusable( ) {
        $cut = new Quick_Db_Mysqli_Adapter($this->_link);
        $tid = $this->_link->thread_id;
        $cut->mysql_close($this->_link);
        @$this->assertNotEquals($tid, $this->_link->thread_id);
        // note: accessing closed object results in "couldn't fetch mysqli" errors
    }

    protected function _openLink( ) {
        global $phpunitDbCreds;
        $creds = $phpunitDbCreds;
        $link = $this->_cut->mysqli_connect(
            $creds->getHost(),
            $creds->getUser(),
            $creds->getPassword(),
            $creds->getDatabase(),
            $creds->getPort(),
            $creds->getSocket()
        );
        return $link;
    }
}
