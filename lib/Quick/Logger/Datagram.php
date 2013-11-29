<?php

/**
 * datagram logger
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * This logger does not offer reliable message delivery, which
 * leaves it more of a curiosity and pedagogic/perftest example.
 *
 * @package     quicklib
 *
 * 2013-05-27 - AR.
 */

class Quick_Logger_Datagram
    extends Quick_Logger_Base
{
    protected $_host, $_port;

    public function __construct( $host, $port, $loglevel = self::INFO ) {
        $this->_host = $host;
        $this->_port = $port;
        $this->_loglevel = $loglevel;
    }

    protected function _logit( $msg ) {
        $target = $this->_host ? "udp://$this->_host:$this->_port" : "udp://$this->_port";
        $sock = stream_socket_client($target, $errno, $errstr, $timeout = .25);
        if (!$sock) throw new Quick_Logger_Exception("$target: unable to open socket");
        fputs($sock, $msg);
    }
}
