<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(! $json = @file_get_contents(__DIR__ . '/config.js'))
    throw new Exception ('../config.js does not exist');
$config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

require __DIR__ . '/api/include/balancer.php';
$balancer = new Balancer($config);
$servers = $balancer->getServers();
$server = $balancer->getBrokeredServer();
if(array_key_exists("auto_unmanaged_dir", $config)) {
    $mysqli = mysqli_init();
    if ($mysqli) {
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        $mysqli->options(MYSQLI_OPT_READ_TIMEOUT, 3);
        @$mysqli->real_connect($config["db_rw_host"], $config["db_user"], $config["db_pass"], $config["db_report_name"]);

        if (!$mysqli->connect_error) {
            $sql1 = <<<EOD
    SELECT `computername`, `queue`, COUNT(`queue`) AS `total_prints`, MAX(`event_datetime`) AS `most_recent`, `printserver`, `username`
    FROM `print_log`
    WHERE `printserver` LIKE 'LCMC-PRTSRV%'
    AND `event_datetime` > NOW() - INTERVAL 24 HOUR
    AND `computername` NOT LIKE ('%CTX%')
    AND `computername` NOT LIKE ('%VDI%')
    AND `computername` NOT LIKE ('%EPS%')
    AND `computername` NOT LIKE ('%LCMC-PRTSRV%')
    AND `computername` != ''
    GROUP BY `computername`, `queue`
    ORDER BY `computername`, COUNT(`queue`) DESC, MAX(`event_datetime`) DESC
    EOD;
            //echo $sql1;
            $stmt1 = $mysqli->prepare($sql1);
            if($stmt1) {
                try {
                    //@$stmt1->bind_param('s', $computername);
                    //$computername = $request['path'][2];
                    //$username = $request['path'][3];
                    $stmt1->execute();
                    $result = $stmt1->get_result();
                    $data = $result->fetch_all(MYSQLI_ASSOC);
                    //print_r($data);
                    $mappings = array();
                    if(count($data) > 0) {
                        for($i = 0; $i < count($data); $i++) {
                            if(array_key_exists($data[$i]["computername"], $mappings)) {
                                array_push($mappings[$data[$i]["computername"]], "\\\\" . $server["server"] . "\\" . $data[$i]["queue"]);
                            } else {
                                $mappings[$data[$i]["computername"]] = array("\\\\" . $server["server"] . "\\" . $data[$i]["queue"]);
                            }
                        }
                        foreach($mappings as $key => $mapping) {
                            array_push($mappings[$key], "Default Printer: " . $mapping[0]);
                            array_push($mappings[$key], "");
                            //echo $mapping[0];
                        }
                    }
                    foreach($mappings as $key => $mapping) {
                        if (!file_exists($config['mount_dir'] . "/" . $config['computer_dir'] . "/" . strtoupper($key) . ".txt")) {
                            echo $key;
                            print_r($mapping);
                            $fp = fopen($config['mount_dir'] . "/" . $config["auto_unmanaged_dir"] . "/". strtoupper($key) .".txt", 'w');
                            if($fp) {
                                fwrite($fp, implode("\r\n", $mapping));
                                fclose($fp);
                            }
                        }
                    }
                } catch(Throwable $e) {
                    //don't show any errors
                    echo $e;
                }
            } else {
                echo "Error with query: $sql1";
            }
        } else {

        }

    } else {
        //header("status: 501");
        throw new Exception ('Failed to initialize mysqli');
    }
} else {
    echo "auto_unmanaged_dir variable must be set";
}
?>