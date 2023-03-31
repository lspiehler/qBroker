<?php

class Balancer {

    private array $servers;

    public function __construct() {
        $this->servers = array(
            'NOEH-PRNT01',
            'LCMC-PRTSRV1'
        );
    }

    public function getServers(): array {
        return $this->servers;
    }

    public function getBrokeredServer(): string {
        return $this->servers[array_rand($this->servers, 1)];
    }
}

?>