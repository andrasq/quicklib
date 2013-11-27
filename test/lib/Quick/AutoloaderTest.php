<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_AutoloaderTest_EngineExposer extends Quick_Autoloader_Engine {
    public function __construct( ) {
    }
}

class Quick_AutoloaderTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_engine = $this->getMock(
            'Quick_AutoloaderTest_EngineExposer',
            array('addClass', 'addSearchPath', 'addSearchTree', 'addCallback')
        );
        $this->_cut = new Quick_Autoloader($this->_engine);
        $this->_v1 = uniqid();
        $this->_v2 = uniqid();
        $this->_v3 = uniqid();
    }

    public function testAddClassShouldCallEngine( ) {
        $this->_engine->expects($this->once())->method('addClass')->with($this->_v1, $this->_v2);
        $this->_cut->addClass($this->_v1, $this->_v2);
    }

    public function testAddSearchPathShouldCallEngine( ) {
        $this->_engine->expects($this->once())->method('addSearchPath')->with($this->_v1, $this->_v2);
        $this->_cut->addSearchPath($this->_v1, $this->_v2);
    }

    public function testAddSearchTreeShouldCallEngine( ) {
        $this->_engine->expects($this->once())->method('addSearchTree')->with($this->_v1, $this->_v2);
        $this->_cut->addSearchTree($this->_v1, $this->_v2);
    }

    public function testAddCallbackShouldCallEngine( ) {
        $this->_engine->expects($this->once())->method('addCallback')->with($this->_v1, $this->_v2, $this->_v3);
        $this->_cut->addCallback($this->_v1, $this->_v2, $this->_v3);
    }

    /**
     * @expectedException       Exception
     */
    public function omit_testAddSearchPathThrowsExceptionOnBadDirectory( ) {
        $cut = new Quick_Autoloader(new Quick_AutoloaderTest_EngineExposer());
        $cut->addSearchPath("/nonesuch", ".php");
    }

    /**
     * @expectedException       Exception
     */
    public function omit_testAddSearchTreeThrowsExceptionOnBadDirectory( ) {
        $cut = new Quick_Autoloader(new Quick_AutoloaderTest_EngineExposer());
        $cut->addSearchTree("/nonesuch", ".php");
    }

    /**
     * @expectedException       Exception
     */
    public function testAddCallbackThrowsExceptionOnBadCallbackMethod( ) {
        $cut = new Quick_Autoloader(new Quick_AutoloaderTest_EngineExposer());
        $cut->addCallback("/tmp", array($this, 'nonesuchMethod'), ".php");
    }
}
