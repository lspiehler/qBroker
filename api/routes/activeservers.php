<?php

//$config = include('../config.php');
require 'include/broker.php';
// set expires header
header('Expires: Thu, 1 Jan 1970 00:00:00 GMT');

// set cache-control header
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);

// set pragma header
header('Pragma: no-cache');

$format = 'json';
$headers = getallheaders();
if(array_key_exists('accept', $headers)) {
  if(strpos($headers['accept'], "application/xml") !== FALSE) {
    $format = "xml";
  }
}
if(array_key_exists('Accept', $headers)) {
  if(strpos($headers['Accept'], "application/xml") !== FALSE) {
    $format = "xml";
  }
}

$broker = new Broker($format);
$response = $broker->getActiveServers();
//print_r($response);
foreach($response['headers'] as $key => $value) {
  header($key . ": " . $value);
}
http_response_code($response['status']);
//http_response_code(400);
echo $response['body'];

?>