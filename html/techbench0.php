<?php

/*
 * Toolkit benchmark in the form of TechEmpower, run on localhost.
 * http://www.techempower.com/benchmarks
 *
 * Copyright (C) 2013 Andras Radics
 * Licensed under the Apache License, Version 2.0
 *
 * This is the full-weight version 0, 11.5 k calls/sec
 * (4-core AMD Phenom II 3.6 GHz, 32-bit ubuntu 13.04, Apache 2.2.22)

% time wrk -t 1 -c 11 -d 20s 'http://localhost/pp/aradics/techbench0.php?op=json'
Running 20s test @ http://localhost/pp/aradics/techbench0.php?op=json
  1 threads and 11 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     1.37ms    6.57ms  85.18ms   97.11%
    Req/Sec    12.22k     1.25k   13.78k    94.61%
  231456 requests in 20.00s, 52.13MB read
Requests/sec:  11573.28
Transfer/sec:      2.61MB
0.912u 2.764s 0:20.00 18.3%	0+0k 0+0io 0pf+0w

 */

if (!defined('QUICKLIB_DIR'))
    define('QUICKLIB_DIR', dirname(realpath(__FILE__)) . "/../lib");

// Note: apache is 20% slower if passed index.php/json, using index.php?op=json instead
$GLOBALS['_SERVER']['PATH_INFO'] = "/{$_GET['op']}";

if (!class_exists('Quick_Autoloader', false))
    require QUICKLIB_DIR . '/Quick/Autoloader.php';
Quick_Autoloader::getInstance()
    ->addSearchTree(QUICKLIB_DIR, ".php")
    ->install();

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
