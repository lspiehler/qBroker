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
$nextroute = "";

if(count($request['path']) >= 2) {
    $route = $request['path'][1];
}

if(count($request['path']) >= 3) {
    $nextroute = $request['path'][2];
}

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

function notFound($request) {
    http_response_code(404);
    print_r(getallheaders());
    print_r($request);
}

switch($route != 'handler' && is_file('./routes/' . $route . '.php')) {
    case true:
        include('./routes/handler.php');
        break;
    /*case 'status':
        if (file_exists('./routes/status/'. $request['path'][2] .'.php')) {
            include('./routes/status/'. $request['path'][2] .'.php');
        } else {
            notFound($request);
        }
        break;
    case 'activeservers':
        include('./routes/activeservers.php');
        break;
    case 'check':
        include('./routes/check.php');
        break;
    case "":
        http_response_code(200);
        include('./views/swagger.php');
        break;*/
    default:
        //echo $route;
        //echo $route;
        switch($route != "" && $nextroute != "handler" && is_dir('./routes/' . $route)) {
            case true:
                //include('./routes/'. $route .'.php');
                //break;
                include('./routes/' . $route . '/handler.php');
                break;
            default:
                switch($route) {
                    case 'dns':
                        $records = dns_get_record("_qbroker._tcp.lcmchealth.org", DNS_SRV);
                        print_r($records);
                        break;
                        /*$json = file_get_contents('../config.js');
                        $config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                        require './include/balancer.php';
                        $balancer = new Balancer($config);
                        print_r($balancer->getServers());*/
                        #break;
                    case "":
                        http_response_code(200);
                        include('./views/swagger.php');
                        break;
                    default:
                        if(file_exists('./views/' . $route)) {
                            http_response_code(200);
                            include('./views/' . $route);
                        } else {
                            notFound($request);
                        }
                }
        }
}

?>