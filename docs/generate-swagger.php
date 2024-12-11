<?php

namespace PTA\docs;

require '/bitnami/wordpress/wp-content/plugins/portals-to-adventure/vendor/autoload.php';

$Paths = [
    __DIR__ . '/API',
];



$openapi = \OpenApi\Generator::scan($Paths);

header('Content-Type: application/json');
echo $openapi->toJson();