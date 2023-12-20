<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../include/httpresponse.php');
$hrc = new HTTPResponse();
$response = $hrc->getHTTPResponse();

$response['body']['result'] = 'success';
//$response['body']['message'] = $server;
$response['status'] = 200;

$mysqli = mysqli_init();
if ($mysqli) {
    $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    $mysqli->options(MYSQLI_OPT_READ_TIMEOUT, 3);
    @$mysqli->real_connect($config["db_rw_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);
    $maxresults = 10;
    if (!$mysqli->connect_error) {
        $q = "";
        if(isset($_GET['q'])) {
            $q = $_GET['q'];
        }
        $type = "query";
        if(isset($_GET['_type'])) {
            $type = $_GET['_type'];
        }
        if(isset($_GET['page'])) {
            $page = intval($_GET['page']);
        }
        $sql1 = array("SELECT `queue`, `ip`, `driver`, `location`, `comment` FROM `queue_cache`");
        if($q != "") {
            array_push($sql1, "WHERE `queue` LIKE '%$q%' OR `ip` LIKE '%$q%'");
        }
        array_push($sql1, "ORDER BY `queue` LIMIT $maxresults");
        if($type == "query:append") {
            $offset = $page * $maxresults;
            array_push($sql1, "OFFSET $offset");
        }
        //echo implode("\r\n", $sql1);
        $stmt1 = $mysqli->prepare(implode("\r\n", $sql1));
        if($stmt1) {
            try {
                //@$stmt1->bind_param('s', $computername);
                //$computername = $request['path'][2];
                //$username = $request['path'][3];
                $stmt1->execute();
                $result = $stmt1->get_result();
                $data = $result->fetch_all(MYSQLI_ASSOC);
                //print_r($data);
                if(count($data) > 0) {
                    $jsonarr = array(
                        'results' => array(),
                        'pagination' => array(
                            'more' => true
                        )
                    );
                    if(count($data) < 10) {
                        $jsonarr['pagination']['more'] = false;
                    }
                    $id = 0;
                    for($i = 0; $i < count($data); $i++) {
                        array_push($jsonarr['results'], array(
                            'id' => $data[$i]["queue"],
                            'text' => $data[$i]["queue"],
                            'ip' => $data[$i]["ip"],
                            'driver' => $data[$i]["driver"],
                            'location' => $data[$i]["location"],
                            'comment' => $data[$i]["comment"],
                        ));
                        $id++;
                    }
                    $response['body']['data'] = $jsonarr;
                } else {
                    $jsonarr = array(
                        'results' => array(),
                        'pagination' => array(
                            'more' => false
                        )
                    );
                    $response['body']['data'] = $jsonarr;
                }
            } catch(Throwable $e) {
                //don't show any errors
                print_r($e);
            }
        }
    }
}

?>