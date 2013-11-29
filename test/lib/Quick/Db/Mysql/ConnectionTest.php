<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Mysql_ConnectionTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        global $phpunitDbCreds;
        $this->_creds = $phpunitDbCreds;
        $this->_cut = $this->_createCut();
    }

    protected function _createCut( ) {
        $adapter = new Quick_Db_Mysql_Adapter(null);
        return new Quick_Db_Mysql_Connection($this->_creds, $adapter);
    }

    protected function _createDb( $link ) {
        // create Mysql db, mysqli conn test overrides
        return new Quick_Db_Mysql_Db($link, new Quick_Db_Mysql_Adapter($link));
    }

    public function testCreateLinkShouldReturnNativeLink( ) {
        // if we can create a db with the link, its native enough
        $db = $this->_createDb($this->_cut->createLink());
    }

    public function testCreateLinkShouldReturnDistinctLinks( ) {
        $link1 = $this->_cut->createLink();
        $link2 = $this->_cut->createLink();
        $this->assertFalse($link1 === $link2, "createLink links should be distinct");
    }

    public function testCreateLinkShouldApplyConfig( ) {
        $this->_cut
            ->configure("SET @a = 111")
            ->configure("SET @b = 222");
        $db = new Quick_Db_Fake_Db();
        $link = $this->_cut->createLink();
        $db2 = $this->_createDb($link);
        $this->assertEquals('111', $db2->select("SELECT @a;")->asColumn()->fetch());
        $this->assertEquals('222', $db2->select("SELECT @b;")->asColumn()->fetch());
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $timer->calibrate(1000, array($this, '_testSpeedNull'), array($this->_cut));
        echo $timer->timeit(2000, 'createLink', array($this, '_testSpeedCreateLink'), array($this->_cut));
        // localhost: mysql = 5000/sec, mysqli = 5400/sec
    }

    public function _testSpeedNull( $cut ) {
    }

    public function _testSpeedCreateLink( $cut ) {
        $cut->createLink();
    }
}
