<?php

$config = include('../config.php');

if (file_exists($config['mount_dir'] . "/" . $config['computer_dir'])) {
    echo "GOOD";
} else {
    echo "BAD";
}

?>