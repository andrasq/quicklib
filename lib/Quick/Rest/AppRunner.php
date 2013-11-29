<?

/**
 * REST application runner.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_AppRunner
    implements Quick_Rest_App
{
    protected $_callbacks = array();
    protected $_config = array();
    protected $_instances = array();
    protected $_instanceBuilders = array();

    public function setConfig( $name, $value ) {
        $this->_config[$name] = $value;
    }

    public function getConfig( $name ) {
        return isset($this->_config[$name]) ? $this->_config[$name] : null;
    }

    public function setInstance( $name, $instance ) {
        $this->_instances[$name] = $instance;
    }

    public function setInstanceBuilder( $name, $builder ) {
        if (!is_callable($builder))
            throw new Quick_Rest_Exception("$name: builder is not callable");
        $this->_instanceBuilders[$name] = $builder;
    }

    public function getInstance( $name ) {
        $item = $this->peekInstance($name);
        if (isset($item)) return $item;
        throw new Quick_Rest_Exception("$name: instance not defined");
    }

    public function peekInstance( $name ) {
        if (isset($this->_instances[$name]))
            return $this->_instances[$name];
        if (isset($this->_instanceBuilders[$name]))
            return $this->_instances[$name] = call_user_func($this->_instanceBuilders[$name]);
        return null;
    }

    public function setRoutes( Array & $routes ) {
        // array of route mappings, eg. ('GET::call/name' => 'class::callback')
        $this->_callbacks = $routes;
        return $this;
    }

    public function validateRoutes( Array & $routes ) {
        foreach ($routes as $path => $callback)
            if (!is_callable($callback, true))
                throw new Quick_Rest_Exception("route for $path not callable: " . print_r($callback, true));
        return true;
    }

    public function setRoute( $route, $callback ) {
        if (is_callable($callback, true))
            $this->_callbacks[$route] = $callback;
        else
            throw new Quick_Rest_Exception("route $route callback $callback not callable");
    }

    public function routeCall( $methods, $path, $callback ) {
        if (!is_callable($callback, true))
            throw new Quick_Rest_Exception("route for $methods::$path not callable: " . print_r($callback, true));
        foreach (explode('|', $methods) as $method) {
            $method = strtoupper($method);
            $this->_callbacks["$method::$path"] = $callback;
        }
        return $this;
    }

    public function runCall( Quick_Rest_Request $request, Quick_Rest_Response $response ) {
        try {
            $path = strtoupper($request->getMethod()) . '::' . $request->getPath();
            if (!isset($this->_callbacks[$path]))
                throw new Quick_Rest_Exception("call not routed to handler: $path");

            $this->_runCallback($this->_callbacks[$path], $request, $response, $app = $this);
            return $response;
        }
        catch (Exception $e) {
            // in case of exception route the call to the user error handler, or die
            if (isset($this->_callbacks['ERROR::/exception'])) {
                try {
                    $request->__exception = $e;
                    $this->_runCallback($this->_callbacks['ERROR::/exception'], $request, $response, $app = $this);
                    return $response;
                }
                catch (Exception $e2) {
                    // if the user error handles itself broke, die with the original error
                    throw $e;
                }
            }
            throw $e;
        }
    }


    protected function _runCallback( $callback, $request, $response, Quick_Rest_App $app ) {
        if (is_string($callback)) {
            if (strpos($callback, '::') !== false) {
                list($class, $method) = explode('::', $callback);
                $handler = new $class();
                if (! $handler instanceof Quick_Rest_Controller)
                    throw new Quick_Rest_Exception("$class: app callback not a Quick_Rest_Controller");
                $handler->$method($request, $response, $app);
            }
            else {
                $callback($request, $response, $app);
            }
            return;
        }
        if (is_array($callback)) {
            call_user_func($callback, $request, $response, $app);
            return;
        }
        throw new Quick_Rest_Exception("unsupported callback " . print_r($callback, true));
    }
}