<?php

class SRVLookup {

    private array $config;
    private string $error;

    public function __construct($config) {
        $this->config = $config;
    }

    private function errorHandler($errno, $errstr) {
        $this->error = $errstr;
    }

    public function lookupServers(): array {
        /*$records = dns_get_record("_qbroker._tcp.lcmchealth.org", DNS_SRV);
        $servers = array();
        if($records !== FALSE) {
            for($i = 0; $i < count($records); $i++) {
                array_push($servers, explode(".", $records[$i]["target"])[0]);
            }
        }*/
        set_error_handler(array($this, "errorHandler"), E_WARNING);
        $response = dns_get_record($this->config['srv_record'], DNS_SRV);
        restore_error_handler();
        if($response !== FALSE) {
            return array(
                'error' => false,
                'records' => $response
            );
        } else {
            return array(
                'error' => $this->error
            );
        }
    }
}

?>