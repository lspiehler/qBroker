<?php

$ignore = array(
    ".",
    "..",
    "handler.php",
    "list.php",
    "php.php",
    "index.php",
    "request.php",
    "disk-usage.php"
);

try {
    $files = @scandir(__DIR__);
    $checks = array();
    //print_r($files);

    for($i = 0; $i < count($files); $i++) {
        if(!in_array($files[$i], $ignore)) {
            array_push($checks, str_replace(".php", "", $files[$i]));
        }
    }

    $response['status'] = 200;

    $response['body']['result'] = 'success';
    $response['body']['data'] = array(
        'checks' => $checks
    );
    $response['body']['message'] = null;
} catch(Throwable $e) {
    $response['status'] = 500;

    $response['body']['result'] = 'error';
    $response['body']['data'] = null;
    $response['body']['message'] = $e->getMessage(). " in " . $e->getFile() . " on line " . $e->getLine();
}
?>