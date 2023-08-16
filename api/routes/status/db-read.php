<?php

$response['status'] = 500;
$response['body']['result'] = 'error';
$response['body']['data'] = null;

if (function_exists('mysqli_connect')) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $mysqli = mysqli_init();
    $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    $mysqli->options(MYSQLI_OPT_READ_TIMEOUT, 3);
    @$mysqli->real_connect($config["db_ro_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);

    if (!$mysqli->connect_error) {
        $sql1 = "SELECT `id` FROM `mapping_history` LIMIT 1";

        $stmt1 = $mysqli->prepare($sql1);

        if($stmt1) {
            try {
                //@$stmt1->bind_param('ss', $computername, $username);
                //$computername = $request['path'][2];
                //$username = $request['path'][3];
                $stmt1->execute();
                //echo "good";
                //$result = $stmt1->result_metadata();
                if($stmt1->error) {
                    $response['body']['message'] = $stmt1->error;
                } else {
                    $result = $stmt1->get_result();
                    //print_r($result);
                    if(count($result->fetch_row()) >= 1) {
                        $response['status'] = 200;
                        $response['body']['result'] = 'success';
                        //$response['body']['data'] = null;
                        $response['body']['message'] = 'Successfully read from the database on ' . $config["db_ro_host"];
                    } else {
                        $response['body']['message'] = "The SQL query \"" . $sql1 . "\" returned 0 rows";
                    }
                }
            } catch(Throwable $e) {
                //don't show any errors
                $response['body']['message'] = $e->getMessage(). " in " . $e->getFile() . " on line " . $e->getLine();
            }
        } else {
            $response['body']['message'] = "The mysqli library failed to prepare the SQL query \"" . $sql1 . "\"";
        }
    } else {
        $response['body']['message'] = $mysqli->connect_error;
    }
} else {
    $response['body']['message'] = "The mysqli library isn't installed";
}

?>