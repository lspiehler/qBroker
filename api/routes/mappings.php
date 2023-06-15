<?php

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

try {
  //$config = include('../config.php');
  $json = file_get_contents('../config.js');
  //echo $json;
  $config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

  //print_r($config);

  require 'include/broker.php';
  // set expires header
  header('Expires: Thu, 1 Jan 1970 00:00:00 GMT');

  // set cache-control header
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Cache-Control: post-check=0, pre-check=0',false);

  // set pragma header
  header('Pragma: no-cache');

  //print_r($request);

  $broker = new Broker($config, $format);
  $response = $broker->getMappings($request['path'][2], $request['path'][3]);

  // test exception
  //throw new Exception('Division by zero.');
} catch(Exception $e) {
  require_once('./include/httpresponse.php');
  $hrc = new HTTPResponse();
  $response = $hrc->getHTTPResponse();
  $response['body']['result'] = 'error';
  $response['body']['message'] = $e->getMessage(). " in " . $e->getFile() . " on line " . $e->getLine();
  $response['status'] = 503;
  $response = $hrc->formatHttpResponse($response, $format);
}

foreach($response['headers'] as $key => $value) {
  header($key . ": " . $value);
}
http_response_code($response['status']);
//http_response_code(400);
echo $response['body'];

?>