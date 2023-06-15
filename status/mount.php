<?php

//$config = include('../config.php');
$json = file_get_contents('../config.js');
$config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

if (file_exists($config['mount_dir'] . "/" . $config['computer_dir'])) {
    echo "GOOD";
} else {
    echo "BAD";
}

?>