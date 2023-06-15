<?php

require 'balancer.php';

class Broker {

    private $config;
    private $httpresponse;
    private $httpbody;
    private $default;
    private $servers;
    private $ttl;
    private $lbserver;
    private $format;
    private $balancer;

    public function __construct($config, $format)
    {
        //echo $format;
        $this->format = $format;
        //$json = file_get_contents('../config.js');
        //echo $json;
        //$this->config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->config = $config;
        //$this->config = require('../config.php');
        require_once('httpresponse.php');
        $this->hrc = new HTTPResponse();
        $this->httpresponse = $this->hrc->getHTTPResponse();
        $this->balancer = new Balancer($this->config);
    }

    private function returnErrorResponse(int $httpcode, string $message): array {
        $this->httpresponse['body']['result'] = 'error';
        $this->httpresponse['body']['message'] = $message;
        $this->httpresponse['status'] = $httpcode;
        $this->httpresponse = $this->hrc->formatHttpResponse($this->httpresponse, $this->format);
        return $this->httpresponse;
    }

    public function getActiveServers() {
        $servers = $this->balancer->getServers();
        if($servers["error"] !== FALSE) {
            return $this->returnErrorResponse(503, $servers["error"]);
        }
        $this->servers = $servers["servers"];
        $this->ttl = $servers["ttl"];
        $this->httpresponse['body']['result'] = 'success';
        $this->httpresponse['body']['message'] = null;
        $this->httpresponse['body']['monitor_interval'] = $this->config['monitor_interval'];
        $this->httpresponse['body']['kill_active_monitors'] = $this->config['kill_active_monitors'];
        $this->httpresponse['body']['active_server_count'] = count($this->servers);
        $this->httpresponse['body']['active_servers']['server'] = $this->servers;
        $this->httpresponse['body']['active_servers']['ttl'] = $this->ttl;
        $this->httpresponse['status'] = 200;
        $this->httpresponse = $this->hrc->formatHttpResponse($this->httpresponse, $this->format);
        return $this->httpresponse;
    }

    private function updatePath(string $queue): string {
        //$server = explode("\\", $path)[2];
        //$queue = explode("\\", $path)[3];
        return "\\\\" . $this->lbserver . "\\" . $queue;
    } 

    public function getMappings($computer, $user) {
        $servers = $this->balancer->getServers();
        if($servers["error"] !== FALSE) {
            return $this->returnErrorResponse(503, $servers["error"]);
        }
        $this->servers = $servers["servers"];
        $this->ttl = $servers["ttl"];
        $lbserver = $this->balancer->getBrokeredServer();
        if($lbserver["error"] !== FALSE) {
            return $this->returnErrorResponse(503, $lbserver["error"]);
        }
        $this->lbserver = $lbserver["server"];
        $computer = strtoupper($computer);
        $user = strtoupper($user);
        if (file_exists($this->config['mount_dir'] . "/" . $this->config['computer_dir'])) {
            $mappingsht = array();
            if (file_exists($this->config['mount_dir'] . "/" . $this->config['computer_dir'] . "/" . $computer . ".txt")) {
                $filemappings = file($this->config['mount_dir'] . "/" . $this->config['computer_dir'] . "/" . $computer. ".txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                for($i = 0; $i < count($filemappings); $i++) {
                    $explodeline = explode("\\", $filemappings[$i]);
                    if(count($explodeline) == 4) {
                        $isdefault = stripos($filemappings[$i], $this->config['default_string']);
                        $path = $this->updatePath(trim($explodeline[3]));
                        if($isdefault!==false) {
                            $this->default = $path;
                        }
                        if(!array_key_exists($path, $mappingsht)) {
                            $mappingsht[$path] = array(
                                'default' => false,
                                'source' => array('computername')
                            );
                        } else {
                            //echo "skipped " . $path;
                        }
                    }
                    #echo $filemappings[$i];
                }
            }
            if (file_exists($this->config['mount_dir'] . "/" . $this->config['user_dir'] . "/" . $user . ".txt")) {
                $filemappings = file($this->config['mount_dir'] . "/" . $this->config['user_dir'] . "/" . $user. ".txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                for($i = 0; $i < count($filemappings); $i++) {
                    $explodeline = explode("\\", $filemappings[$i]);
                    if(count($explodeline) == 4) {
                        $isdefault = stripos($filemappings[$i], $this->config['default_string']);
                        $path = $this->updatePath(trim($explodeline[3]));
                        if($isdefault!==false) {
                            $this->default = $path;
                        }
                        if(!array_key_exists($path, $mappingsht)) {
                            $mappingsht[$path] = array(
                                'default' => false,
                                'source' => array('username')
                            );
                        } else {
                            //echo "skipped " . $path;
                            if(!in_array('username', $mappingsht[$path]['source'])) {
                                array_push($mappingsht[$path]['source'], 'username');
                            }
                        }
                    }
                    #echo $filemappings[$i];
                }
            }
            if(count($mappingsht) < 1) {
                $this->httpresponse['body']['result'] = 'success';
                $this->httpresponse['body']['message'] = null;
                //$this->httpresponse['body']['monitor_interval'] = $this->config['monitor_interval'];
                $this->httpresponse['body']['monitor'] = false;
                //$this->httpresponse['body']['print_mappings']['mapping'] = $mappings;
                $this->httpresponse['body']['active_servers']['server'] = $this->servers;
                $this->httpresponse['body']['active_servers']['ttl'] = $this->ttl;
                $this->httpresponse['body']['print_mapping_count'] = 0;
                $this->httpresponse['body']['active_server_count'] = count($this->servers);
                $this->httpresponse['status'] = 200;
                $this->httpresponse = $this->hrc->formatHttpResponse($this->httpresponse, $this->format);
            } else {
                $mappingsht[$this->default]['default'] = true;
                $mappings = array();
                foreach($mappingsht as $key => $value) {
                    $default = false;
                    if($value['default'] === true) {
                        $default = true;
                    }
                    $parsepath = explode("\\", $key);
                    $server = $parsepath[2];
                    $queue = $parsepath[3];
                    $mapping = array(
                        'server' => $server,
                        'queue' => $queue,
                        'default' => $default,
                        'source' => $value['source']
                    );
                    array_push($mappings, $mapping);
                }
                $this->httpresponse['body']['result'] = 'success';
                $this->httpresponse['body']['message'] = null;
                $this->httpresponse['body']['monitor'] = $this->config['monitor'];
                $this->httpresponse['body']['monitor_interval'] = $this->config['monitor_interval'];
                $this->httpresponse['body']['print_mapping_count'] = count($mappings);
                $this->httpresponse['body']['active_server_count'] = count($this->servers);
                $this->httpresponse['body']['print_mappings']['mapping'] = $mappings;
                $this->httpresponse['body']['active_servers']['server'] = $this->servers;
                $this->httpresponse['body']['active_servers']['ttl'] = $this->ttl;
                $this->httpresponse['status'] = 200;
                $this->httpresponse = $this->hrc->formatHttpResponse($this->httpresponse, $this->format);
            }
            return $this->httpresponse;
        } else {
            return $this->returnErrorResponse(503, 'failed to access mapping smb share');
        }
    }

}

?>