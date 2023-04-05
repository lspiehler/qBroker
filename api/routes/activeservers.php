<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//$config = include('config.php');
require 'include/broker.php';
// set expires header
header('Expires: Thu, 1 Jan 1970 00:00:00 GMT');

// set cache-control header
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);

// set pragma header
header('Pragma: no-cache');

$format = 'json';
if(array_key_exists('format', $request['query'])) {
    $format = $request['query']['format'];
}

$broker = new Broker($format);
$response = $broker->getActiveServers();
foreach($response['headers'] as $key => $value) {
  header($key . ": " . $value);
}
http_response_code($response['status']);
//http_response_code(400);
echo $response['body'];

?>