<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Reply_HttpTest
    extends Quick_Test_Case
{
    public function testGetReplyReturnsEntireMessage( ) {
        $message = microtime(true) . ' ' . uniqid();
        $this->assertEquals($message, $this->_createFromString($message)->getReply());
    }

    public function testSetReplyShouldBecomeReply( ) {
        $cut = new  Quick_Rest_Reply_Http("");
        $cut->setReply($str = uniqid());
        $this->assertEquals($str, $cut->getReply());
    }

    public function testSetReplyShouldSetStatusAndMessage( ) {
        $cut = new  Quick_Rest_Reply_Http("");
        $cut->setReply("body", $s = rand(), $m = uniqid());
        $this->assertEquals($s, $cut->getStatus());
        $this->assertEquals($m, $cut->getMessage());
    }

    public function blankLineProvider( ) {
        return array(
            array("\r\n\r\n"),
            array("\n\n"),
            array("\r\n\n"),
            array("\n\r\n"),
        );
    }

    /**
     * @dataProvider    blankLineProvider
     */
    public function testGetBodyReturnsMessageFollowingFirstBlankLine( $blankline ) {
        $header = $this->_makeHeader();
        $body = microtime(true) . ' ' . uniqid() . "\r\n" . uniqid() . "\n" . uniqid() . "\r\n\n";
        $message = $header . $blankline . $body;
        $this->assertEquals($body, $this->_createFromString($message)->getBody());
    }

    public function testGetHeadersReturnsHeadersAsHash( ) {
        $header = $this->_makeHeader(array("H1: 1", "H2: 2", "H3: 3"));
        $body = "some body text";
        $message = $header . "\r\n\r\n" . $body;
        $hash = $this->_createFromString($message)->getHeaders();
        $this->assertEquals(1, $hash['H1']);
        $this->assertEquals(2, $hash['H2']);
        $this->assertEquals(3, $hash['H3']);
        $this->assertNull(@$hash['H4']);
    }

    public function testGetStatusReturnsHeaderStatus( ) {
        $header = $this->_makeHeader(array(), 123);
        $message = $header . "\r\n\r\n" . "some body text";
        $this->assertEquals(123, $this->_createFromString($message)->getStatus());
    }

    public function testGetMessageShouldReturnHeaderStatusMessage( ) {
        $header = $this->_makeHeader(array(), 123, $u = uniqid());
        $message = $header . "\r\n\r\n" . "some body text";
        $this->assertEquals($u, $this->_createFromString($message)->getMessage());
    }

    public function testGetStatusReturnsStatusLineIfPresent( ) {
        $header = $this->_makeHeader(array("Status: 456"), 123);
        $message = $header . "\r\n\r\n" . "some body text";
        $this->assertEquals(456, $this->_createFromString($message)->getStatus());
    }

    public function testGetHeadersWillCombineSeparateCookiesLines( ) {
        $header = $this->_makeHeader(array());
        $header .= "\r\n";
        $header .= "Set-Cookie: cookie1=1, cookie2=2\r\n";
        $header .= "Set-Cookie: cookie3=3, cookie4=4\r\n";
        $message = $header . "\r\n" . "body text";
        $cut = $this->_createFromString($message);
        $headers = $cut->getHeaders();
        $this->assertContains('cookie3=3', $headers['Set-Cookie']);
        $this->assertContains('cookie2=2', $headers['Set-Cookie']);
    }

    public function xx_testSpeed( ) {
        $timer = new Quick_Test_Timer();
    }

    public function _testNullSpeed( $cut ) {
    }

    public function _testGetStatusSpeed( $cut ) {
    }

    protected function _makeHeader( Array $headers = array(), $status = 200, $message = "OK" ) {
        $header[] = "HTTP/1.0 $status $message";
        $header[] = "Content-Type: application/json";
        $header[] = "Header-Line-1: abc def";
        $header[] = "Header-Line-2: ghi jkl; mnop";
        foreach ($headers as $line) {
            $header[] = $line;
        }
        return implode("\r\n", $header);
    }

    protected function _createFromString( $str ) {
        return new Quick_Rest_Reply_Http($str);
    }
}
