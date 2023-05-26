<?php

require 'balancer.php';

class Broker {

    private $config;
    private $httpresponse;
    private $httpbody;
    private $default;
    private $servers;
    private $lbserver;
    private $format;
    private $balancer;

    public function __construct($format)
    {
        //echo $format;
        $this->format = $format;
        $this->config = require('../config.php');
        require_once('httpresponse.php');
        $this->hrc = new HTTPResponse();
        $this->httpresponse = $this->hrc->getHTTPResponse();
        $this->balancer = new Balancer();
        $this->servers = $this->balancer->getServers();
    }

    public function getActiveServers() {
        $this->httpresponse['body']['result'] = 'success';
        $this->httpresponse['body']['message'] = null;
        $this->httpresponse['body']['monitor_interval'] = $this->config['monitor_interval'];
        $this->httpresponse['body']['kill_active_monitors'] = $this->config['kill_active_monitors'];
        $this->httpresponse['body']['active_server_count'] = count($this->servers);
        $this->httpresponse['body']['active_servers']['server'] = $this->servers;
        $this->httpresponse['status'] = 200;
        $this->httpresponse = $this->hrc->formatHttpResponse($this->httpresponse, $this->format);
        return $this->httpresponse;
    }

    private function updatePath(string $path): string {
        //$server = explode("\\", $path)[2];
        $queue = explode("\\", $path)[3];
        return "\\\\" . $this->lbserver . "\\" . $queue;
    } 

    public function getMappings($computer, $user) {
        $this->lbserver = $this->balancer->getBrokeredServer();
        $computer = strtoupper($computer);
        $user = strtoupper($user);
        if (file_exists($this->config['mount_dir'] . "/" . $this->config['computer_dir'])) {
            $mappingsht = array();
            if (file_exists($this->config['mount_dir'] . "/" . $this->config['computer_dir'] . "/" . $computer . ".txt")) {
                $filemappings = file($this->config['mount_dir'] . "/" . $this->config['computer_dir'] . "/" . $computer. ".txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                for($i = 0; $i < count($filemappings); $i++) {
                    $isdefault = stripos($filemappings[$i], $this->config['default_string']);
                    if($isdefault===false) {
                      $path = $this->updatePath(trim($filemappings[$i]));
                    } else {
                      $path = $this->updatePath(trim(substr($filemappings[$i], strlen($this->config['default_string']))));
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
                    #echo $filemappings[$i];
                }
            }
            if (file_exists($this->config['mount_dir'] . "/" . $this->config['user_dir'] . "/" . $user . ".txt")) {
                $filemappings = file($this->config['mount_dir'] . "/" . $this->config['user_dir'] . "/" . $user. ".txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                for($i = 0; $i < count($filemappings); $i++) {
                    $isdefault = stripos($filemappings[$i], $this->config['default_string']);
                    if($isdefault===false) {
                      $path = $this->updatePath(trim($filemappings[$i]));
                    } else {
                      $path = $this->updatePath(trim(substr($filemappings[$i], strlen($this->config['default_string']))));
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
                $this->httpresponse['status'] = 200;
                $this->httpresponse = $this->hrc->formatHttpResponse($this->httpresponse, $this->format);
            }
            return $this->httpresponse;
        } else {
            $this->httpresponse['body']['result'] = 'error';
            $this->httpresponse['body']['message'] = 'failed to access mapping smb share';
            $this->httpresponse['status'] = 503;
            $this->httpresponse = $this->hrc->formatHttpResponse($this->httpresponse, $this->format);
            return $this->httpresponse;
        }
    }

}

?>