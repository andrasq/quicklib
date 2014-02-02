<?

class Quick_Db_Decorator_RetryReconnectTest
    extends Quick_Test_Case
{
    public function setUp( ) {
    }

    public function dbAccessMethodProvider( ) {
        return array(
            array('select'),
            array('execute'),
            array('query'),
        );
    }

    /**
     * @dataProvider    dbAccessMethodProvider
     */
    public function testAccessShouldReconnectOnRetryableError( $method ) {
        $conn = $this->_getMockDbConnection();
        $db = $this->_getMockDbEngineWithRetryableError();
        $cut = new Quick_Db_Decorator_RetryReconnect($conn, $db);
        $db->expects($this->exactly(2))->method($method)->will(
            $this->onConsecutiveCalls(
                $this->throwException(new Quick_Db_Exception("fail"),
                $this->returnValue(true)
            )));
        $cut->$method("QUERY");
    }

    /**
     * @dataProvider            dbAccessMethodProvider
     * @expectedException       Quick_Db_Exception
     */
    public function testAccessShouldThrowExceptionOnNonRetryableError( $method ) {
        $conn = $this->_getMockDbConnection();
        $db = $this->_getMockDbEngineWithQueryError();
        $cut = new Quick_Db_Decorator_RetryReconnect($conn, $db);
        $db->expects($this->exactly(1))->method($method)->will($this->throwException(new Quick_Db_Exception("fail")));
        $cut->$method("QUERY");
    }


    protected function _getMockDbConnection( ) {
        $conn = $this->getMockSkipConstructor('Quick_Db_Connection', array('configure', 'createLink', 'configureLink'));
        return $conn;
    }

    protected function _getMockDbEngineWithRetryableError( ) {
        $db = $this->getMockSkipConstructor('Quick_Db_Fake_Db', array('select', 'execute', 'query', 'setLink', 'getQueryInfo'));
        $info = $this->getMock('Quick_Db_QueryInfo', array('getError', 'getErrno', 'getAffectedRows', 'getLastInsertId'));
        $info->expects($this->any())->method('getError')->will($this->returnValue("MySQL server has gone away"));
        $info->expects($this->any())->method('getErrno')->will($this->returnValue(2006));
        $db->expects($this->any())->method('getQueryInfo')->will($this->returnValue($info));
        return $db;
    }

    protected function _getMockDbEngineWithQueryError( ) {
        $db = $this->getMockSkipConstructor('Quick_Db_Fake_Db', array('select', 'execute', 'query', 'setLink', 'getQueryInfo'));
        $info = $this->getMock('Quick_Db_QueryInfo', array('getError', 'getErrno', 'getAffectedRows', 'getLastInsertId'));
        $info->expects($this->any())->method('getError')->will($this->returnValue("non-retryable MySQL error"));
        $info->expects($this->any())->method('getErrno')->will($this->returnValue(1001));
        $db->expects($this->any())->method('getQueryInfo')->will($this->returnValue($info));
        return $db;
    }
}
