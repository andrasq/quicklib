<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Mysqli_DbExposer extends Quick_Db_Mysqli_Db {
    protected function _addslashes( $str ) {
        return is_object($this->_link) ? $this->_link->real_escape_string($str) : addslashes($str);
    }
}

class Quick_Db_Mysqli_DbTest
    extends Quick_Test_Case
{
    protected $_fakeResult = -1;

    public function setUp( ) {
        $this->_adapter = $this->getMock('Quick_Db_Mysqli_Adapter', array('mysql_query', 'mysql_errno', 'mysql_error'));
        $this->_adapter->expects($this->any())->method('mysql_errno')->will($this->returnValue(1001));
        $this->_adapter->expects($this->any())->method('mysql_error')->will($this->returnValue('mysqli error'));
        $this->_cut = new Quick_Db_Mysqli_DbExposer(null, $this->_adapter);
    }

    public function testQueryShouldCallAdapterQuery( ) {
        $id = uniqid();
        $this->_adapter->expects($this->once())->method('mysql_query')->with("SELECT $id FROM table")->will($this->returnValue(true));
        $this->_cut->query("SELECT $id FROM table");
    }

    /**
     * @expectedException       Quick_Db_Exception
     */
    public function testQueryShouldThrowDbExceptionIfMysqliReturnsFalse( ) {
        $this->_adapter->expects($this->once())->method('mysql_query')->will($this->returnValue(false));
        $rs = $this->_cut->query("SELECT 1");
    }

    public function testQueryShouldReturnTrueIfMysqliReturnsTrue( ) {
        $this->_adapter->expects($this->once())->method('mysql_query')->will($this->returnValue(true));
        $rs = $this->_cut->query("SELECT 1");
        $this->assertEquals(true, $rs);
    }

    public function testQueryShouldReturnSelectResultsIfMysqliReturnsResults( ) {
        $this->_adapter->expects($this->once())->method('mysql_query')->will($this->returnValue($this->_fakeResult));
        $rs = $this->_cut->query("SELECT 1");
        $this->assertType('Quick_Db_SelectResult', $rs);
    }

    public function testSelectShouldInterpolateArgumentsAndCallAdapterQuery( ) {
        $this->_adapter
            ->expects($this->once())
            ->method('mysql_query')
            ->with("SELECT 1 FROM table WHERE x = 123 AND y = 'test'")
            ->will($this->returnValue($this->_fakeResult));
        $rs = $this->_cut->select("SELECT 1 FROM table WHERE x = ? AND y = ?", array(123, 'test'));
    }

    public function testExecuteShouldInterpolateArgumentsAndCallAdapterQuery( ) {
        $this->_adapter
            ->expects($this->once())
            ->method('mysql_query')
            ->with("UPDATE table SET z = 1 WHERE x = 123 AND y = 'test'")
            ->will($this->returnValue(true));
        $rs = $this->_cut->execute("UPDATE table SET z = 1 WHERE x = ? AND y = ?", array(123, 'test'));
    }

    public function testGetQueryInfoShouldReturnDbInfo( ) {
// in dev only
return;
        $info = $this->_cut->getQueryInfo();
        $this->assertType('Quick_Db_QueryInfo', $info);
    }

    public function xx_testSpeed( ) {
        // see also ar/time-db-speed.php, timings are 4x slower within phpunit
        // sudo sh -c '/bin/echo -n "ondemand" > /sys/devices/system/cpu/cpu0/cpufreq/scaling_governor'
        // sudo sh -c '/bin/echo -n 11 > /sys/devices/system/cpu/cpufreq/ondemand/up_threshold'

        global $phpunitDbCreds, $dbCredsCsv;
        $conn = new Quick_Db_Mysql_Connection($phpunitDbCreds, new Quick_Db_Mysql_Adapter());
        $db = new Quick_Db_Mysql_Db($conn->getLink(), new Quick_Db_Mysql_Adapter($conn->getLink()));

        $timer = new Quick_Test_Timer();
        $timer->calibrate(10000, array($this, '_testSpeedNull'), array($db));

        echo $timer->timeit(10000, "query", array($this, '_testSpeedQuery'), array($db));
        // 33k/s "select 1"; 2.6.18 was 43k/s (tagged: 40k/s)

        echo $timer->timeit(10000, "select", array($this, '_testSpeedSelect'), array($db));
        // 29k/s "select 1"; 2.6.18 was 32k/s

        $db = new Quick_Db_Decorator_QueryTagger($db);
        echo $timer->timeit(10000, "decorated (tagged) query", array($this, '_testSpeedQuery'), array($db));
        // 17.5k/s; 2.6.18 was 16k/s
    }

    public function _testSpeedNull( $db ) {
    }

    public function _testSpeedQuery( $db ) {
        $db->query("SELECT 1");
    }

    public function _testSpeedSelect( $db ) {
        $db->select("SELECT 1")->asColumn()->fetch();
    }
}
