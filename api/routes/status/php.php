<?php

require '../vendor/autoload.php';

use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

$classes = array(
    'Symfony\Component\Serializer\Encoder\XmlEncoder',
    'Symfony\Component\Serializer\Normalizer\ObjectNormalizer',
    'Symfony\Component\Serializer\Serializer'
);

$missingclasses = array();

for($i = 0; $i < count($classes); $i++) {
    if(class_exists($classes[$i])) {

    } else {
        //echo $classes[$i];
        array_push($missingclasses, $classes[$i]);
    }
}

if(count($missingclasses) <= 0) {
    $response['status'] = 200;
    $response['body']['result'] = 'success';
    $response['body']['data'] = array(
        "version" => phpversion(),
        "dependency_check" => "success"
    );
    $response['body']['message'] = null;
} else {
    $response['status'] = 500;
    $response['body']['result'] = 'error';
    $response['body']['message'] = 'The following classes could not be found: ' . implode(", ", $missingclasses);
}

?>