<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;

header('Content-Type: application/json; charset=utf-8');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    http_response_code(405);
    echo json_encode(array('filas' => 0, 'message' => 'Use POST'));
    exit;
}

$documento = isset($_POST['documento']) ? trim((string) $_POST['documento']) : '';
$nombre = isset($_POST['nombre']) ? trim((string) $_POST['nombre']) : '';
$telefono = isset($_POST['telefono']) ? trim((string) $_POST['telefono']) : '';
$sede = isset($_POST['sede']) ? trim((string) $_POST['sede']) : '';

if ($documento === '' || $nombre === '') {
    echo json_encode(array('filas' => 0, 'message' => 'Documento y nombre son obligatorios'));
    exit;
}

$database = new Database();
$repository = new BiometricRepository($database);

if ($repository->getUserRowByIdentification($documento)) {
    echo json_encode(array('filas' => 0, 'message' => 'Ya existe un usuario con ese documento'));
    exit;
}

try {
    $filas = $repository->createAdministrativeUser($documento, $nombre);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(array(
        'filas' => 0,
        'message' => 'No fue posible registrar el usuario. Verifique la tabla usuarios y el log del servidor.',
    ));
    exit;
}

if ($filas < 1) {
    echo json_encode(array('filas' => 0, 'message' => 'No fue posible registrar el usuario'));
    exit;
}

try {
    $repository->updateAdministrativeUserExtras($documento, $telefono, $sede);
} catch (\Throwable $e) {
    // Telefono o sede: columnas opcionales segun esquema; el alta principal ya quedo.
}

echo json_encode(array(
    'filas' => $filas,
    'message' => 'Usuario registrado con exito',
));
