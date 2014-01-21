<?php

if (!defined('QUICKLIB_DIR'))
    define('QUICKLIB_DIR', __DIR__ . "/../../lib");

function __autoload( $classname ) {
    require QUICKLIB_DIR . '/' . str_replace('_', '/', $classname) . ".php";
}

/*/*
// wrk -t 1 -c 11 -d 2s 'http://localhost:80/pp/aradics/techbench/db.php'
// 10400/s (raw php mysql 18100/s)
$link = mysql_pconnect("localhost", "andras", null);
$db = new Quick_Db_Mysql_Db($link, new Quick_Db_Mysql_Adapter($link));
$rs = $db->select("SELECT id, randomNumber FROM test.World WHERE id = " . mt_rand(1, 10000))->asHash()->fetch();
header('Content-Type: application/json; charset="UTF-8"');
echo json_encode($rs);
die();
/**/

// 9200/s
$response = new Quick_Rest_Response_Http();
$link = mysql_pconnect("localhost", "andras", null);
$db = new Quick_Db_Mysql_Db($link, new Quick_Db_Mysql_Adapter($link));
$rs = $db->select("SELECT id, randomNumber FROM test.World WHERE id = " . mt_rand(1, 10000))->asHash()->fetch();
$response->setContent(json_encode($rs));
$response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
$response->emitResponse();
die();
