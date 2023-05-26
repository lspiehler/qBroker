<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = include('../config.php');

if (file_exists($config['mount_dir'] . "/" . $config['computer_dir'])) {
    echo "GOOD";
} else {
    echo "BAD";
}

?>