<?

/**
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Autoloader_QuickLoaderTest
    extends Quick_Test_Case
{
    public function setUp( ) {
        $this->_cut = new Quick_Autoloader_QuickLoader("/tmp");
    }

    public function testMapShouldPrependDirAndAppendPhp( ) {
        $classpath = $this->_cut->map("Foo_Class");
        $this->assertEquals("/tmp/Foo/Class.php", $classpath);
    }

    public function testAutoloadShouldLoadTheClass( ) {
        $cut = new Quick_Autoloader_QuickLoader(dirname(__FILE__));
        $this->assertFalse(class_exists('Quick_Loader_TestClass', false));
        $cut->autoload('Quick_Loader_TestClass');
        $this->assertTrue(class_exists('Quick_Loader_TestClass', false));
    }

    public function testBootstrapShouldReturnQuickAutoloader( ) {
// FIXME: does not fully un-install itself? interferes with other tests
return;
        $loader = $this->_cut->bootstrap();
        $this->assertType('Quick_Autoloader', $loader);
        $loader->unregister();
    }

    public function testBootstrapAutoloaderShouldLoadQuickLoaderClasses( ) {
// FIXME: does not fully un-install itself? interferes with other tests
return;
        $cut = new Quick_Autoloader_QuickLoader(dirname(__FILE__));
        $loader = $cut->bootstrap();
        $this->assertFalse(class_exists('Quick_Loader_TestClass2'));
        $loader->autoload('Quick_Loader_TestClass2');
        $this->assertTrue(class_exists('Quick_Loader_TestClass2'));
        $loader->unregister();
    }
}
