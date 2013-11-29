<?

class Quick_Rest_Response_XmlTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Rest_Response_Xml();
    }

    public function testShouldGenerateValidEmptyXml( ) {
        $str = $this->_cut->getResponse();
        $this->assertContains("<xml", $str);
        $this->assertContains(">\n</xml>\n", $str);
        $xml = simplexml_load_string($str);
        $this->assertTrue(is_object($xml));
        $this->assertEquals(array(), get_object_vars($xml));
    }

    public function testShouldGenerateValidXmlForScalars( ) {
        $this->_cut->setValue('a', 1);
        $this->_cut->setValue('b', 2.5);
        $this->_cut->setValue('c', "hello");
        $this->_cut->setValue('d', null);
        $xml = simplexml_load_string($this->_cut->getResponse());
        $this->assertEquals((string)1, (string)$xml->a);
        $this->assertEquals((string)2.5, (string)$xml->b);
        $this->assertEquals((string)'hello', (string)$xml->c);
        $this->assertEquals((string)'', (string)$xml->d);
    }

    public function testShouldGenerateValidXmlForObjects( ) {
        $this->_cut->setValue('o1', (object) array('a' => 1, 'b' => 2.5, 'c' => 'hello'));
        $this->_cut->setValue('o2', (object) array('a' => 1, 'b' => 2.5, 'c' => 'hello'));
        $xml = simplexml_load_string($this->_cut->getResponse());
        foreach (array('o1', 'o2') as $field) {
            $this->assertEquals((string)1, (string)$xml->$field->a);
            $this->assertEquals((string)2.5, (string)$xml->$field->b);
            $this->assertEquals('hello', (string)$xml->$field->c);
        }
    }

    public function testShouldGenerateValidXmlForNestedObjects( ) {
        $obj = (object) array('a' => 1, 'b' => 2.5, 'c' => 'hello');
        $obj2 = (object) array('a' => 1, 'o' => $obj);
        $this->_cut->setValue('o', $obj2);
        $xml = simplexml_load_string($this->_cut->getResponse());
        $this->assertEquals((string)1, (string)$xml->o->a);
        $this->assertEquals((string)1, (string)$xml->o->o->a);
        $this->assertEquals((string)2.5, (string)$xml->o->o->b);
        $this->assertEquals('hello', (string)$xml->o->o->c);
    }

    public function tsetShouldGenerateValidXmlForArrays( ) {
    }

    public function testShouldGenerateValidXmlForNestedArrays( ) {
        $this->_cut->setValue('a', array('b' => array(1,2,3)));
        $str = $this->_cut->getResponse();
        $this->assertContains("  <a>", $str);
        $this->assertContains("    <b>", $str);
        $this->assertContains("      <0>1</0>", $str);
        $this->assertContains("      <1>2</1>", $str);
        $this->assertContains("      <2>3</2>", $str);
        $this->assertContains("    </b>\n  </a>", $str);
    }

    public function testShouldGenerateCollectionWithIdenticalElementNames( ) {
        $this->_cut->setValue('a', array(1,2,3));
        $this->_cut->nameCollection('a', 'x');
        $str = $this->_cut->getResponse();
        foreach (array(1,2,3) as $x)
            $this->assertContains("  <x>$x</x>\n", $str);
    }

    public function testShouldGenerateNestedCollectionWithIdenticalElementNames( ) {
        $this->_cut->setValue('a.b', array(1,2,3), '.');
        $this->_cut->nameCollection('a.b', 'y', '.');
        $str = $this->_cut->getResponse();
        foreach (array(1,2,3) as $x)
            $this->assertContains("    <y>$x</y>\n", $str);
    }

    public function testAppendCollectionShouldAddToList( ) {
        $this->_cut->nameCollection('a', 'x');
        $this->_cut->setValue('a', array(1,2,3));
        $this->_cut->appendCollection('a', 4);
        $str = $this->_cut->getResponse();
        $this->assertContains("  <x>4</x>\n", $str);
    }

    public function testAppendCollectionShouldAddToNestedList( ) {
        $this->_cut->nameCollection('a.b.c', 'x', '.');
        $this->_cut->setValue('a.b.c', array(1,2,3), '.');
        //$this->_cut->setValue('a.b.c.', 4, '.');
        $this->_cut->appendCollection('a.b.c', 4, '.');
        $str = $this->_cut->getResponse();
        $this->assertContains("        <x>4</x>\n", $str);
    }

    public function testShouldAllowNestedCollection( ) {
        $this->_cut->setValue('a' , array('a' => 1, 'x' => array('a' => 1, 'y' => array(1,2,3))));
        $this->_cut->nameCollection('a.x.y', 'v', '.');
        $str = $this->_cut->getResponse();
        $xml = simplexml_load_string($this->_cut->getResponse());
        $this->assertEquals('1', (string)$xml->a->x->y->v[0]);
        $this->assertEquals('2', (string)$xml->a->x->y->v[1]);
        $this->assertEquals('3', (string)$xml->a->x->y->v[2]);
    }

    public function testEmitShouldStripControlCharsFromText( ) {
        $this->_cut->setValue('a', "ab\001c");
        $xml = $this->_cut->getResponse();
        $this->assertContains("<a>abc</a>", $xml);
    }

    public function testEmitShouldConvertLatin1ToUtf8( ) {
        $this->_cut->setValue('a', "ab\x80c");
        $xml = $this->_cut->getResponse();
        $this->assertContains("<a>ab\xC2\x80c</a>", $xml);
    }

    public function testXmlShouldBeValidForAllAsciiCharsInInput( ) {
        $str = implode("", array_map('chr', range(0, 127)));
        $this->_cut->setValue('a', $str);
        $xml = $this->_cut->getResponse();
        $this->assertType('SimpleXMLElement', simplexml_load_string($xml));
    }

    public function testXmlShouldBeValidForAllExtendedCharactersInInput( ) {
        // this breaks on parsing &nbsp;
        $this->markTestSkipped();
        $str = implode("", array_map('chr', range(129, 255)));
        $this->_cut->setValue('a', $str);
        $xml = $this->_cut->getResponse();
        $this->assertType('SimpleXMLElement', simplexml_load_string($xml));
    }
}
