<?

class Quick_Db_Sql_SelectBatchTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        global $phpunitDbCreds;
        $this->_db = $this->getTestDb();
        $this->_db->execute("CREATE TEMPORARY TABLE __test (k int PRIMARY KEY AUTO_INCREMENT, v varchar(40))");
        $this->_cut = new Quick_Db_Sql_SelectBatch($this->_db, "SELECT k, v FROM __test", "", "__test", "k", 1);
    }

    public function batchDataProvider( ) {
        return array(
            array( array() ),
            // array( (array(null => 'a')) ),
            array( array(1 => 'a') ),
            array( array(1 => 'a', 2 => 'b', 3 => 'c', 4 => 'd') ),
            array( array(10 => 'a', 100 => 'b', 1000 => 'c', 10000 => 'd', 100000 => 'e') ),
            array( array(10 => 'a', 100 => 'b', 1000 => 'c', 10000 => 'd', 100000 => 'e', 1000000 => 'f') ),
        );
    }

    public function testSelectReturnsRowsWithNullIndex( ) {
        $this->_db->execute("ALTER TABLE __test MODIFY COLUMN k int, DROP PRIMARY KEY");
        $this->_db->execute("INSERT INTO __test (k, v) VALUES (NULL, 'a'), (NULL, 'b'), (NULL, 'c')");
        $this->_cut
            ->setSelectSql("SELECT k, v FROM __test")
            ->setBatchSize(2);
        $rows = $this->_fetchAllRows($this->_cut);
        // a batch includes every item with the same key
        $this->assertEquals(array('a', 'b', 'c'), $rows);
    }

    public function testSelectHonorsBatchSize( ) {
        $this->_db->execute("INSERT INTO __test (k, v) VALUES (1, 'a'), (2, 'b'), (3, 'c')");
        $this->_cut->setSelectSql("SELECT k, v FROM __test")->setBatchSize(2);
        $rs = $this->_cut->fetch()->asList()->fetchAll();
        $this->assertEquals(2, count($rs));
        $this->_cut->reset();
        $this->assertEquals(3, count($this->_fetchAllRows($this->_cut)));
    }

    public function testResetFetchesResultsAgain( ) {
        $this->_db->execute("INSERT INTO __test (k, v) VALUES (1, 'a'), (2, 'b'), (3, 'c')");
        $this->_cut->setSelectSql("SELECT k, v FROM __test")->setBatchSize(2);
        $rs = $this->_cut->fetch()->asList()->fetchAll();
        $this->_cut->reset();
        $rs2 = $this->_cut->fetch()->asList()->fetchAll();
        $this->assertEquals($rs, $rs2);
        $rs3 = $this->_cut->fetch()->asList()->fetchAll();
        $this->assertTrue(!empty($rs3));
    }

    /**
     * @dataProvider    batchDataProvider
     */
    public function testSelectReturnsAllRows( $data ) {
        if ($data) {
            $this->_db->execute(
                "INSERT INTO __test (k, v) VALUES " . implode(', ', $this->_arrayFill(0, count($data), '(?, ?)')),
                $this->_arrayKeyVals($data)
            );
        }
        foreach (array(1, 2, 3, 10) as $batchSize) {
            $this->_cut->reset();
            $this->_cut
                ->setSelectSql("SELECT k, v FROM __test")
                ->setBatchSize($batchSize);
            $rows = $this->_fetchAllRows($this->_cut);
            $this->assertEquals(array_values($data), $rows);
        }
    }


    protected function _arrayFill( $base, $count, $value ) {
        if ($count > 0)
            return array_fill($base, $count, $value);
        else
            return array();
    }

    protected function _arrayKeyVals( Array $a ) {
        $ret = array();
        foreach ($a as $k => $v) {
            $ret[] = $k;
            $ret[] = $v;
        }
        return $ret;
    }

    protected function _fetchAllRows( $fetcher ) {
        $ret = array();
        while (($rs = $fetcher->fetch()) !== false) {
            $batch = $rs->asHash()->fetchAll();
            foreach ($batch as $kv) {
                list($k, $v) = array_values($kv);
                $ret[] = $v;
            }
        }
        return $ret;
    }
}
