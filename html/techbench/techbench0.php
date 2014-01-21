<?php

// $start_tm = microtime(true);

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
    define('QUICKLIB_DIR', dirname(realpath(__FILE__)) . "/../../lib");

// Note: apache is 20% slower if passed index.php/json, using index.php?op=json instead
$GLOBALS['_SERVER']['PATH_INFO'] = "/{$_GET['op']}";

if (1) {
    // this is the general-purpose, handles-all-cases autoloader (optimized for trees) (11700/s)
    if (!class_exists('Quick_Autoloader', false))
        require QUICKLIB_DIR . '/Quick/Autoloader.php';
    Quick_Autoloader::getInstance()
        ->addSearchTree(QUICKLIB_DIR, ".php")
        ->install();
}
elseif (1) {
    // 5% slower than __autoload, but 15% faster than the full-featured autoloader (13500/s)
    if (!class_exists('Quick_Autoloader_QuickLoader', false))
        require QUICKLIB_DIR . '/Quick/Autoloader/QuickLoader.php';
    $al = new Quick_Autoloader_QuickLoader(QUICKLIB_DIR);
    $al->register();
}
else {
    // __autoload is faster than require_once, and much faster than any multi-dir autoloader
    // using a primitive autoloader speeds up this test by 25% (14650/s; 2.5% for taskset 1)
    function __autoload( $classname ) {
        require QUICKLIB_DIR . '/' . str_replace('_', '/', $classname) . ".php";
    }
}

$request = new Quick_Rest_Request_Http();
$response = new Quick_Rest_Response_Http();
$app = new Quick_Rest_AppRunner();

$request->setParamsFromGlobals($GLOBALS);
if (PHP_SAPI === 'cli') $response->setCli(true);

$routes = array(
    //'GET::/json' => "jsonAction",             // function callbacks are no faster than app methods
    'GET::/json' => "testController::jsonAction",
    'GET::/db' => "testController::dbAction",
    'GET::/queries' => "testController::queriesAction",
    'GET::/updates' => "testController::updatesAction",
    'GET::/plaintext' => "testController::plaintextAction",
);
$app->setRoutes($routes);

function jsonAction( Quick_Rest_Request $request, Quick_Rest_Response $response, Quick_Rest_App $app ) {
    $msg = json_encode(array('message' => "Hello, World!"));
    $response->setContent($msg);
    $response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
}

// The app encapsulates state and makes it available to the calls.
class testApp
    implements Quick_Rest_App
{
    public function getInstance( $name ) {
        return null;
    }
}

// The controller(s) implement the calls.  All calls have the same signature.
class testController
    implements Quick_Rest_Controller
{
    public function jsonAction( Quick_Rest_Request $request, Quick_Rest_Response $response, Quick_Rest_App $app ) {
        $msg = json_encode(array('message' => "Hello, World!"));
        $response->setContent($msg);
        $response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
    }

    public function dbAction( Quick_Rest_Request $request, Quick_Rest_Response $response, Quick_Rest_App $app ) {
        // create table if not exists World (id int primary key auto_increment, randomNumber int) engine=MyISAM;
        $conn = new Quick_Db_Mysql_Connection(
            new Quick_Db_Credentials_Http("mysql://andras+@localhost:3306"),
            $adapter = new Quick_Db_Mysql_PersistentAdapter()
        );
        $adapter->setLink($link = $conn->createLink());
        $db = new Quick_Db_Mysql_Db($link, $adapter);
        $rs = $db->select("SELECT id, randomNumber FROM test.World WHERE id = ?", array(mt_rand(1, 10000)))->asHash()->fetch();
        $response->setContent(json_encode($rs));
        $response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
    }

    public function queriesAction( Quick_Rest_Request $request, Quick_Rest_Response $response, Quick_Rest_App $app ) {
        $queries = $request->getParam('queries');
        if ($queries < 1 || $queries > 500) $queries = 1;
        $conn = new Quick_Db_Mysql_Connection(
            new Quick_Db_Credentials_Http("mysql://andras+@localhost:3306"),
            $adapter = new Quick_Db_Mysql_PersistentAdapter()
        );
        $adapter->setLink($link = $conn->createLink());
        $db = new Quick_Db_Mysql_Db($link, $adapter);
        while (--$queries >= 0) {
            $ret[] = $db->select("SELECT id, randomNumber FROM test.World WHERE id = " . mt_rand(1, 10000))->asHash()->fetch();
        }
        $response->setContent(json_encode($ret));
        $response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
    }

    public function updatesAction( Quick_Rest_Request $request, Quick_Rest_Response $response, Quick_Rest_App $app ) {
        $queries = $request->getParam('queries');
        if ($queries < 1 || $queries > 500) $queries = 1;
        $conn = new Quick_Db_Mysql_Connection(
            new Quick_Db_Credentials_Http("mysql://andras+@localhost:3306"),
            $adapter = new Quick_Db_Mysql_PersistentAdapter()
        );
        $adapter->setLink($link = $conn->createLink());
        $db = new Quick_Db_Mysql_Db($link, $adapter);
        $template = new StdClass();
        while (--$queries >= 0) {
            $obj = $ret[] = $db->select("SELECT id, randomNumber FROM test.World WHERE id = " . mt_rand(1, 10000))
                ->asObject($template)->fetch();
            $obj->randomNumber = mt_rand(1, 10000);
        }

        $save = new Quick_Db_Sql_SaveMany($db, 'test.World', 'id');
        $save->saveMany($ret);

        $response->setContent(json_encode($ret));
        $response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
    }

    public function plaintextAction( Quick_Rest_Request $request, Quick_Rest_Response $response, Quick_Rest_App $app ) {
        $msg = "Hello, world.";
        $response->setContent($msg);
        $response->setHttpHeader("Content-Type", "text/plain; charset=\"UTF-8\"");
    }
}
$app->runCall($request, $response);
$response->emitResponse();

// echo "duration = ", microtime(true) - $start_tm, "\n";
