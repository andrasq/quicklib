<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_Bundle_XmlTerseTest
    extends Quick_Test_Case
{
    public function testTerseXmlShouldNotContainNewlinesOrIndentation( ) {
        $cut = new Quick_Data_Bundle_XmlTerse();
        $cut->set('a', 1);
        $cut->set('b', 2);
        $cut->setXmlHeader("");
        $xml = (string) $cut;
        $this->assertNotContains("\n", substr($xml, 0, -1));
        $this->assertNotContains(" ", $xml);
    }
}
