<?

/**
 * REST application.
 * Every controller is guaranteed to have access to getInstance().
 *
 * Copyright (C) 2014 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_AppBase
    implements Quick_Rest_App
{
    protected $_instances = array();
    protected $_instanceBuilders = array();

    public function setInstance( $name, $instance ) {
        $this->_instances[$name] = $instance;
    }

    public function setInstanceBuilder( $name, $builder ) {
        if (!is_callable($builder))
            throw new Quick_Rest_Exception("$name: builder is not callable");
        $this->_instanceBuilders[$name] = $builder;
    }

    public function getInstance( $name ) {
        if (isset($this->_instances[$name]))
            return $this->_instances[$name];
        elseif (isset($this->_instanceBuilders[$name]))
            return $this->_instances[$name] = call_user_func($this->_instanceBuilders[$name]);
        else
            throw new Quick_Rest_Exception("$name: instance not defined");
    }

    public function peekInstance( $name ) {
        try {
            return $this->getInstance($name);
        }
        catch (Quick_Rest_Exception $e) {
            return null;
        }
    }
}
