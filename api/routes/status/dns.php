<?php

if(! @include_once('./include/srvlookup.php'))
    throw new Exception ('./include/srvlookup.php does not exist');
$srvlookup = new SRVLookup($config);
$lookup = $srvlookup->lookupServers();
if($lookup["error"] === FALSE) {
    $response['status'] = 200;
    $response['body']['result'] = 'success';
    $response['body']['data'] = $lookup;
    $response['body']['message'] = null;
} else {
    $response['status'] = 500;
    $response['body']['result'] = 'error';
    $response['body']['message'] = $lookup["error"];
}

?>