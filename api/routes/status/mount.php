<?php

if (file_exists($config['mount_dir'] . "/" . $config['computer_dir'])) {
    $files = scandir($config['mount_dir'] . "/" . $config['computer_dir']);
    $filecount = count($files);
    $response['status'] = 200;
    $response['body']['result'] = 'success';
    $response['body']['data'] = array(
        'mapping_file_count' => $filecount
    );
    $response['body']['message'] = "The mount point contains " . $filecount . " computer mappings";
} else {
    $response['status'] = 500;
    $response['body']['result'] = 'error';
    $response['body']['data'] = null;
    $response['body']['message'] = "Unable to acces the mount point";
}

?>