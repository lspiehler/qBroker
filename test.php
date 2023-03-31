<?php

if (file_exists("/printermappings/mount.lock")) {
	echo "true\r\n";
} else {
	echo "false\r\n";
}

?>
