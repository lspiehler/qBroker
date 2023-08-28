<?php

//echo implode("/", $request["path"]);
//echo $_SERVER['HTTP_HOST'];
//echo gethostbyaddr('127.0.1.1');
$response['body']['result'] = 'error';
$response['status'] = 500;
$response['body']['data'] = null;

$url = 'https://'.$config['fqdn'].'/self-service';

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$httpresponse = curl_exec($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

$error = curl_error($curl);

if($error) {
    $response['body']['message'] = $error;
} else {
    if($httpcode==200) {
        $response['status'] = 200;
        $response['body']['result'] = 'success';
        $response['body']['message'] = 'The self-service portal is available';
    } else {
        $response['body']['message'] = 'The self-service portal returned an http '. $httpcode;
    }
}
//print_r(get_defined_functions());

/*print_r($response);

if($config['active']) {
    $response['status'] = 200;
    $response['body']['message'] = 'The server is active';
} else {
    $response['status'] = 500;
    $response['body']['message'] = 'The server is NOT active';
}
$response['body']['result'] = 'success';
$response['body']['data'] = array(
    'active' => $config['active']
);*/
?>