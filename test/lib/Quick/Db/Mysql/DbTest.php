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

class Quick_Db_Mysql_DbTest_MockLogger
    implements Quick_Logger
{
    public $lines = array();
    public function debug($msg) { $this->lines[] = $msg; }
    public function info($msg) { $this->lines[] = $msg; }
    public function err($msg) { $this->lines[] = $msg; }
    public function addFilter(Quick_Logger_Filter $f) { }
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

    public function testSetLoggerShouldTurnOnCallLogging( ) {
        $logger = new Quick_Db_Mysql_DbTest_MockLogger();
        // use the result set "0" for testing, it will not be freed by SelectResult.
        // Note that mysql only returns true, false, or a non-zero resource id.
        $this->_mysql->expects($this->any())->method('mysql_query')->will($this->returnValue(0));
        $this->_cut->select("SELECT 11");
        $this->_cut->setLogger($logger);
        $this->_cut->select("SELECT 22");
        $this->assertEquals(1, count($logger->lines));
        $this->assertContains("SELECT 22", $logger->lines[0]);
    }

    public function xx_testSpeed( ) {
        // see also ar/time-db-speed.php, timings are 4x slower within phpunit

        $timer = new Quick_Test_Timer();
        $timer->calibrate(10000, array($this, '_testSpeedNull'), array($this->_cut));

        //$cut = $this->_cut;
        global $phpunitDbCreds;
        $conn = new Quick_Db_Mysql_Connection($phpunitDbCreds, new Quick_Db_Mysql_Adapter());
        $cut = new Quick_Db_Mysql_Db($conn->getLink(), new Quick_Db_Mysql_Adapter($conn->getLink()));

        echo $timer->timeit(10000, "query", array($this, '_testSpeedQuery'), array($cut));
        // 30-39k/s "select 1"

        $cut = new Quick_Db_Decorator_QueryTagger($cut);
        echo $timer->timeit(10000, "decorated (tagged) query", array($this, '_testSpeedQuery'), array($cut));
        // 20k/s
    }

    public function _testSpeedNull( $cut ) {
    }

    public function _testSpeedQuery( $cut ) {
        $cut->query("SELECT 1");
    }

    public function _testSpeedSelect( $cut ) {
        $cut->execute("SELECT 1");
    }
}
