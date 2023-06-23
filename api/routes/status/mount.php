<?php

if (file_exists($config['mount_dir'] . "/" . $config['computer_dir'])) {
    $files = scandir($config['mount_dir'] . "/" . $config['computer_dir']);
    $response['status'] = 200;
    $response['body']['result'] = 'success';
    $response['body']['data'] = array(
        'mapping_file_count' => count($files)
    );
    $response['body']['message'] = null;
} else {
    $response['status'] = 500;
    $response['body']['result'] = 'success';
    $response['body']['data'] = array(
        'mapping_file_count' => count($files)
    );
    $response['body']['message'] = null;
}

?>