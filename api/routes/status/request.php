<?php

$response['status'] = 200;
$response['body']['result'] = 'success';
$response['body']['data'] = array(
    'headers' => getallheaders(),
    'request' => $request
);
$response['body']['message'] = null;
?>