<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(! $json = @file_get_contents(__DIR__ . '/../config.js'))
    throw new Exception ('../config.js does not exist');
$config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

$curl = curl_init();

$url = "https://" . $config['qmanager_host'] . "/api/printer/queue/list";
$postdata = json_encode(array(
    'servers' => array($config['qmanager_reference_server']),
    'combine' => true,
    'includeleases' => false,
    'search' => false,
    'updatecache' => false
));

curl_setopt($curl, CURLOPT_POST, 1);

curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);

curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
));

// Optional Authentication:
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($curl, CURLOPT_USERPWD, $config['qmanager_user'] . ":" . $config['qmanager_pass']);

curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($curl);

//print_r($postdata);

$json_result = json_decode($result);

//print_r($json_result[0]);
//echo count($json_result);

curl_close($curl);

if(count($json_result) > 1000) {
    //mysqli_report(MYSQLI_REPORT_OFF);
    $mysqli = mysqli_init();

    if ($mysqli) {
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        $mysqli->options(MYSQLI_OPT_READ_TIMEOUT, 3);
        @$mysqli->real_connect($config["db_rw_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);

        $sql1 = "TRUNCATE `queue_cache`";
        $stmt1 = $mysqli->prepare($sql1);
        //if prepare succeeded
        if($stmt1) {
            $stmt1->execute();
            try {
                $sql2 = "INSERT INTO `queue_cache` (`queue` ,`ip`, `driver`, `location`, `comment`) VALUES (?, ?, ?, ?, ?)";
                $stmt2 = $mysqli->prepare($sql2);
                //if prepare succeeded
                if($stmt2) {
                    @$stmt2->bind_param('sssss', $queue, $ip, $driver, $location, $comment);
                    //$computername = $request['path'][2];
                    //$username = $request['path'][3];
                    for($i = 0; $i < count($json_result); $i++) {
                        //print_r($json_result[$i]->ShareName[0]);
                        $queue = $json_result[$i]->Name;
                        $ip = substr($json_result[$i]->PortName[0], 0, 20);
                        $driver = substr($json_result[$i]->DriverName[0], 0, 50);
                        if(count($json_result[$i]->Location) > 0) {
                            $location = substr($json_result[$i]->Location[0], 0, 200);
                        } else {
                            $location = "";
                        }
                        if(count($json_result[$i]->Comment) > 0) {
                            $comment = substr($json_result[$i]->Comment[0], 0, 200);
                        } else {
                            $comment = "";
                        }
                        //echo $ip;
                        $stmt2->execute();
                    }
                    echo "Updated cache with " . count($json_result) . " print queues";
                } else {

                }
            } catch(Throwable $e) {
                //don't show any errors
                print_r($e);
            }
        } else {

        }
    }
}

?>