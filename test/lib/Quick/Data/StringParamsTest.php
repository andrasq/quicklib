<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Data_StringParamsTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Data_StringParams();
        $this->_cut->setSeparator("/");
        $this->_cut->setNameDelimiters("{}");
    }

    public function testSetSeparatorShouldChangeSubstringComponentBoundaries( ) {
        $ret = $this->_cut
            ->setSeparator("x")
            ->setTemplate("{a}x{b}")
            ->getParams("1x2");
        $this->assertEquals(array('a' => 1, 'b' => 2), $ret);
    }

    public function testSetNameDelimitersShouldChangeTemplateNameScanning( ) {
        $ret = $this->_cut
            ->setNameDelimiters("[]")
            ->setTemplate("[a]/[b]")
            ->getParams("1/2");
        $this->assertEquals(array('a' => 1, 'b' => 2), $ret);
    }

    public function testSetTemplateShouldUseTemplateToMatch( ) {
        $ret = $this->_cut
            ->setTemplate("a/b")
            ->getParams("a/b");
        $this->assertEquals(array(), $ret);
    }

    public function getParamsTestCaseProvider( ) {
        return array(
            // the template does not match the string
            array("a", "b", false),             // only component does not match
            array("a/b", "b/b", false),         // first componenet does not match
            array("a/b", "a/a", false),         // second component does not match
            array("a/b/c", "a/b", false),       // string too short
            array("a//b", "a/b", false),        // missing component from string
            array("a/b", "a//b", false),        // extra component in string
            array("/a", "a", false),            // missing component at front
            array("a/", "a", false),            // missing component at end
            array("a", "/a", false),            // extra component at front of string

            // the template matches the string but no parameters
            array("", "", array()),             // empty strings match
            array("a", "a", array()),           // single component match
            array("a/b", "a/b", array()),       // multiple components match
            array("a/b", "a/b/c", array()),     // ok if string is longer
            array("a", "a/", array()),          // ok if string is longer

            // the template matches and extracts parameters from the string
            array("{a}", "a", array('a' => 'a')),
            array("{a}/b", "a/b", array('a' => 'a')),
            array("a/{b}", "a/b", array('b' => 'b')),
            array("{a}/{b}", "a/b", array('a' => 'a', 'b' => 'b')),
            array("{a}/b/{c}/d/{e}", "a/b/c/d/e", array('a' => 'a', 'c' => 'c', 'e' => 'e')),

            // weird corner cases
            array("a//{b}///c", "a//b///c", array('b' => 'b')),
            array("a//{b}", "a/b", false),
        );
    }

    /**
     * @dataProvider    getParamsTestCaseProvider
     */
    public function testGetParamsShouldReturnExpectedResults( $template, $string, $expect ) {
        $ret = $this->_cut->setTemplate($template)->getParams($string);
        $this->assertEquals($expect, $ret);
    }
}
