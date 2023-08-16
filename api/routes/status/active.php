<?php

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
);
?>