<?php

require 'include/broker.php';

$broker = new Broker($config, $format);
$response = $broker->getActiveServers();

?>