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
    define('QUICKLIB_DIR', __DIR__ . "/../../lib");

function __autoload( $classname ) {
    require QUICKLIB_DIR . '/' . str_replace('_', '/', $classname) . ".php";
}

$request = new Quick_Rest_Request_Http();
$response = new Quick_Rest_Response_Http();

//$request->setParamsFromGlobals($GLOBALS);
//if (PHP_SAPI === 'cli') $response->setCli(true);

//switch ($request->getParam('op'))
switch ($GLOBALS['_GET']['op'])
{
    case 'json':
        $msg = json_encode(array('message' => "Hello, World!"));
        $response->setContent($msg);
        $response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
        break;

    case 'db':
        // mysql: 8200/sec, mysqli: 7500/sec
        //$link = mysqli_connect("p:localhost", "andras", null);
        //$db = new Quick_Db_Mysqli_Db($link, new Quick_Db_Mysqli_Adapter($link));
        $link = mysql_pconnect("localhost", "andras", null);
        $db = new Quick_Db_Mysql_Db($link, new Quick_Db_Mysql_Adapter($link));
        $rs = $db->select("SELECT id, randomNumber FROM test.World WHERE id = " . mt_rand(1, 10000))->asHash();
        $response->setContent(json_encode($rs->fetch()));
        $response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
        // Quick_Db without controller: 9200/s
        // raw mysql: 10000/s
        // raw mysql w/o controller: 18100/s
        break;

    case 'queries':
        $request->setParamsFromGlobals($GLOBALS);
        $queries = $request->getParam('queries');
        if ($queries < 1 || $queries > 500) $queries = 1;
        $link = mysql_pconnect("localhost", "andras", null);
        $db = new Quick_Db_Mysql_Db($link, new Quick_Db_Mysql_Adapter($link));
        // $ret = array_map('fetch_random_row', array_fill(0, $queries, $db));
        while (--$queries >= 0) {
            $ret[] = $db->select("SELECT id, randomNumber FROM test.World WHERE id = " . mt_rand(1, 10000))->asHash()->fetch();
            // Quick_Db: 8200/sec
            // $rs = mysql_query("SELECT id, randomNumber FROM test.World WHERE id = " . mt_rand(1, 10000), $link);
            // $ret[] = mysql_fetch_assoc($rs);
            // raw mysql: 10000/sec (22% faster)
        }
        $response->setContent(json_encode($ret));
        $response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
        break;

    case 'updates':
        $request->setParamsFromGlobals($GLOBALS);
        $queries = $request->getParam('queries');
        if ($queries < 1 || $queries > 500) $queries = 1;
        $link = mysql_pconnect("localhost", "andras", null);
        $db = new Quick_Db_Mysql_Db($link, new Quick_Db_Mysql_Adapter($link));
        $template = new StdClass();
        while (--$queries >= 0) {
            $obj = $db->select("SELECT id, randomNumber FROM test.World WHERE id = " . mt_rand(1, 10000))
                ->asObject($template)->fetch();
            $ret[$obj->id] = $obj;
            $obj->randomNumber = mt_rand(1, 10000);
        }
        // nb: order the rows to avoid innodb deadlocks
        // also nb: in most cases myisam has higher throughput than innodb
        ksort($ret);
        foreach ($ret as $obj) $namevals[] = "($obj->id, $obj->randomNumber)";
        $dataSql = implode(", ", $namevals);
        $insertSql =
            "INSERT INTO test.World (id, randomNumber) VALUES $dataSql
             ON DUPLICATE KEY UPDATE randomNumber = VALUES(randomNumber)";
        $db->execute($insertSql);
/**
        foreach ($ret as $obj) $namevals[] = "$obj->id AS id, $obj->randomNumber AS randomNumber";
        $dataSql = "SELECT " . implode(" UNION ALL SELECT ", $namevals);
        $updateSql = "UPDATE test.World w JOIN ($dataSql) t USING(id) SET w.randomNumber = t.randomNumber";
        $db->execute($updateSql);
**/
        // NOTE: MyISAM is *much* quicker near-idle! but at high loads InnoDB has an edge
        // -t1 -c4: 1200/s My, 210/s In; -t1 -c1: 460/s My, 110/s In
        // -t3 -c66 -d10s: 850/s MyISAM, 970/s InnoDB
        // -t4 -c256 -d10s: 790/s MyISAM, 850/s InnoDB
        $response->setContent(json_encode($ret));
        $response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
        break;

    case 'plaintext':
        $msg = "Hello, world.";
        $response->setContent($msg);
        $response->setHttpHeader("Content-Type", "text/plain; charset=\"UTF-8\"");
        break;
}

function fetch_random_row( $db ) {
    return $db->select("SELECT id, randomNumber FROM test.World WHERE id = " . mt_rand(1, 10000))->asHash()->fetch();
}

$response->emitResponse();
