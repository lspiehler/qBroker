<?php

require 'vendor/autoload.php';
require 'balancer.php';

use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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
        $this->config = require('config.php');
        $this->httpresponse = array(
            'status' => null,
            'headers' => array(),
            'body' => null
        );
        $this->httpbody = array(
            'result' => null,
            'message' => null,
            //'monitor' => $this->config['monitor'],
            //'monitor_interval' => $this->config['monitor_interval'],
            //'kill_active_monitors' => $this->config['kill_active_monitors'],
            /*'print_mapping_count' => 0,
            'active_server_count' => 0,
            'print_mappings' => array(
                'mapping' => array()
            ),*/
            'active_servers' => array(
                'server' => array()
            )
        );
        $this->balancer = new Balancer();
        $this->servers = $this->balancer->getServers();
    }

    public function getActiveServers() {
        $this->httpbody['result'] = 'success';
        $this->httpbody['message'] = null;
        $this->httpbody['monitor_interval'] = $this->config['monitor_interval'];
        $this->httpbody['kill_active_monitors'] = $this->config['kill_active_monitors'];
        $this->httpbody['active_server_count'] = count($this->servers);
        $this->httpbody['active_servers']['server'] = $this->servers;
        $this->httpresponse['status'] = 200;
        if($this->format=='xml') {
            $this->httpresponse['headers']['Content-type'] = 'application/xml';
        } else {
            $this->httpresponse['headers']['Content-type'] = 'application/json';
        }
        $this->httpresponse['body'] = $this->formatHttpBody($this->httpbody);
        return $this->httpresponse;
    }

    private function formatHttpBody(array $response): string {
        if($this->format=="json") {
            return json_encode($response);
        } else if($this->format=="xml") {
            $encoder = [new XmlEncoder()];
            $normalizer = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizer, $encoder);
            $response = $serializer->serialize($response, 'xml');
            //echo $response;
            return $response;
        } else {
            return json_encode($response);
        }
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
                $this->httpbody['result'] = 'success';
                $this->httpbody['message'] = null;
                //$this->httpbody['monitor_interval'] = $this->config['monitor_interval'];
                $this->httpbody['monitor'] = false;
                //$this->httpbody['print_mappings']['mapping'] = $mappings;
                $this->httpbody['active_servers']['server'] = $this->servers;
                $this->httpresponse['status'] = 200;
                if($this->format=='xml') {
                    $this->httpresponse['headers']['Content-type'] = 'application/xml';
                } else {
                    $this->httpresponse['headers']['Content-type'] = 'application/json';
                }
                $this->httpresponse['body'] = $this->formatHttpBody($this->httpbody);
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
                $this->httpbody['result'] = 'success';
                $this->httpbody['message'] = null;
                $this->httpbody['monitor'] = $this->config['monitor'];
                $this->httpbody['monitor_interval'] = $this->config['monitor_interval'];
                $this->httpbody['print_mapping_count'] = count($mappings);
                $this->httpbody['active_server_count'] = count($this->servers);
                $this->httpbody['print_mappings']['mapping'] = $mappings;
                $this->httpbody['active_servers']['server'] = $this->servers;
                $this->httpresponse['status'] = 200;
                if($this->format=='xml') {
                    $this->httpresponse['headers']['Content-type'] = 'application/xml';
                } else {
                    $this->httpresponse['headers']['Content-type'] = 'application/json';
                }
                $this->httpresponse['body'] = $this->formatHttpBody($this->httpbody);
            }
            return $this->httpresponse;
        } else {
            $this->httpbody['result'] = 'error';
            $this->httpbody['message'] = 'failed to access mapping smb share';
            $this->httpresponse['status'] = 503;
            $this->httpresponse['headers']['Content-type'] = 'application/json';
            $this->httpresponse['body'] = $this->formatHttpBody($this->httpbody);
            return $this->httpresponse;
        }
    }

}

?>