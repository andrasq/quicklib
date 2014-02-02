<?php

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Logger_SyslogTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = $this->getMock('Quick_Logger_Syslog', array('_logit'), array(Quick_Logger::DEBUG));
    }

    public function logMethodProvider( ) {
        return array(
            // array('debug'),
            array('info'),
            array('err'),
        );
    }

    /**
     * @dataProvider    logMethodProvider
     */
    public function testLogMethodShouldCallLogit( $method ) {
        $msg = "phpunit tag = " . uniqid();
        $this->_cut->expects($this->once())->method('_logit')->with($msg);
        $this->_cut->$method($msg);
    }

    /**
     * @dataProvider    logMethodProvider
     */
    public function testLogMethodShouldLogToSystemSyslog( $method ) {
        $msg = "phpunit tag = " . uniqid();
        $cut = new Quick_Logger_Syslog(Quick_Logger::DEBUG);
        $cut->$method($msg);
        usleep(100000);
        $this->assertContains($msg, $this->_getSyslogLines(20));
    }


    protected function _getSyslogLines( $nlines ) {
        $ret = "";
        // the syslog files are configurable by level, so try /var/log/{syslog,messages,debug}
        foreach (array("/var/log/syslog", "/var/log/messages", "/var/log/debug") as $file)
            if (file_exists($file)) $ret .= `tail -{$nlines} $file`;
        return $ret;
    }
}
