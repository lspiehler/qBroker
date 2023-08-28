<?php

try {
    if(! $json = @file_get_contents('../config.js'))
        throw new Exception ('../config.js does not exist');
    $config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    foreach($config as $key => $value) {
        //echo strpos(strtoupper($key), "PASS");
        //echo $key;
        if(strpos(strtoupper($key), "PASS") !== False) {
            $config[$key] = "PROTECTED_VALUE";
        }
    }
    //print_r($config);
    $response['status'] = 200;
    $response['body']['result'] = 'success';
    $response['body']['message'] = 'config.js exists and was parsed successfully';
    $response['body']['data'] = $config;
} catch(Exception $e) {
    $response = $hrc->getHTTPResponse();
    $response['body']['result'] = 'error';
    $response['status'] = 500;
    $response['body']['message'] = $e->getMessage(). " in " . $e->getFile() . " on line " . $e->getLine();
    $response['body']['data'] = null;
}

?>