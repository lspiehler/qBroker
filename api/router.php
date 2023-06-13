<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//default to http 500 in case only errors are shown, if successful, http code will be updated
http_response_code(500);

$request = array();

$request['method'] = $_SERVER['REQUEST_METHOD'];
$request['uri'] = parse_url($_SERVER['REQUEST_URI']);
$request['path'] = explode("/", $request['uri']['path']);
array_shift($request['path']);
$request['query'] = array();
if(array_key_exists('query', $request['uri'])) {
    parse_str($request['uri']['query'], $request['query']);
}

$route = "";

if(count($request['path']) >= 2) {
    $route = $request['path'][1];
}

switch($route) {
    case 'mappings':
        include('./routes/mappings.php');
        break;
    case 'activeservers':
        include('./routes/activeservers.php');
        break;
    case 'check':
        include('./routes/check.php');
        break;
    case 'dns':
        $records = dns_get_record("_qbroker._tcp.lcmchealth.org", DNS_SRV);
        print_r($records);
        break;
        /*$config = include('../config.php');
        require './include/balancer.php';
        $balancer = new Balancer($config);
        print_r($balancer->getServers());*/
        break;
    case "":
        http_response_code(200);
        include('./views/swagger.php');
        break;
    default:
        if(file_exists('./views/' . $route)) {
            http_response_code(200);
            include('./views/' . $route);
        } else {
            http_response_code(404);
            print_r(getallheaders());
            print_r($request);
        }
}

?>