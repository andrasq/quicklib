<?php

/*
 * Toolkit benchmark in the form of TechEmpower, run on localhost.
 * http://www.techempower.com/benchmarks
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * This version is 13.9 k calls/sec, using QuickLoader
 * (4-core AMD Phenom II 3.6 GHz, 32-bit ubuntu 13.04, Apache 2.2.22)

% time wrk -t 1 -c 11 -d 2s 'http://localhost/pp/aradics/techbench1.php?op=json'
Running 2s test @ http://localhost/pp/aradics/techbench1.php?op=json
  1 threads and 11 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   785.74us    2.01ms  22.99ms   94.56%
    Req/Sec    14.78k     0.93k   16.22k    61.07%
  27908 requests in 2.00s, 6.29MB read
Requests/sec:  13959.39
Transfer/sec:      3.14MB
0.140u 0.292s 0:02.00 21.5%	0+0k 0+0io 0pf+0w

 */

if (!defined('QUICKLIB_DIR'))
    define('QUICKLIB_DIR', dirname(realpath(__FILE__)) . "/../../lib");

if (!class_exists('Quick_Autoloader_QuickLoader'))
    require QUICKLIB_DIR . '/Quick/Autoloader/QuickLoader.php';

// Note: apache is 20% slower if passed index.php/json, using index.php?op=json instead
$GLOBALS['_SERVER']['PATH_INFO'] = "/{$_GET['op']}";

$loader = new Quick_Autoloader_QuickLoader(QUICKLIB_DIR);
$loader->register();

$request = new Quick_Rest_Request_Http();
$response = new Quick_Rest_Response_Http();
$appRunner = new Quick_Rest_AppRunner();

$request->setParamsFromGlobals($GLOBALS);
if (PHP_SAPI === 'cli') $response->setCli(true);

$routes = array(
    'GET::/json' => "testApp::jsonAction",
    'GET::/db' => "testApp::dbAction",
    'GET::/plaintext' => "testApp::plaintextAction",
);
$appRunner->setRoutes($routes);

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

$appRunner->runCall($request, $response);
$response->emitResponse();
