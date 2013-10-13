<?

/**
 * Simplistic little dependency injector.
 * The Engine reflects the class to find out how to build the object,
 * the wrapper handles get/setInstance of globally shared instances.
 *
 * Unlike other dependency injectors, shared instances must be set explicitly.
 * GetInstance() must have an instance specified with setInstance().
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-03-04 - AR.
 */

class Quick_Injector
{
    protected $_instances = array();

    public function __construct( Quick_Injector_Engine $engine = null ) {
        if ($engine === null) $engine = new Quick_Injector_Engine();
        $this->_engine = $engine;
    }

    public function createInstance( $classname ) {
        $this->_engine->createInstance($classname);
    }

    public function setInstance( $classname, $instance ) {
        // setInstance assigns an arbitrary value to name, so no type checking
        // if (!is_object($instance)) $this->_throwException("$classname: set instance not an object");
        $this->_instances[$classname] = $instance;
    }

    public function getInstance( $classname ) {
        // there is no globally shared instance by default, must set explicitly
        if (!isset($this->_instances[$classname])) $this->_throwException("$classname: get instance not defined");
        return $this->_instances[$classname];
    }

    public function bindCallback( $classname, $callback, Array $arglist ) {
        $this->_engine->bindCallback($classname, $callback, $arglist);
    }

    public function bindFactoryMethod( $classname, $factoryname, $method = 'create', Array $args = array() ) {
        $this->_engine->bindCallback($classname, array($factoryname, $method), $args);
    }

    protected function _throwException( $msg ) {
        throw new Quick_Injector_Exception($msg);
    }
}
