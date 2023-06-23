<?php

require '../vendor/autoload.php';

use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class HTTPResponse {

    public function getHTTPResponse() {
        return array(
            'status' => null,
            'headers' => array(),
            'body' => array(
                'result' => null,
                'message' => null,
                //'monitor' => $this->config['monitor'],
                //'monitor_interval' => $this->config['monitor_interval'],
                //'kill_active_monitors' => $this->config['kill_active_monitors'],
                /*'print_mapping_count' => 0,
                'active_server_count' => 0,
                'print_mappings' => array(
                    'mapping' => array()
                ),
                'active_servers' => array(
                    'server' => array()
                )*/
            )
        );
    }

    public function sendHTTPResponse($response) {
        foreach($response['headers'] as $key => $value) {
            header($key . ": " . $value);
        }
        
        http_response_code($response['status']);
        //http_response_code(400);
        echo $response['body'];
    }

    public function formatHttpResponse(array $response, string $format): array {
        if($format=="json") {
            $response['headers']['Content-type'] = 'application/json';
            $response['body'] = json_encode($response['body']);
            return $response;
        } else if($format=="xml") {
            $encoder = [new XmlEncoder()];
            $normalizer = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizer, $encoder);
            $response['headers']['Content-type'] = 'application/xml';
            $response['body'] = $serializer->serialize($response['body'], 'xml');
            //echo $response;
            return $response;
        } else {
            return json_encode($response);
        }
    }

}
?>