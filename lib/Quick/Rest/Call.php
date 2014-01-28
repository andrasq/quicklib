<?

/**
 * REST api call.
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 */

interface Quick_Rest_Call {
    public function setProfiling(Quick_Data_Datalogger $profiler = null);
    public function setMethod($method /*, $methodArg */);
    public function setHeader($name, $value);
    public function setParam($name, $value);
    public function setUrl($url);
    public function call();
    public function getReply();
    public function getReplyHeader();
    public function getContentOffset();
    public function getContentLength();
    public function getContentFile($filename);
}
