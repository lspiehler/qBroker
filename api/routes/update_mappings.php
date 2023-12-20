<?php

require __DIR__.'/../include/balancer.php';
$balancer = new Balancer($config);
$servers = $balancer->getServers();
$lbserver = $balancer->getBrokeredServer();
$server = $lbserver["server"];

$json = file_get_contents('php://input');
$values = json_decode($json, true);

require_once('./include/httpresponse.php');
$hrc = new HTTPResponse();
$response = $hrc->getHTTPResponse();

$mappingfile = array();

for($i = 0; $i < count($values[0]['queues']); $i++) {
    array_push($mappingfile, "\\\\" . $server . "\\" . $values[0]['queues'][$i]);
}

if($values[0]['default']) {
    array_push($mappingfile, "Default Printer: \\\\" . $server . "\\" . $values[0]['default']);
}
array_push($mappingfile, "");

$fp = fopen($config['mount_dir'] . "/" . $config["computer_dir"] . "/". $values[0]['computername'] .".txt", 'w');
if($fp) {
    fwrite($fp, implode("\r\n", $mappingfile));
    fclose($fp);
}

$response['body']['result'] = 'success';
$response['body']['message'] = $server;
$response['body']['data'] = $mappingfile;
$response['status'] = 200;

?>