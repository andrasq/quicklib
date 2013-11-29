<?

/**
 * Object that conveys REST request params to the call handler.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Rest_Request {
    public function getMethod();
    public function getPath();
    public function getParam($name);
    public function requireParam($name);
    public function setParam($name, $value);
    public function getParams(Array  $names = array(), Array & $missing = null);
    public function checkRequiredParams(Array $required);
    public function getUnknownParams(Array $required, Array $optional);
    public function getRequestQueryString();
    public function getCombinedQueryString();
}
