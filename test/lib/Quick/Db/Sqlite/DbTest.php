<?

class Quick_Db_Sqlite_DbTest
    extends Quick_Test_Case
{
    protected function _createDb( $httpCreds ) {
        $creds = $httpCreds instanceof Quick_Db_Credentials ? $httpCreds : new Quick_Db_Credentials_Http($httpCreds);
        $this->_adapter = new Quick_Db_Sqlite_Adapter();
        @unlink("/tmp/test-sqlite.db");
        $db = new Quick_Db_Sqlite_Db(sqlite_open("/tmp/test-sqlite.db"), $this->_adapter);
        //$db->execute("PRAGMA synchronous = OFF");
        $db->execute("PRAGMA temp_store = FILE");   // file = 7.1k/s, memory = 31k/s
        unlink("/tmp/test-sqlite.db");
        $db->execute("CREATE TEMPORARY TABLE test (i INT, f DOUBLE, t TEXT)");
        return $db;
    }

    public function setUp( ) {
        if (!function_exists('sqlite_open')) $this->markTestSkipped();
        global $testDbCreds;
        $this->_db = $this->_createDb($testDbCreds);
    }

    public function testDbShouldSelectValues( ) {
        $rs = $this->_db->select("SELECT 1, 2, 3");
        $this->assertEquals(array(1, 2, 3), $rs->asList()->fetch());
    }

    public function testDbShouldInsertValues( ) {
        $this->_db->execute("INSERT INTO test (i, f, t) VALUES (1, 1.01, 'one')");
        $this->_db->execute("INSERT INTO test (i, f, t) VALUES (2, 2.02, 'two')");
        $rs = $this->_db->select("SELECT * FROM test");
        $this->assertEquals(array(1, 1.01, 'one'), $rs->asList()->fetch());
        $this->assertEquals(array(2, 2.02, 'two'), $rs->asList()->fetch());
    }

    public function xx_testDbShouldCreateTable( ) {
        $ok = $this->_db->execute("CREATE TABLE test2 (i INT)");
        for ($i=1; $i<= 3; ++$i) $this->_db->execute("INSERT INTO test2 (i) VALUES ($i)");
        // FIXME: reads no values from test2??
        $rs = $this->_db->select("SELECT * FROM test2");
//print_r($rs);
//var_dump($rs->asHash()->fetchAll());
        $this->assertEquals(array(array('i' => 1), array('i' => 2), array('i' => 3)), $rs->asHash()->fetchAll());
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $dbfile = "/tmp/test-sqlite.db";
        //$dbfile = "/dev/shm/test-sqlite.db";

// test: figure out how to make sqlite faster than 200-700 oneshot inserts / sec
        $link = sqlite_open($dbfile);
        sqlite_exec($link, "CREATE TABLE test (i INT, f DOUBLE, t TEXT); -- ADD INDEX ON test (i, f)");
        sqlite_exec($link, "PRAGMA journal_mode = WAL");  // persistent per db

        $tm = microtime(true);
        $link = sqlite_open($dbfile);
        sqlite_exec($link, "PRAGMA synchronous = OFF");
        sqlite_exec($link, "PRAGMA wal_autocheckpoint = 0");    // no autocheckpoint
        for ($i=0; $i<1; ++$i)
            $ok = sqlite_query($link, "insert into test values(11, 11.11, 'eleven')");
        $tm = microtime(true) - $tm;
/**
echo "AR: opened link in $tm sec\n";
// note: .01 sec to open and insert -- first insert is *very* slow!  ...setup/init???
$rs = sqlite_query($link, "SELECT * FROM test");
//var_dump($rs);
//while ($row = sqlite_fetch_array($rs, SQLITE_ASSOC)) print_r($row);
**/
        $timer->calibrate(1000, array($this, '_testSpeedNull'), array($this->_db, array($dbfile)));
        echo $timer->timeit(1000, 'open', array($this, '_testSpeedCreate'), array($dbfile));
        // 5300/sec

        unlink($dbfile);
        $db = new Quick_Db_Sqlite_Db(sqlite_open($dbfile), $this->_adapter);
        $db->execute("CREATE TABLE test (i INT, f DOUBLE, t TEXT); -- ADD INDEX ON test (i, f)");
        //$db->execute("PRAGMA synchronous = OFF");    // no diff to insert, helps a lot oneshot
        //$db->execute("PRAGMA journal_mode = WAL");   // persistent per db

        $timer->calibrate(1000, array($this, '_testSpeedNull'), array($this->_db, array(1, 1.01, 'one')));
        echo $timer->timeit(1000, 'insert', array($this, '_testSpeedInsert'), array($this->_db, array(1, 1.01, 'one')));
        // 7100/sec 1k, 6200/sec 100k file (31k/s memory)
        echo $timer->timeit(1, 'oneshot', array($this, '_testSpeedOneshot'), array($dbfile, array(1, 1.01, 'one')));
        // 100 to 700/sec ??? (why so variable?  why so slow??) (numbers repeatable w/ vanilla sqlite_* calls)
        // A: PRAGMA synchronous = OFF raises to 2100/sec (still poor, but usable)

        unlink($dbfile);
    }

    public function _testSpeedNull( $db, Array $data ) {
    }

    public function _testSpeedInsert( $db, Array $data ) {
        $this->_db->execute("INSERT INTO test VALUES(?, ?, ?)", $data);
    }

    public function _testSpeedCreate( $name ) {
        $db = new Quick_Db_Sqlite_Db(sqlite_open($name), new Quick_Db_Sqlite_Adapter());
    }

    public function _testSpeedOneshot( $name, $data ) {
        $db = new Quick_Db_Sqlite_Db(sqlite_open($name), new Quick_Db_Sqlite_Adapter());
        $db->execute("PRAGMA synchronous = OFF");
        $db->execute("INSERT INTO test VALUES(?, ?, ?)", $data);
    }
}
