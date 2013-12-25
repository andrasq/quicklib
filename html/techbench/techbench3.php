<?php

/*
 * Toolkit benchmark in the form of TechEmpower, run on localhost.
 * http://www.techempower.com/benchmarks
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * This is the fast version, 18.7 k calls/sec, stripped down minimum
 * with just __autoload, request, response, and the app a switch statement.
 * (4-core AMD Phenom II 3.6 GHz, 32-bit ubuntu 13.04, Apache 2.2.22)

% time wrk -t 1 -c 11 -d 20s 'http://localhost/pp/aradics/techbench3.php?op=json'
Running 20s test @ http://localhost/pp/aradics/techbench3.php?op=json
  1 threads and 11 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   691.19us    3.02ms  57.48ms   96.08%
    Req/Sec    19.65k     1.65k   22.11k    78.35%
  371915 requests in 20.00s, 83.77MB read
Requests/sec:  18596.53
Transfer/sec:      4.19MB
1.248u 4.052s 0:20.00 26.4%	0+0k 0+0io 0pf+0w

 */

if (!defined('QUICKLIB_DIR'))
    define('QUICKLIB_DIR', dirname(realpath(__FILE__)) . "/../../lib");

function __autoload( $classname ) {
    require QUICKLIB_DIR . '/' . str_replace('_', '/', $classname) . ".php";
}

$request = new Quick_Rest_Request_Http();
$response = new Quick_Rest_Response_Http();

$request->setParamsFromGlobals($GLOBALS);
//if (PHP_SAPI === 'cli') $response->setCli(true);

switch ($request->getParam('op')) {
case 'json':
    $msg = json_encode(array('message' => "Hello, World!"));
    $response->setContent($msg);
    $response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
    break;
case 'db':
    break;
case 'plaintext':
    $msg = "Hello, world.";
    $response->setContent($msg);
    $response->setHttpHeader("Content-Type", "text/plain; charset=\"UTF-8\"");
    break;
}

$response->emitResponse();
