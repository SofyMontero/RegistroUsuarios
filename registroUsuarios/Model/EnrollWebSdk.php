<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Huella\Controllers\BiometricController;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode(array('filas' => 0, 'message' => 'Metodo no permitido'));
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(array('filas' => 0, 'message' => 'JSON invalido'));
    exit;
}

$controller = new BiometricController();
$controller->createUserDirect($input);
