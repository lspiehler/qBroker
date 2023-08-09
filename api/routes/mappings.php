<?php

if(count($request['path']) < 3) {
  require_once('./include/httpresponse.php');
  $hrc = new HTTPResponse();
  $response = $hrc->getHTTPResponse();
  $response['body']['result'] = 'error';
  $response['body']['message'] = "No computer or username was supplied in the request";
  $response['status'] = 503;
} else {
  require 'include/broker.php';

  $broker = new Broker($config, $format);
  $pathlength = count($request['path']);
  if($pathlength >= 4) {
    $response = $broker->getMappings($request['path'][2], $request['path'][3]);
  } else if($pathlength >= 3) {
    $response = $broker->getMappings($request['path'][2], FALSE);
  } else {
    $response = $broker->getMappings(FALSE, FALSE);
  }

  $computername = $request['path'][2];
  if(count($request['path']) >= 4) {
    if($request['path'][3] == "") {
      $username = "";
    } else {
      $username = $request['path'][3];
    }
  } else {
    $username = "";
  }

  //echo $request['path'][2];
  //echo $request['path'][3];
  //print_r($response["body"]);
  if(array_key_exists("print_mappings", $response["body"])) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $mysqli = mysqli_init();
    if ($mysqli) {
      $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
      $mysqli->options(MYSQLI_OPT_READ_TIMEOUT, 3);
      @$mysqli->real_connect($config["db_rw_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);

      if (!$mysqli->connect_error) {
        $sql1 = "DELETE FROM `mapping_history` WHERE `computername` = ? AND `username` = ?";

        $stmt1 = $mysqli->prepare($sql1);

        if($stmt1) {
          try {
            @$stmt1->bind_param('ss', $computername, $username);
            //$computername = $request['path'][2];
            //$username = $request['path'][3];
            $stmt1->execute();
            //$result = $stmt1->result_metadata();
            //print_r($result);
          } catch(Throwable $e) {
            //don't show any errors
          }

          $sql2 = "INSERT INTO `mapping_history` (`computername`, `username`, `ip`, `printserver`, `queue`, `default`, `datetime` ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
          $stmt2 = $mysqli->prepare($sql2);
          //if prepare succeeded
          if($stmt2) {
            try {
              @$stmt2->bind_param('sssssi', $computername, $username, $ip, $printserver, $queue, $default);
              //$computername = $request['path'][2];
              //$username = $request['path'][3];
              $ip = $clientip;
              for($i = 0; $i < count($response["body"]["print_mappings"]["mapping"]); $i++) {
                $printserver = $response["body"]["print_mappings"]["mapping"][$i]["server"];
                $queue = $response["body"]["print_mappings"]["mapping"][$i]["queue"];
                $default = $response["body"]["print_mappings"]["mapping"][$i]["default"];
                $stmt2->execute();
              }
            } catch(Throwable $e) {
              //don't show any errors
            }

            $sql3 = "INSERT INTO `request_count` (`computername`, `username`, `count`) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE `count` = `count` + 1";

            $stmt3 = $mysqli->prepare($sql3);

            if($stmt3) {
              try {
                @$stmt3->bind_param('ss', $computername, $username);
                //$computername = $request['path'][2];
                //$username = $request['path'][3];
                $stmt3->execute();
                //$result = $stmt1->result_metadata();
                //print_r($result);
              } catch(Throwable $e) {
                //don't show any errors
              }
            }
          }
        }
      } else {
        //throw new Exception($mysqli->connect_error);
      }
    }
  }
}

?>