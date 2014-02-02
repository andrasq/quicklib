<?

class Quick_Db_Mysql_DbTest_MockMysqlAdapter
    extends Quick_Db_Mysql_Adapter
{
    const FAKE_MYSQL_RESULT = -1;
    public $calls = array();

    public function __construct( ) { }
    public function mysql_query( $sql, $link ) { $this->calls[__FUNCTION__] += 1; return self::FAKE_MYSQL_RESULT; }
    public function mysql_errno( $link ) { return 1001; }
    public function mysql_error( $link ) { return "Adapter error"; }
    public function mysql_free_result( $rs ) { }
    public function num_rows( $rs ) { return 123; }
    public function affected_rows( $link ) { return 1; }
}

class Quick_Db_Mysql_DbExposer
    extends Quick_Db_Mysql_Db
{
    public $calls = array();
    public $_verbose = false;

    public function query( $sql, $tag = '' ) { @$this->calls[__FUNCTION__] += 1; return parent::query($sql, $tag); }
}

class Quick_Db_Mysql_DbTest
    extends Quick_Test_Case
{
    const FAKE_RESULT = -1;     // close enough to a mysql_result to fool the code

    public function setUp( ) {
        $this->_conn = $this->getMock('Quick_Db_Connection');
        //$this->_mysql = new Quick_Db_Mysql_DbTest_MockMysqlAdapter();
        $this->_mysql = $this->getMock('Quick_Db_Mysql_DbTest_MockMysqlAdapter', array('mysql_query'));
        $this->_mysql->expects($this->any())->method('mysql_errno')->will($this->returnValue('1001'));
        $this->_mysql->expects($this->any())->method('mysql_error')->will($this->returnValue('Adapter error'));
        $this->_cut = new Quick_Db_Mysql_DbExposer(0, $this->_mysql);
    }

    public function testQueryShouldCallMysqlQuery( ) {
        $this->_mysql->expects($this->once())->method('mysql_query')->will($this->returnValue(true));
        $this->_cut->query("SET profiling = 1");
    }

    /**
     * @expectedException       Quick_Db_Exception
     */
    public function testQueryShouldThrowExceptionOnError( ) {
        $this->_mysql->expects($this->once())->method('mysql_query')->will($this->returnValue(false));
        $this->_cut->query("SELECT 1");
    }

    public function testSelectShouldInterpolateValues( ) {
        $this->_mysql->expects($this->once())->method('mysql_query')->with("SELECT 1, 'test' FROM table")->will($this->returnValue(self::FAKE_RESULT));
        $rs = $this->_cut->select("SELECT ?, ? FROM table", array(1, 'test'));
    }

    public function testExecuteShouldInterpolateValues( ) {
        $this->_mysql->expects($this->once())->method('mysql_query')->with("UPDATE table SET field1 = 1, field2 = 'test'")->will($this->returnValue(true));
        $rs = $this->_cut->execute("UPDATE table SET field1 = ?, field2 = ?", array(1, 'test'));
    }

    /**
     * @expectedException Quick_Db_Exception
     */
    public function testSelectShouldThrowExceptionIfTooFewIntepolatedValues( ) {
        $this->_cut->select("SELECT ?, ?", array(1));
    }

    /**
     * @expectedException Quick_Db_Exception
     */
    public function testSelectShouldThrowExceptionIfTooManyIntepolatedValues( ) {
        $this->_cut->select("SELECT ?, ?", array(1, 2, 3));
    }

    public function testQueryShouldReturnBoolIfNoResults( ) {
        $this->_mysql->expects($this->once())->method('mysql_query')->will($this->returnValue(true));
        $rs = $this->_cut->query("SET profiling = 1");
        $this->assertTrue($rs);
    }

    public function testQueryShouldReturnFetchableResults( ) {
        $this->_mysql->expects($this->once())->method('mysql_query')->will($this->returnValue(self::FAKE_RESULT));
        $rs = $this->_cut->query("SELECT 1");
        $this->assertType('Quick_Db_SelectResult', $rs);
    }

    public function testSelectShouldCallQuery( ) {
        $this->_mysql->expects($this->once())->method('mysql_query')->with('SELECT 1')->will($this->returnValue(self::FAKE_RESULT));;
        $this->_cut->select("SELECT 1");
        $this->assertEquals(1, $this->_cut->calls['query']);
    }

    /**
     * @expectedException       Quick_Db_Exception
     */
    public function testSelectShouldThrowExceptionOnTrue( ) {
        $this->_mysql->expects($this->once())->method('mysql_query')->with('SELECT 1')->will($this->returnValue(true));;
        $this->_cut->select("SELECT 1");
    }

    public function testExecuteShouldCallQuery( ) {
        $this->_mysql->expects($this->once())->method('mysql_query')->with('SET profiling = 1')->will($this->returnValue(true));;
        $this->_cut->execute("SET profiling = 1");
        $this->assertEquals(1, $this->_cut->calls['query']);
    }

    /**
     * @expectedException       Quick_Db_Exception
     */
    public function testExecuteShouldThrowExceptionOnResults( ) {
        $this->_mysql->expects($this->once())->method('mysql_query')->with('SELECT 1')->will($this->returnValue(self::FAKE_RESULT));;
        $this->_cut->execute("SELECT 1");
    }

    public function testGetQueryInfoShouldReturnInfo( ) {
        $info = $this->_cut->getQueryInfo();
        $this->assertType('Quick_Db_QueryInfo', $info);
    }

    public function testSetProfilingShouldProfileSuccessfulSelect( ) {
        $datalogger = new Quick_Data_Datalogger_Array();
        // use the result set resource id "0" for testing, it will not be freed by SelectResult.
        // Note that mysql only returns true, false, or a non-zero resource id.
        $this->_mysql->expects($this->any())->method('mysql_query')->will($this->returnValue(0));
        $this->_cut->select("SELECT 11");
        $this->_cut->setProfiling($datalogger);
        $this->_cut->select("SELECT 22");
        $this->_cut->select("UPDATE 33");
        $data = $datalogger->getData();
        $this->assertEquals(2, count($data));
        $this->assertContains("SELECT 22", $data[0]);
        $this->assertContains("UPDATE 33", $data[1]);
    }

    public function testSetProfilingShouldProfileSuccessfulExec( ) {
        $datalogger = new Quick_Data_Datalogger_Array();
        $this->_mysql->expects($this->any())->method('mysql_query')->will($this->returnValue(true));
        $this->_cut->setProfiling($datalogger);
        $this->_cut->execute("UPDATE foo");
        $data = $datalogger->getData();
        $this->assertContains("UPDATE foo", $data[0]);
    }

    public function testSetProfilingShouldProfileErroringCall( ) {
        $datalogger = new Quick_Data_Datalogger_Array();
        $this->_mysql->expects($this->any())->method('mysql_query')->will($this->returnValue(false));
        $this->_cut->setProfiling($datalogger);
        try { $this->_cut->select("SELECT foo"); } catch (Exception $e) { }
        $data = $datalogger->getData();
        $this->assertContains("SELECT foo", $data[0]);
    }

    public function testAsListShouldReturnList( ) {
        $cut = $this->_createDb();
        $expect = array(1);
        $this->assertEquals($expect, $cut->select("SELECT 1 AS one")->asList()->fetch());
    }

    public function testAsHashShouldReturnHash( ) {
        $cut = $this->_createDb();
        $expect = array('one' => 1);
        $this->assertEquals($expect, $cut->select("SELECT 1 AS one")->asHash()->fetch());
    }

    public function testAsObjectShouldReturnObject( ) {
        $cut = $this->_createDb();
        $expect = new StdClass();
        $expect->one = 1;
        $this->assertEquals($expect, $cut->select("SELECT 1 AS one")->asObject(new StdClass())->fetch());
    }

    public function testAsObjectWithCallbackShouldReturnObject( ) {
        $cut = $this->_createDb();
        $callback = function ($r) { $ret = new StdClass(); foreach ($r as $k => $v) $ret->$k = $v; return $ret; };
        $expect = new StdClass();
        $expect->one = 1;
        $this->assertEquals($expect, $cut->select("SELECT 1 AS one")->asObject($callback)->fetch());
    }

    protected function _createDb( ) {
        global $phpunitDbCreds;
        $conn = new Quick_Db_Mysql_Connection($phpunitDbCreds, new Quick_Db_Mysql_Adapter());
        $link = $conn->createLink();
        return $cut = new Quick_Db_Mysql_Db($link, new Quick_Db_Mysql_Adapter($link));
    }

    public function xx_testSpeed( ) {
        // see also ar/time-db-speed.php, timings are 4x slower within phpunit

        $timer = new Quick_Test_Timer();
        $timer->calibrate(10000, array($this, '_testSpeedNull'), array($this->_cut));

        //$cut = $this->_cut;
        global $phpunitDbCreds;
        $creds = new Quick_Db_Credentials_Discrete("host=localhost,port=3306,user=andras,password=");
        //$conn = new Quick_Db_Mysql_Connection($phpunitDbCreds, new Quick_Db_Mysql_Adapter());
        $conn = new Quick_Db_Mysql_Connection($creds, new Quick_Db_Mysql_Adapter());
        $link = $conn->createLink();
        $cut = new Quick_Db_Mysql_Db($link, new Quick_Db_Mysql_Adapter($link));

        echo $timer->timeit(10000, "createLink", array($this, '_testSpeedCreateLink'), array($conn));
        // 15.0k/s without USE andras_test (the initial built-in config); 6.4k/s with

        echo $timer->timeit(10000, "query", array($this, '_testSpeedQuery'), array($cut));
        // 30-39k/s "select 1" (mostly 35-36k/s)

        $cut = new Quick_Db_Decorator_QueryTagger($cut);
        echo $timer->timeit(10000, "decorated (tagged) query", array($this, '_testSpeedQuery'), array($cut));
        // 17-20k/s (mostly 17k/s)

        echo $timer->timeit(10000, "result", array($this, '_testSpeedResult'), array($cut));
        // 17k/s "select 1"
        echo $timer->timeit(10000, "oneshot", array($this, '_testSpeedOneshot'), array($creds));
        // 8.3k/s "select 1" without USE db, 4.9k/s with
        echo $timer->timeit(10000, "oneshot persistent", array($this, '_testSpeedOneshotPersistent'), array($creds));
        // 17k/s

        echo $timer->timeit(10000, "mysql_connect", array($this, '_testSpeedMysqlConnect'), array($conn));
        // 15.8k/s
        echo $timer->timeit(10000, "mysql oneshot", array($this, '_testSpeedMysqlOneshot'), array($conn));
        // 9.3k/s -- 10-13% oneshot overhead for class hierarchy (not including autoload time!)
    }

    public function _testSpeedNull( $cut ) {
    }

    public function _testSpeedCreateLink( $conn ) {
        $conn->createLink();
    }

    public function _testSpeedQuery( $cut ) {
        $cut->query("SELECT 1");
    }

    public function _testSpeedSelect( $cut ) {
        $cut->execute("SELECT 1");
    }

    public function _testSpeedResult( $cut ) {
        //$cut->query("SELECT 1")->asList()->fetch();   // 17k/s
        //$cut->query("SELECT 1")->asColumn()->fetch();   // 17k/s
        $cut->query("SELECT 1")->fetch();   // 17.5k/s
    }

    public function _testSpeedOneshot( $creds ) {
        $adapter = new Quick_Db_Mysql_Adapter();
        $conn = new Quick_Db_Mysql_Connection($creds, $adapter);
        $link = $conn->createLink();
        $adapter->setLink($link);
        $db = new Quick_Db_Mysql_Db($link, $adapter);
        return $db->query("SELECT 1")->asColumn()->fetch();
    }

    public function _testSpeedOneshotPersistent( $creds ) {
        $adapter = new Quick_Db_Mysql_PersistentAdapter();
        $conn = new Quick_Db_Mysql_Connection($creds, $adapter);
        $link = $conn->createLink();
        $adapter->setLink($link);
        $db = new Quick_Db_Mysql_Db($link, $adapter);
        return $db->query("SELECT 1")->asColumn()->fetch();
    }

    public function _testSpeedMysqlConnect( $dummy ) {
        mysql_connect("localhost", "andras", null, true);
    }

    public function _testSpeedMysqlOneshot( $dummy ) {
        $link = mysql_connect("localhost", "andras", null, true);
        $rs = mysql_query("SELECT 1", $link);
        $rs = mysql_fetch_row($rs);
        return $rs[0];
    }
}
