<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$json = file_get_contents('php://input');
$values = json_decode($json, true);

#print_r($values['winlog']);

##file_put_contents('/tmp/print.json', $json);
#$fp = fopen('/var/www/html/default/test/print.json', 'w') or die("Unable to open file!");
#fwrite($fp, $json) or die("Unable to write to file!");
#fclose($fp) or die("Unable to write to file!");

#print_r($values);

$myServer = "localhost";
$myUser = "report_receiver";
$myPass = "3Vt?B22G_LEejG@Z";
$myDB = "report_receiver";

if($values['winlog']['event_id']=="372") {
	#$fp = fopen('/var/www/html/default/test/print.json', 'w') or die("Unable to open file!");
        #fwrite($fp, $json) or die("Unable to write to file!");
        #fclose($fp) or die("Unable to write to file!");
	$con=mysqli_connect($myServer,$myUser,$myPass,$myDB);

        $eventid = intval($con->real_escape_string($values['winlog']['event_id']));
        $printserver = $con->real_escape_string($values['winlog']['computer_name']);
        $computername = $con->real_escape_string(str_replace("\\\\", "", $values['winlog']['user_data']['Param9']));
        $username = $con->real_escape_string($values['winlog']['user_data']['Param2']);
        $print_name = $con->real_escape_string($values['winlog']['user_data']['Param1']);
        $queue = $con->real_escape_string($values['winlog']['user_data']['Param3']);
        $port = "";
        $size = $con->real_escape_string($values['winlog']['user_data']['Param5']);
        $pages = $con->real_escape_string($values['winlog']['user_data']['Param7']);

        $date = new DateTime($values['event']['created']);
        $formatted_date = $date->format('Y-m-d H:i:s');

        if (mysqli_connect_errno()){
                //echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }

        $sql = "INSERT INTO `print_log` (`eventid`, `printserver`,`computername`, `username`, `print_name`, `queue`, `port`, `size`, `pages`, `event_datetime`, `processed_datetime`) VALUES (" . $eventid . " ,'" . $printserver . "', '" . $computername . "', '" . $username . "', '" . $print_name . "', '" . $queue . "', '" . $port . "', " . $size . ", " . $pages . ", '" . $formatted_date . "', NOW())";

        echo $sql;

        $result = mysqli_query($con,$sql);

        echo $result;
} else if($values['winlog']['event_id']=="314") {
	#$fp = fopen('/var/www/html/default/test/print.json', 'w') or die("Unable to open file!");
	#fwrite($fp, $json) or die("Unable to write to file!");
	#fclose($fp) or die("Unable to write to file!");
	$con=mysqli_connect($myServer,$myUser,$myPass,$myDB);

        $eventid = intval($con->real_escape_string($values['winlog']['event_id']));
        $printserver = $con->real_escape_string($values['winlog']['computer_name']);
        $computername = "";
        $username = $con->real_escape_string($values['winlog']['user_data']['Param3']);
        $print_name = $con->real_escape_string($values['winlog']['user_data']['Param2']);
        $queue = $con->real_escape_string($values['winlog']['user_data']['Param4']);
        $port = "";
        $size = 0;
        $pages = 0;

        $date = new DateTime($values['event']['created']);
        $formatted_date = $date->format('Y-m-d H:i:s');

        if (mysqli_connect_errno()){
                //echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }

        $sql = "INSERT INTO `print_log` (`eventid`, `printserver`,`computername`, `username`, `print_name`, `queue`, `port`, `size`, `pages`, `event_datetime`, `processed_datetime`) VALUES (" . $eventid . " ,'" . $printserver . "', '" . $computername . "', '" . $username . "', '" . $print_name . "', '" . $queue . "', '" . $port . "', " . $size . ", " . $pages . ", '" . $formatted_date . "', NOW())";

        echo $sql;

        $result = mysqli_query($con,$sql);

        echo $result;
} else {
	$con=mysqli_connect($myServer,$myUser,$myPass,$myDB);

	$eventid = intval($con->real_escape_string($values['winlog']['event_id']));
	$printserver = $con->real_escape_string($values['winlog']['computer_name']);
	$computername = $con->real_escape_string(str_replace("\\\\", "", $values['winlog']['user_data']['Param4']));
	$username = $con->real_escape_string($values['winlog']['user_data']['Param3']);
	$print_name = $con->real_escape_string($values['winlog']['user_data']['Param2']);
	$queue = $con->real_escape_string($values['winlog']['user_data']['Param5']);
	$port = $con->real_escape_string($values['winlog']['user_data']['Param6']);
	$size = $con->real_escape_string($values['winlog']['user_data']['Param7']);
	$pages = $con->real_escape_string($values['winlog']['user_data']['Param8']);

	$date = new DateTime($values['event']['created']);
	$formatted_date = $date->format('Y-m-d H:i:s');

	if (mysqli_connect_errno()){
	        //echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}

	$sql = "INSERT INTO `print_log` (`eventid`, `printserver`,`computername`, `username`, `print_name`, `queue`, `port`, `size`, `pages`, `event_datetime`, `processed_datetime`) VALUES (" . $eventid . " ,'" . $printserver . "', '" . $computername . "', '" . $username . "', '" . $print_name . "', '" . $queue . "', '" . $port . "', " . $size . ", " . $pages . ", '" . $formatted_date . "', NOW())";

	echo $sql;

	$result = mysqli_query($con,$sql);

	echo $result;
}
//echo $insert;

print_r($values);

?>
