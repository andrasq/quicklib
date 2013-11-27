<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_InjectorTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_engine = $this->getMock('Quick_Injector_Engine', array('bindCallback', 'createInstance'));
        $this->_cut = new Quick_Injector($this->_engine);
    }

    public function testGetInstanceShouldReturnItemStoredWithSetInstance( ) {
        $value = uniqid();
        $this->_cut->setInstance('testclass', $value);
        $this->assertEquals($value, $this->_cut->getInstance('testclass'));
    }

    /**
     * @expectedException       Quick_Injector_Exception
     */
    public function testGetInstanceShouldThrowExceptionIfValueNotSet( ) {
        $this->_cut->getInstance('nonesuch_class');
    }

    public function testCreateInstanceShouldCallEngine( ) {
        $classname = uniqid();
        $this->_engine->expects($this->once())->method('createInstance')->with($classname);
        $this->_cut->createInstance($classname);
    }

    public function testBindCallbackShouldCallEngine( ) {
        $classname = uniqid();
        $this->_engine->expects($this->once())->method('bindCallback')->with($classname);
        $this->_cut->bindCallback($classname, array($this, 'method'), array());
    }

    public function testBindFactoryMethodShouldCallEngine( ) {
        $classname = uniqid();
        $this->_engine->expects($this->once())->method('bindCallback')->with($classname);
        $this->_cut->bindFactoryMethod($classname, 'factoryname', 'create', array());
    }
}
