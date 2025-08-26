<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

http_response_code(200);

$health = [
    'status' => 'healthy',
    'service' => 'CakePHP Fatture Demo',
    'timestamp' => date('c'),
    'version' => '1.0.0',
    'environment' => 'production'
];

echo json_encode($health, JSON_PRETTY_PRINT);
