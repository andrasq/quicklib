<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Injector_EngineTest_TestClassNoConstructor {
}

class Quick_Injector_EngineTest_TestClassSimpleConstructor {
    public function __construct( stdClass $a, Quick_Injector_EngineTest_TestClassNoConstructor $b, $c = 123 ) {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }
}

class Quick_Injector_EngineTest_TestClassCreateMethod {
    public static function create( ) {
        return new self();
    }
}

class Quick_Injector_EngineTest_TestClassFactoryConstructor {
}

class Quick_Injector_EngineTest_TestClassFactoryConstructor_Factory {
    public function create( $a = 1 ) {
        return 1;
    }

    public function create2( $a, $b ) {
        return 2;
    }
}

class Quick_Injector_EngineTest_TestClassUnknownConstructorParams {
    public function __construct( $a, $b ) {
    }
}

class Quick_Injector_EngineTest_TestClassNotInstantiable {
    protected function __construct( $a, $b ) {
    }
}

interface Quick_Injector_EngineTest_TestInterfaceNotInstantiable {
}

class Quick_Injector_EngineTest_TestClassIndirectlyNotInstantiable {
    protected function __construct( Quick_Injector_EngineTest_TestClassNotInstantiable $a ) {
    }
}

class Quick_Injector_EngineTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Injector_Engine();
        $this->_engine = new Quick_Injector_Engine();
    }

    public function testShouldCreateInstanceWithCallback( ) {
        $this->_cut->bindCallback('testclass', array($this, 'construct1234'), array(1,2,3,4));
        $ret = $this->_cut->createInstance('testclass');
        $this->assertEquals(1234, $ret);
    }

    public function testShouldCreateInstanceWithNew( ) {
        $obj = $this->_cut->createInstance('StdClass');
        $this->assertType('stdClass', $obj);
        $obj = $this->_cut->createInstance('Quick_Injector_EngineTest_TestClassNoConstructor');
        $this->assertType('Quick_Injector_EngineTest_TestClassNoConstructor', $obj);
    }

    public function testShouldCreateInstanceWithClassCreateMethod( ) {
        $obj = $this->_cut->createInstance('Quick_Injector_EngineTest_TestClassCreateMethod');
        $this->assertType('Quick_Injector_EngineTest_TestClassCreateMethod', $obj);
    }

    public function testShouldCreateInstanceWithConstructor( ) {
        $obj = $this->_cut->createInstance('Quick_Injector_EngineTest_TestClassSimpleConstructor');
        $this->assertType('Quick_Injector_EngineTest_TestClassSimpleConstructor', $obj);
    }

    public function testShouldCreateInstanceWithFactoryNotConstructor( ) {
        // if default factory create method exists, it should be used instead of object constructor
        $obj = $this->_cut->createInstance('Quick_Injector_EngineTest_TestClassFactoryConstructor');
        $this->assertFalse(is_object($obj));
        $this->assertEquals(1, $obj);
    }

    public function testShouldCreateInstanceWithFactoryMethod( ) {
        $this->_cut->bindCallback(
            'Quick_Injector_EngineTest_TestClassFactoryConstructor',
            array('Quick_Injector_EngineTest_TestClassFactoryConstructor_Factory', 'create2'),
            array(1,2)
        );
        $obj = $this->_cut->createInstance('Quick_Injector_EngineTest_TestClassFactoryConstructor');
        $this->assertEquals(2, $obj);
    }

    public function construct1234( $a, $b, $c, $d ) {
        return $a*1000 + $b*100 + $c*10 + $d;
    }

    /**
     * @expectedException       Quick_Injector_Exception
     */
    public function testShouldThrowExceptionIfCannotDetermineConstructorParams( ) {
        $this->_cut->createInstance('Quick_Injector_EngineTest_TestClassUnknownConstructorParams');
    }

    /**
     * @expectedException       Quick_Injector_Exception
     */
    public function testShouldThrowExceptionIfClassIsNotInstantiable( ) {
        $this->_cut->createInstance('Quick_Injector_EngineTest_TestClassNotInstantiable');
    }

    /**
     * @expectedException       Quick_Injector_Exception
     */
    public function testShouldThrowExceptionIfInterfaceIsNotInstantiable( ) {
        $this->_cut->createInstance('Quick_Injector_EngineTest_TestInterfaceNotInstantiable');
    }

    /**
     * @expectedException       Quick_Injector_Exception
     */
    public function testShouldThrowExceptionIfClassIsIndirectlyNotInstantiable( ) {
        $this->_cut->createInstance('Quick_Injector_EngineTest_TestClassIndirectlyNotInstantiable');
    }
}
