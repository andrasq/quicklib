<?php

/**
 * Basic autoloader for flat directories and directory trees.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-02-09 - AR.
 */

if (!class_exists('Quick_Autoloader_Engine', false))
    require dirname(__FILE__) . '/Autoloader/Engine.php';

class Quick_Autoloader
{
    protected $_engine;

    public function __construct( Quick_Autoloader_Engine $engine = null ) {
        if ($engine === null) $engine = Quick_Autoloader_Engine::getInstance();
        $this->_engine = $engine;
    }

    public function getEngine( ) {
        return $this->_engine;
    }

    static public function getInstance( $engine = null ) {
        static $instance;
        return $instance ? $instance : $instance = new Quick_Autoloader($engine);
    }

    // add an explicit classname => sourcefile mapping
    public function addClass( $classname, $sourcefile ) {
        $this->_engine->addClass($classname, $sourcefile);
        return $this;
    }

    // register a directory that contains multiple source files alongside to each other
    public function addSearchPath( $dirname, $extensions = ".class.php,.interface.php" ) {
        $this->_engine->addSearchPath($dirname, $extensions);
        return $this;
    }

    // register a directory tree containing class definition files in the canonical hierarchical layout
    public function addSearchTree( $dirname, $extensions = ".php" ) {
        $this->_engine->addSearchTree($dirname, $extensions);
        return $this;
    }

    // register a callback function that builds possible class pathnames from the class name
    public function addCallback( $dirname, $callback, $extensions = ".php" ) {
        $this->_engine->addCallback($dirname, $callback, $extensions);
        return $this;
    }

    public function install( ) {
        return $this->register();
    }

    public function register( ) {
        $this->_engine->register();
        return $this;
    }

    public function unregister( ) {
        $this->_engine->unregister();
        return $this;
    }

    public function autoload( $classname ) {
        if (!class_exists($classname, false) && !interface_exists($classname, false))
            $this->_engine->autoload($classname);
        return $this;
    }
}
