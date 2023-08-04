<?php

if($config['active']) {
    $response['status'] = 200;
} else {
    $response['status'] = 500;
}
$response['body']['result'] = 'success';
$response['body']['data'] = array(
    'active' => $config['active']
);
$response['body']['message'] = null;
?>