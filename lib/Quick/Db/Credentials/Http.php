<?

/**
 * Credentials encoded in the form of an Http address, eg
 *     "mysql:user+password@host:port+dbname?name1=value1&name2=value2"
 * User and Host are required, the other fields are optional.
 * The trailing ?-args can override the credentials
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Db_Credentials_Http
    implements Quick_Db_Credentials
{
    protected $_addr, $_creds;

    public function __construct( $addr ) {
        $this->_addr = $addr;
        $this->_creds = $this->_parseCreds($addr);
        if ($this->_creds === false)
            throw new Quick_Db_Exception("unable to extract user and host from '$addr'");
    }

    public function getHost( ) {
        return $this->_creds['host'];
    }

    public function getPort( ) {
        return $this->_creds['port'];
    }

    public function getSocket( ) {
        return $this->_creds['socket'];
    }

    public function getUser( ) {
        return $this->_creds['user'];
    }

    public function getPassword( ) {
        return $this->_creds['password'];
    }

    public function getDatabase( ) {
        return $this->_creds['dbname'];
    }

    protected function _parseCreds( $addr ) {
        // parse "mysql://USER+password@HOST:port+dbname?name1=value1&name2=value2", USER @ HOST are required
        $patt =
            "((\w+):(\/\/)?)?" .        // mysql:
            "(\w+)" .                   // user
            "(\+([^@]*))?" .            // +password
            "@" .                       // @
            "([^+:\?\/]+)" .            // host
            "(:(([0-9]+)|([^+\?]+)))?" .// :port or :socket
            "(\+(\w+))?" .              // dbname
            "(\?(.*))?" .               // ?name1=value1&name2=value2
            "";
        if (!preg_match("/^{$patt}\$/", $addr, $mm))
            return false;

        $namevals = array();
        if (isset($mm[15])) {
            parse_str($mm[15], $namevals);
        }

        return $namevals + array(
            'dbtype' => isset($mm[2]) ? $mm[2] : null,
            'user' => $mm[4],
            'password' => isset($mm[6]) ? $mm[6] : null,
            'host' => $mm[7],
            'port' => isset($mm[10]) ? $mm[10] : null,
            'socket' => isset($mm[11]) ? $mm[11] : null,
            'dbname' => isset($mm[13]) ? $mm[13] : null,
        );
    }
}
