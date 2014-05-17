<?

/**
 * REST application runner.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

class Quick_Rest_AppRunner
    extends Quick_Rest_AppBase
    implements Quick_Rest_App
{
    protected $_callbacks = array();
    protected $_call;

    public function setRoutes( Array & $routes ) {
        // array of route mappings, eg. ('GET::call/name' => 'class::callback')
        $this->_callbacks = $routes;
        return $this;
    }

    // execute the call(s) as if matching this route
    public function setCall( $call ) {
        $this->_call = $call;
    }

    public function routeCall( $methods, $path, $callback ) {
        if (!is_callable($callback, true))
            throw new Quick_Rest_Exception("route for $methods::$path not callable: ``" . print_r($callback, true) . "''");
        foreach (explode('|', $methods) as $method) {
            $method = strtoupper($method);
            $this->_callbacks["$method::$path"] = $callback;
        }
        return $this;
    }

    public function runCall( Quick_Rest_Request $request, Quick_Rest_Response $response, Quick_Rest_App $app = null ) {
        if ($app === null) $app = $this;
        try {
            // note: method and path must match exactly, they are both case sensitive
            $path = isset($this->_call) ? $this->_call : "{$request->getMethod()}::{$request->getPath()}";
            if (isset($this->_callbacks[$path]))
                $callback = $this->_callbacks[$path];
            elseif (isset($this->_callbacks[$path = "ANY::{$request->getPath()}"]))
                $callback = $this->_callbacks[$path];
            else
                throw new Quick_Rest_Exception("call not routed {$request->getMethod()}::{$request->getPath()}");

            if (is_string($callback) && strpos($callback, '::') !== false) {
                // 5% faster app if we inline this most common use case
                list($class, $method) = explode('::', $callback);
                $handler = new $class();
                if (! $handler instanceof Quick_Rest_Controller)
                    throw new Quick_Rest_Exception("$class: app callback not a Quick_Rest_Controller");
                $handler->$method($request, $response, $app);
                return $response;
            }
            else {
                // generic runner
                $this->_runCallback($callback, $request, $response, $app);
                return $response;
            }
        }
       catch (Exception $e) {
            // in case of exception route the call to the user error handler, or die
            if (isset($this->_callbacks['ERROR::/exception'])) {
                try {
                    $request->__exception = $e;
                    $this->_runCallback($this->_callbacks['ERROR::/exception'], $request, $response, $app);
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


    protected function _runCallback( $callback, $request, $response, $app ) {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            // :: notation is used for 'class::method', we instantiate the class
            list($class, $method) = explode('::', $callback);
            $handler = new $class();
            if (! $handler instanceof Quick_Rest_Controller)
                throw new Quick_Rest_Exception("$class: app callback not a Quick_Rest_Controller");
            $handler->$method($request, $response, $app);
            return;
        }
        elseif (is_array($callback)) {
            // array callbacks can be ($obj, 'method') or ("class", 'method')
            list($object, $method) = $callback;
            if (is_string($object)) $object = new $object();
            $object->$method($request, $response, $app);
            return;
        }
        elseif (is_callable($callback)) {
            // others we call directly, eg function names or anonymous functions
            $callback($request, $response, $app);
            return;
        }
        throw new Quick_Rest_Exception("unsupported callback " . print_r($callback, true));
    }
}
