<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_Bundle_XmlTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Data_Bundle_Xml();
    }

    public function testToStringShouldOutputValidXml( ) {
        $this->assertType('array', $this->_getXmlArray());
    }

    public function testToStringShouldSupplyEnvelopeForEmptyHash( ) {
        $this->assertEquals(array(), $this->_getXmlArray());
    }

    public function testToStringShouldSupplyEnvelopeForMultiValuedHash( ) {
        $this->_cut->set('a', 1);
        $this->_cut->set('b', 2);
        $this->assertEquals(array('a' => 1, 'b' => 2), $this->_getXmlArray());
    }

    public function testToStringShouldUseSingleElementAsTheEnvelope( ) {
        $this->_cut->set('envelope', array('a' => 1));
        $this->assertEquals(array('a' => 1), $this->_getXmlArray());
    }

    public function testToStringShouldOutputHierarchy( ) {
        $this->_cut->withSeparator('.')->set('envelope.a.b.c1', 1);
        $this->_cut->withSeparator('.')->set('envelope.a.b.c2', 2);
        $this->_cut->withSeparator('.')->set('envelope.a.b2', 3);
        $this->assertEquals(array('a' => array('b' => array('c1' => 1, 'c2' => 2), 'b2' => 3)), $this->_getXmlArray());
    }

    public function testToStringShouldOutputListsInHierarchy( ) {
        $this->_cut->withSeparator('.')->push('envelope.a.b.c', 1);
        $this->_cut->withSeparator('.')->push('envelope.a.b.c', 2);
        $expect = array('a' => array('b' => array('c' => array(1, 2))));
        $this->assertEquals(array('envelope' => $expect), $this->_cut->shareValues());
        $this->assertEquals($expect, $this->_getXmlArray());
    }

    public function testSetListItemNameShouldChangeListItemNames( ) {
        $this->_cut->withSeparator('.')->push('envelope.a.b.c', 1);
        $this->_cut->withSeparator('.')->push('envelope.a.b.c', 2);
        $this->_cut->withSeparator('.')->setListItemName('envelope.a.b.c', 'item');
        $xml = (string)$this->_cut;
        $this->assertContains("<item>1</item>", $xml);
        $this->assertContains("<item>2</item>", $xml);
        $this->assertEquals(array('a' => array('b' => array('c' => array('item' => array(1, 2))))), $this->_getXmlArray($xml));
    }

    public function testXmlTagsShouldAllowAttributesAfterASpace( ) {
        $this->_cut->set('name p1="1" p2="2"', 'value');
        $xml = (string) $this->_cut;
        $this->assertEquals(array('name' => 'value'), $this->_getXmlArray($xml));
        $this->assertContains('<name p1="1" p2="2">', $xml);
        $this->assertContains('</name>', $xml);
    }

    public function testXmlShouldEscapeHtmlSpecialCharsButLeaveAttributeQuotesInPlace( ) {
        $this->_cut->set('a.b ns:p="1"', "&<>;'\"");
        $xml = (string) $this->_cut;
        $this->assertContains('<a.b ns:p="1">&amp;&lt;&gt;;\'&quot;</a.b>', $xml);
    }

    public function testToStringShouldStripControlChars( ) {
        $this->_cut->set('a', "one\x01");
        $this->assertContains("<a>one</a>", (string)$this->_cut);
    }

    protected function _getXmlArray( ) {
        $str = (string) $this->_cut;
        $xml = simplexml_load_string($str);
        if (!is_object($xml)) return false;
        return json_decode(json_encode($xml), true);
    }
}
