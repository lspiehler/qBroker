<?php

class Balancer {

    private array $servers;
    private array $config;
    private $srvlookup;

    public function __construct($config) {
        $this->config = $config;
        require_once('srvlookup.php');
        $this->srvlookup = new SRVLookup($this->config);
    }

    public function getServers(): array {
        /*$this->servers = array(
            'lcmc-prtsrv01',
            'lcmc-prtsrv02',
            'lcmc-prtsrv03'
        );
        return array(
            'error' => false,
            'servers' => $this->servers
        );*/
        $lookup = $this->srvlookup->lookupServers();
        $this->servers = array();
        $ttl = 0;
        if($lookup["error"] === FALSE) {
            for($i = 0; $i < count($lookup["records"]); $i++) {
                if($lookup["records"][$i]["weight"] !== 0) {
                    $ttl = $lookup["records"][$i]["ttl"];
                    //array_push($this->servers, explode(".", $lookup["records"][$i]["target"])[0]);
                    array_push($this->servers, $lookup["records"][$i]["target"]);
                }
            }
            return array(
                'error' => false,
                'ttl' => $ttl,
                'servers' => $this->servers
            );
        } else {
            return array(
                'error' => $lookup["error"]
            );
        }
    }

    public function getBrokeredServer(): array {
        return array(
            'error' => false,
            //'server' => $this->servers[array_rand($this->servers, 1)]
            'server' => $this->servers[0]
        );
    }
}

?>