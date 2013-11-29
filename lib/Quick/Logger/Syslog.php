<?php

/**
 * syslog(2) logger
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * @package     quicklib
 *
 * WARNING: some rsyslogd daemons rate-limit syslog messages, with a message eg
 * Sep 22 13:11:51 work2 rsyslogd-2177: imuxsock begins to drop messages from pid 21328 due to rate-limiting
 */

class Quick_Logger_Syslog
    extends Quick_Logger_Base
{
    protected $_syslogLevel;

    public function debug( $msg ) {
        $this->_syslogLevel = LOG_DEBUG;
        parent::debug($msg);
    }

    public function info( $msg ) {
        $this->_syslogLevel = LOG_INFO;
        parent::info($msg);
    }

    public function err( $msg ) {
        $this->_syslogLevel = LOG_ERR;
        parent::err($msg);
    }

    protected function _logit( $msg ) {
        syslog($this->_syslogLevel, $msg);
    }
}
