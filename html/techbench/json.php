<?php

if (!defined('QUICKLIB_DIR'))
    define('QUICKLIB_DIR', __DIR__ . "/../../lib");

function __autoload( $classname ) {
    require QUICKLIB_DIR . '/' . str_replace('_', '/', $classname) . ".php";
}

/*/*
// php raw: 38000/s (39000/s w/o autoload) (50% cpu, 1/8th total, for the test runner)
$rs = array('message' => "Hello, world.\n");
header('Content-Type: application/json; charset="UTF-8"');
echo json_encode($rs);
die();
/**/


// quick-raw: 19000/s (22000/s w/o Request object)
// $request = new Quick_Rest_Request_Http();
$response = new Quick_Rest_Response_Http();
$msg = json_encode(array('message' => "Hello, World!"));
$response->setContent($msg);
$response->setHttpHeader("Content-Type", "application/json; charset=\"UTF-8\"");
$response->emitResponse();
