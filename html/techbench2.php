<?php

/*
 * Toolkit benchmark in the form of TechEmpower, run on localhost.
 * http://www.techempower.com/benchmarks
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * This version is 15.0 k calls/sec, using __autoload
 * (4-core AMD Phenom II 3.6 GHz, 32-bit ubuntu 13.04, Apache 2.2.22)

% time wrk -t 1 -c 11 -d 2s 'http://localhost/pp/aradics/techbench2.php?op=json'
Running 2s test @ http://localhost/pp/aradics/techbench2.php?op=json
  1 threads and 11 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   705.85us    1.78ms  27.70ms   94.41%
    Req/Sec    16.11k     1.03k   18.00k    69.13%
  30501 requests in 2.00s, 6.87MB read
Requests/sec:  15251.79
Transfer/sec:      3.44MB
0.144u 0.316s 0:02.00 22.5%	0+0k 0+0io 0pf+0w

 */

if (!defined('QUICKLIB_DIR'))
    define('QUICKLIB_DIR', dirname(realpath(__FILE__)) . "/../lib");

function __autoload( $classname ) {
    require QUICKLIB_DIR . '/' . str_replace('_', '/', $classname) . ".php";
}

// Note: apache is 20% slower if passed index.php/json, using index.php?op=json instead
$GLOBALS['_SERVER']['PATH_INFO'] = "/{$_GET['op']}";

$request = new Quick_Rest_Request_Http();
$response = new Quick_Rest_Response_Http();
$app = new Quick_Rest_AppRunner();

$request->setParamsFromGlobals($GLOBALS);
if (PHP_SAPI === 'cli') $response->setCli(true);

$routes = array(
    'GET::/json' => "testApp::jsonAction",
    'GET::/db' => "testApp::dbAction",
    'GET::/plaintext' => "testApp::plaintextAction",
);
$app->setRoutes($routes);

class testApp
    implements Quick_Rest_Controller
{
    public function getInstance( ) {
    }

    public function jsonAction( Quick_Rest_Request $request, Quick_Rest_Response $response, Quick_Rest_App $app ) {
        $msg = json_encode(array('message' => "Hello, World!"));
        $response->setContent($msg);
        $response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
    }

    public function dbAction( Quick_Rest_Request $request, Quick_Rest_Response $response, Quick_Rest_App $app ) {
    }

    public function plaintextAction( Quick_Rest_Request $request, Quick_Rest_Response $response, Quick_Rest_App $app ) {
        $msg = "Hello, world.";
        $response->setContent($msg);
        $response->setHttpHeader("Content-Type", "text/plain; charset=\"UTF-8\"");
    }
}

$app->runCall($request, $response);
$response->emitResponse();
