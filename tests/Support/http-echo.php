<?php

declare(strict_types=1);

// Router for PHP built-in server: echoes request details back as JSON

header('Content-Type: application/json');
header('X-Echo: yes');

$headers = [];
foreach ($_SERVER as $key => $value) {
    if (str_starts_with($key, 'HTTP_')) {
        $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
    }
}

if (($_GET['sleep'] ?? '') !== '') {
    usleep((int) $_GET['sleep']);
}

echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'body' => file_get_contents('php://input'),
    'headers' => $headers,
]);
