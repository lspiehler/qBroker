<?php

require 'include/broker.php';

$broker = new Broker($config, $format);
$pathlength = count($request['path']);
if($pathlength >= 4) {
  $response = $broker->getMappings($request['path'][2], $request['path'][3]);
} else if($pathlength >= 3) {
  $response = $broker->getMappings($request['path'][2], FALSE);
} else {
  $response = $broker->getMappings(FALSE, FALSE);
}

// test exception
//throw new Exception('Division by zero.');

?>