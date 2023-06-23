<?php

require_once('./include/httpresponse.php');
$hrc = new HTTPResponse();
$response = $hrc->getHTTPResponse();

try {
    if(! $json = @file_get_contents('../config.js'))
        throw new Exception ('../config.js does not exist');
    $config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    if (file_exists('./routes/'.$route.'/'. $request['path'][2] .'.php')) {
        include('./routes/'.$route.'/'. $request['path'][2] .'.php');
    } else {
        notFound($request);
        exit;
    }

    $response = $hrc->formatHttpResponse($response, $format);
    $hrc->sendHTTPResponse($response);
} catch(Exception $e) {
    $response = $hrc->getHTTPResponse();
    $response['body']['result'] = 'error';
    $response['body']['message'] = $e->getMessage(). " in " . $e->getFile() . " on line " . $e->getLine();
    $response['status'] = 503;
    $response = $hrc->formatHttpResponse($response, $format);
    $hrc->sendHTTPResponse($response);
}

?>