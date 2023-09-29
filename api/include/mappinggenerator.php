<?php

class MappingGenerator {
    
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function generateMappings($computername, $server) {
        if($this->config["auto_onboard_dir"]) {
            //mysqli_report(MYSQLI_REPORT_OFF);
            $mysqli = mysqli_init();
            if ($mysqli) {
                $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
                $mysqli->options(MYSQLI_OPT_READ_TIMEOUT, 3);
                @$mysqli->real_connect($this->config["db_rw_host"], $this->config["db_user"], $this->config["db_pass"], $this->config["db_report_name"]);
        
                if (!$mysqli->connect_error) {
                    $sql1 = "SELECT COUNT(`id`) AS `total`, `printername`, MAX(`default`) AS `default` FROM `printers` WHERE `computername` = ? GROUP BY `printername` ORDER BY `default` DESC, `total` DESC";
                    $stmt1 = $mysqli->prepare($sql1);
                    if($stmt1) {
                        try {
                            @$stmt1->bind_param('s', $computername);
                            //$computername = $request['path'][2];
                            //$username = $request['path'][3];
                            $stmt1->execute();
                            $result = $stmt1->get_result();
                            $data = $result->fetch_all(MYSQLI_ASSOC);
                            //print_r($data);
                            $mappingfile = array();
                            if(count($data) > 0) {
                                $vq = @file($this->config['mount_dir'] . "/valid_queues.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                if($vq) {
                                    for($i = 0; $i < count($data); $i++) {
                                        if(in_array(strtoupper($data[$i]["printername"]), $vq)) {
                                            array_push($mappingfile, "\\\\" . $server . "\\" . $data[$i]["printername"]);
                                        }
                                    }
                                    if(count($mappingfile) > 0) {
                                        array_push($mappingfile, "Default Printer: " . $mappingfile[0]);
                                        array_push($mappingfile, "");
                                    }
                                } else {
                                    #throw new Exception ('Failed to read valid queues file');
                                }
                            }
                            if(count($mappingfile) > 0) {
                                //print_r($vq);
                                    //throw new Exception ($this->config['mount_dir'] . '/valid_queues.json does not exist');
                                #$vq = json_decode($vqjson, true, 512, JSON_THROW_ON_ERROR);
                                $fp = fopen($this->config['mount_dir'] . "/" . $this->config["auto_onboard_dir"] . "/". $computername .".txt", 'w');
                                if($fp) {
                                    fwrite($fp, implode("\r\n", $mappingfile));
                                    fclose($fp);
                                }
                                //return $vq;
                            }
                            return $mappingfile;
                        } catch(Throwable $e) {
                            //don't show any errors
                            //echo $e;
                        }
                    }
                }
            }
        }
    }
}

?>