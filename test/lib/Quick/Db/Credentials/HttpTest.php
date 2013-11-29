<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Credentials_HttpExposer extends Quick_Db_Credentials_Http {
    public function _parseCreds( $addr ) {
        return parent::_parseCreds($addr);
    }
}

class Quick_Db_Credentials_HttpTest
    extends Quick_Test_Case
{
    public function setUp(  ) {
        $this->_addr = "mysql:user+password@host:port+dbname";
        $this->_cut = new Quick_Db_Credentials_HttpExposer($this->_addr);
    }

    /**
     * @expectedException       Quick_Db_Exception
     */
    public function testConstructorShouldThrowExceptionOnInvalidAddress( ) {
        $cut = new Quick_Db_Credentials_HttpExposer("@host");
    }

    public function mysqlAddressProvider( ) {
        return array(
            array("user@", false),
            array("@host", false),
            array("user@:3306", false),
            array("user@+dbname", false),
            array("mysql:@host", false),
            array("mysql:+passwd@host", false),

            array(
                "user@host",
                array('user' => 'user', 'host' => 'host'),
            ),
            array(
                "user@host:socket",
                array('user' => 'user', 'host' => 'host', 'socket' => 'socket'),
            ),
            array(
                "user@host:3306",
                array('user' => 'user', 'host' => 'host', 'port' => '3306'),
            ),
            array(
                "user+password@host",
                array('user' => 'user', 'password' => 'password', 'host' => 'host'),
            ),
            array(
                "user+password@host:3306",
                array('user' => 'user', 'password' => 'password', 'host' => 'host', 'port' => '3306'),
            ),
            array(
                "user+password@host:3306+db",
                array('user' => 'user', 'password' => 'password', 'host' => 'host', 'port' => 3306, 'dbname' => 'db'),
            ),
            array(
                "mysql:usr+pwd@host:123+dbname",
                array('dbtype' => 'mysql', 'user' => 'usr', 'password' => 'pwd',
                      'host' => 'host', 'port' => '123', 'dbname' => 'dbname'),
            ),
            array(
                "usr@host?name1=value1&name2=value2",
                array('user' => 'usr', 'host' => 'host', 'name1' => 'value1', 'name2' => 'value2'),
            ),
            array(
                "mysql:usr+pwd@host:/tmp/mysql.sock+dbname",
                array('dbtype' => 'mysql', 'user' => 'usr', 'password' => 'pwd',
                      'host' => 'host', 'socket' => '/tmp/mysql.sock', 'dbname' => 'dbname'),
            ),

            array(
                "mysql://user+pass@dbhost",
                array('dbtype' => 'mysql', 'user' => 'user', 'password' => 'pass', 'host' => 'dbhost'),
            ),
        );
    }

    /**
     * @dataProvider    mysqlAddressProvider
     */
    public function testParseCredsShouldExtractValues( $addr, $expect ) {
        $creds = $this->_cut->_parseCreds($addr);
        if ($expect === false) $this->assertEquals(false, $creds);
        else $this->assertEquals($expect, array_filter($creds));
    }

    public function mysqlAddressFieldProvider( ) {
        return array(
            array("user12+password@host23", 'getHost', 'host23'),
            array("user12+password@host23:1234", 'getPort', '1234'),
            array("user12+password@host23:/var/run/mysqld/mysqld.sock", 'getSocket', '/var/run/mysqld/mysqld.sock'),
            array("user12+password@host23", 'getUser', 'user12'),
            array("user12+password333@host23", 'getPassword', 'password333'),
            array("user12+password333@host23+dbname777", 'getDatabase', 'dbname777'),
        );
    }

    /**
     * @dataProvider    mysqlAddressFieldProvider
     */
    public function testGetFieldShouldReturnField( $addr, $getMethod, $expect ) {
        $cut = new Quick_Db_Credentials_Http($addr);
        $this->assertEquals($expect, $cut->$getMethod());
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
        $cut = $this->_cut;
        $timer->calibrate(10000, array($this, '_testSpeedNull'), array($cut, "user@host"));
        echo $timer->timeit(10000, "parse", array($this, '_testSpeedParse'), array($cut, "user@host"));
        // 57k/sec w/ single @ suppression, 150k/sec w/ 5 isset() tests
    }

    public function _testSpeedNull( $cut, $addr ) {
    }

    public function _testSpeedParse( $cut, $addr ) {
        $cut->_parseCreds($addr);
    }
}
