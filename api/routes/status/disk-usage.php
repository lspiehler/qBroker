<?php

$output = array();
exec("df -h", $output, $res);
//echo $res;
if($res == 0) {
    $response['status'] = 200;
    $response['body']['result'] = 'success';
    $response['body']['message'] = implode("\r\n", $output);
} else {
    $response['status'] = 500;
    $response['body']['result'] = 'error';
    $response['body']['message'] = 'Failed to get server disk utilization';
}

$response['body']['data'] = null;
?>