<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_Response_HttpTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Rest_Response_Json();
        $this->_cut->setCli(true);
    }

    public function testResponseReturnsContentAsString( ) {
        $str = uniqid() . "test response";
        $this->_cut->setContent($str);
        $this->assertContains($str, "$this->_cut");
    }

    public function testResponseReturnsValuesAsJson( ) {
        $this->_cut->setValue('a', "test string");
        $this->assertEquals('{"a":"test string"}', "$this->_cut");
    }

    public function testSetContentShouldBeOutput( ) {
        $u = uniqid();
        $this->_cut->setContent($u);
        $this->assertContains($u, $this->_cut->getResponse());
    }

    public function testAppendContentShouldBeOutput( ) {
        $u = uniqid();
        $this->_cut->appendContent($u);
        $this->assertContains($u, $this->_cut->getResponse());
    }

    public function testAppendContentShouldBeOutputAppended( ) {
        $u1 = uniqid();
        $u2 = uniqid();
        $this->_cut->setContent($u1);
        $this->_cut->appendContent($u2);
        $output = $this->_cut->getResponse();
        $this->assertContains($u1, $output);
        $this->assertContains($u2, $output);
        $this->assertGreaterThan(strpos($output, $u1), strpos($output, $u2));
    }

    public function testAppendContentShouldBeOutputAfterOutputStarted( ) {
        $u1 = uniqid();
        $u2 = uniqid();
        $this->_cut->setCli(true);
        $this->_cut->setContent("$u1\n");
        ob_start();
        ob_start();
        $this->_cut->emitResponse();
        $output1 = ob_get_clean();
        $this->_cut->appendContent("$u2\n");
        $output2 = ob_get_clean();
        $this->assertContains($u1, $output1);
        $this->assertNotContains($u2, $output1);
        $this->assertContains($u2, $output2);
    }

    public function testGetResponseShouldReturnEmittedOutput( ) {
        $this->_cut->setValue('a', 1);
        $this->_cut->setContent("test");
        $this->_cut->setValue('b.c', 2, '.');
        ob_start();
        $this->_cut->emitResponse();
        $output1 = ob_get_clean();
        $output2 = $this->_cut->getResponse();
        $this->assertContains($output2, $output1);
    }

    public function testSetContentFileShouldBeIncludedInOutput( ) {
        $u = uniqid();
        $tempfile = tempnam("/tmp", "test-");
        file_put_contents($tempfile, str_repeat("test\n", 200000) . $u);
        $this->_cut->setContentFile($tempfile);
        $this->_cut->unsetValues();
        $output = $this->_cut->getResponse();
        unlink($tempfile);
        $this->assertContains($u, $output);
        $this->assertEquals(1000000 + strlen($u), strlen($output));
    }

    public function testResponseSeparatesContentFromJsonWithNewline( ) {
        $this->_cut->setContent("test response");
        $this->_cut->setValue('a', 'test string');
        $this->assertEquals("test response\n{\"a\":\"test string\"}", "$this->_cut");
    }

    public function testResponseAppendsToCollection( ) {
        $this->_cut->nameCollection("vars", "var");
        $this->_cut->appendCollection("vars", 1);
        $this->_cut->appendCollection("vars", "b");
        $this->assertEquals("{\"vars\":[1,\"b\"]}", "$this->_cut");
    }

    public function testResponseReturnsHttpHeadersInOrder( ) {
        $this->_cut->setHttpHeader('Header-One', 1);
        $this->_cut->setHttpHeader('Header-Two', 22);
        $this->_cut->setContent("Hello, world.");
        $output = $this->_emitResponse();

        $pos0 = strpos($output, "Status: 200");
        $pos1 = strpos($output, "Header-One: 1");
        $pos2 = strpos($output, "Header-Two: 22");
        $this->assertTrue($pos0 !== false);
        $this->assertLessThan($pos2, $pos1);
    }

    public function testCanSetHttpStatusCode( ) {
        $this->_cut->setStatus(123, "my status message");
        $response = $this->_emitResponse();
        $this->assertContains("HTTP/1", $response);
        $this->assertContains("123 my status message", $response);
        $this->assertContains("Status: 123", $response);
    }

    public function testRedirectReturnsLocationHeader( ) {
        $this->_cut->setHttpRedirect("my target url");
        $this->assertContains("Location: my target url", $this->_emitResponse());
    }

    public function testSetValueCanBuildNestedLists( ) {
        $this->_cut->setValue("a.a", 1, '.');
        $this->_cut->setValue("a.b.a", 2, '.');
        $this->_cut->setValue("a.b.b", 3, '.');
        $ret = $this->_cut->getResponse();
        $this->assertEquals(array('a' => array('a' => 1, 'b' => array('a' => 2, 'b' => 3))), json_decode($ret, true));
    }

    public function testSetValueCanInsertIntoCollection( ) {
        $this->_cut->nameCollection("items", "item");
        $this->_cut->setValue("items.a", 1, ".");
        $this->_cut->setValue("items.b", 22, ".");
        $ret = $this->_cut->getResponse();
        $this->assertEquals(array('items' => array('a' => 1, 'b' => 22)), json_decode($ret, true));
    }

    public function testSetValueCanAppendToCollection( ) {
        $this->_cut->nameCollection("a.items", "item", ".");
        $this->_cut->setValue("a.items.", 1, ".");
        $this->_cut->setValue("a.items.", 2, ".");
        $this->_cut->setValue("a.items.", 3, ".");
        $ret = $this->_cut->getResponse();
        $this->assertEquals(array('a' => array('items' => array('0' => 1, '1' => 2, '2' => 3))), json_decode($ret, true));
    }

    public function testUnsetValuesEmptiesValuesAndEmitsOnlyStringContent( ) {
        $this->_cut->setValue("a", 1);
        $this->_cut->setValue("b", 2);
        $this->_cut->setContent("foo\n");
        $this->assertEquals("foo\n{\"a\":1,\"b\":2}", $this->_cut->getResponse());
        $this->_cut->unsetValues();
        $this->assertEquals("foo\n", $this->_cut->getResponse());
    }

    public function testHasContentShouldReturnTrueIfContentHasBeenSet( ) {
        $this->assertFalse($this->_cut->hasContent());
        $this->_cut->setContent('');
        $this->assertTrue($this->_cut->hasContent());
        $this->_cut->setContent('abc');
        $this->assertTrue($this->_cut->hasContent());
        $this->_cut->setContent(null);
        $this->assertFalse($this->_cut->hasContent());
    }

    public function testHasValuesShouldReturnTrueIfValuesAreNotEmpty( ) {
        $this->assertFalse($this->_cut->hasValues());
        $this->_cut->setValue('a', 1);
        $this->assertTrue($this->_cut->hasValues());
        $this->_cut->unsetValues();
        $this->assertFalse($this->_cut->hasValues());
    }


    protected function _emitResponse( ) {
        ob_start();
        $this->_cut->emitResponse();
        return ob_get_clean();
    }
}
