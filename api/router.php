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

switch($request['path'][1]) {
    case 'mappings':
        include('./routes/mappings.php');
        break;
    case 'activeservers':
        include('./routes/activeservers.php');
        break;
    case 'check':
        include('./routes/check.php');
        break;
    default:
        http_response_code(404);
        print_r($request);
}

?>