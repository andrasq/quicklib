<?php

$start_tm = microtime(true);

error_reporting(E_ALL);
ini_set('precision', 16);
ini_set('display_errors', 1);

$queueConfig = array(
    'queuedir' => "/var/run/queue",
    'jobsdir' => "/var/run/queue/jobs",
    'jobs' => array(
        '__default' => array(
            'runner' => "http://localhost:80/pp/aradics/echo.php?op=echo&jobtype={JOBTYPE}&data={DATA}",
            'batchsize' => 1,
            'batchlimit' => 1,
        ),
    ),
);

function __autoload( $classname ) {
    $classpath = str_replace('_', '/', $classname);
    require dirname(__FILE__) . "/../lib/$classpath.php";
}

$request = new Quick_Rest_Request_Http();
$response = new Quick_Rest_Response_Http();
$app = new Quick_Queue_App($queueConfig);

$request->setParamsFromGlobals($GLOBALS);

switch ($request->getParam('op')) {
case 'add':
    $app->addAction($request, $response, $app);
    break;
case 'run':
    $app->runAction($request, $response, $app);
    break;
case 'status':
    $app->statusAction($request, $response, $app);
    break;
default:
    $response->setStatus(404, "Not Found");
    $response->setContent($request->getParam('op') . ": unknown queue operation, try add|run|status\n");
    break;
}

$response->appendContent("duration = " . (microtime(true) - $start_tm) . "\n");
$response->emitResponse();
