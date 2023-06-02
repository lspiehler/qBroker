<?php

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
    case "":
        include('./views/swagger.php');
        break;
    default:
        if(file_exists('./views/' . $route)) {
            include('./views/' . $route);
        } else {
            http_response_code(404);
            print_r(getallheaders());
            print_r($request);
        }
}

?>