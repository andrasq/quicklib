<?php

/**
 * Primitive little dependency injector engine.
 * Can create instances by looking for a factory or
 * analyzing the constructor signature.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * 2013-03-04 - AR.
 */

class Quick_Injector_Engine
{
    protected $_callbacks = array();
    protected $_builders = array();

    public function createInstance( $classname ) {
        if (isset($this->_callbacks[$classname])) {
            // build with user-bound callback function
            list($callback, $arglist) = $this->_callbacks[$classname]['args'];
            // even though array($string1, $string2) is a valid callback, always create a factory object
            if (is_array($callback) && is_string($callback[0])) $callback[0] = $this->createInstance($callback[0]);
            return $this->_buildWithCallback($callback, $arglist);
        }
        else {
            // build with internally cached build function
            if (empty($this->_builders[$classname])) $this->_findBuilder($classname);
            $builder = $this->_builders[$classname];
            return $this->_buildWithCallback(array($this, $builder['method']), $builder['args']);
        }
    }

    public function bindCallback( $classname, $callback, Array $arglist ) {
        $this->_callbacks[$classname] = array('method' => '_buildWithCallback', 'args' => array($callback, $arglist));
    }


    protected function _bindBuilder( $classname, $method, Array $arglist ) {
        $this->_builders[$classname] = array('method' => $method, 'args' => $arglist);
    }

    protected function _findBuilder( $classname ) {
        $reflection = new ReflectionClass($classname);
        if (!$reflection->isInstantiable()) {
            // note: isInstantiable is slow, it recurisively checks the constructor dependencies for instantiability
            $this->_throwException("$classname: is not instantiable");
        }
        if ((class_exists($factory = "{$classname}_Factory") || class_exists($factory = "{classname}Factory")) &&
            is_callable($factory, "create"))
        {
            $factoryReflection = new ReflectionClass($factory);
            $factory = $this->createInstance($factoryReflection->name);
            $args = $this->_constructParametersForMethod($factoryReflection->getMethod('create'));
            $this->_bindBuilder($classname, '_buildWithCallback', array(array($factory, 'create'), $args));
        }
        else {
            try { $createMethod = $reflection->getMethod("create"); }
            catch (Exception $e) { $constructor = $reflection->getConstructor(); }
            if (!empty($createMethod) && $createMethod->isStatic() && $createMethod->getNumberOfRequiredParameters() === 0) {
                $this->_bindBuilder($classname, '_buildWithCallback', array(array($classname, 'create'), array()));
            }
            elseif (!empty($constructor)) {
                $args = $this->_constructParametersForMethod($constructor);
                $this->_bindBuilder($classname, '_buildWithReflection', array($reflection, $args));
            }
            else {
                $this->_bindBuilder($classname, '_buildWithNew', array($classname));
            }
        }

        // to keep the list of builders manageable, slice off the head of the list when it grows too large
        if (count($this->_builders) >= 500) array_splice($this->_builders, 0, 300);
    }

    protected function _constructParametersForMethod( ReflectionMethod $method ) {
        $args = array();
        foreach ($method->getParameters() as $param) {
            if ($param->isOptional())
                // only trailing parameters are optional, and those can be omitted
                break;
            elseif ($class = $param->getClass())
                $args[] = $this->createInstance($class->name);
            else
                $this->_throwException("$method->class::$method->name: unable to determine type of constructor arg $param->name");
        }
        return $args;
    }

    protected function _buildWithCallback( $callback, Array $arglist ) {
        if (is_object($callback[0])) {
            list($factory, $method) = $callback;
            if (!$arglist) {
                return $factory->$method();
            }
            elseif (count($arglist) == 1) {
                return $factory->$method($arglist[0]);
            }
            elseif (count($arglist) == 2) {
                return $factory->$method($arglist[0], $arglist[1]);
            }
        }

        // otherwise invoke the callback as-is, without optimizing the call
        return call_user_func_array($callback, $arglist);
    }

    protected function _buildWithNew( $classname ) {
        return new $classname();
    }

    protected function _buildWithReflection( $reflection, $args ) {
        return $reflection->newInstanceArgs($args);
    }

    protected function _throwException( $msg ) {
        throw new Quick_Injector_Exception($msg);
    }
}
