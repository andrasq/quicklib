<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Credentials_Discrete
    implements Quick_Db_Credentials
{
    protected $_host;
    protected $_port;
    protected $_socketFile;
    protected $_user;
    protected $_password;
    protected $_database;

    public function __construct( $csv = "" ) {
        if ($csv !== "") $this->setCsv($csv);
    }

    public static function create( $csv = "" ) {
        //$class = get_called_class();  // php 5.3
        $class = __CLASS__;
        return new $class($csv);
    }

    public function getHost( ) {
        return $this->_host;
    }
    public function getPort( ) {
        return $this->_port;
    }
    public function getSocket( ) {
        return $this->_socketFile;
    }
    public function getUser( ) {
        return $this->_user;
    }
    public function getPassword( ) {
        return $this->_password;
    }

    public function getDatabase( ) {
        return $this->_database;
    }

    public function getMysqlConnectString( ) {
        return $this->socket ? "$this->_host:$this->_socket"
                             : ($this->_port ? "$this->_host:$this->_port"
                                             : "$this->_host");
    }

    public function getPdoConnectString( ) {
        return /* "mysql:" */"host={$this->_host};port={$this->_port};dbname={$this->_database}";
    }

    public function setCsv( $csv ) {
        static $fieldMap = array(
            'host' => '_host', 'port' => '_port', 'socket' => '_socketFile',
            'user' => '_user', 'password' => '_password', 'database' => '_database',
        );
        foreach (explode(',', $csv) as $nameval) {
            list($name, $value) = explode('=', $nameval, 2);
            if (isset($fieldMap[$name])) {
                $this->{$fieldMap[$name]} = $value;
            }
        }
        return $this;
    }

    public function setHost( $host ) {
        $this->_host = $host;
        return $this;
    }

    public function setUserPassword( $user, $password ) {
        $this->_user = $user;
        $this->_password = $password;
        return $this;
    }

    public function setDatabase( $database ) {
        $this->_database = $database;
        return $this;
    }

    public function setPort( $port ) {
        $this->_port = $port;
        return $this;
    }

    public function setSocket( $socket ) {
        $this->_socket = $socket;
        return $this;
    }
}
