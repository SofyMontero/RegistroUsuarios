<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;

header('Content-Type: application/json; charset=utf-8');

$repository = new BiometricRepository(new Database());
$action = isset($_REQUEST['action']) ? trim((string) $_REQUEST['action']) : 'list';

try {
    if ($action === 'list') {
        echo json_encode(array(
            'success' => true,
            'sedes' => $repository->getHeadquartersList(),
        ));
        exit;
    }

    if ($action === 'create') {
        $nombre = isset($_POST['nombre']) ? trim((string) $_POST['nombre']) : '';
        if ($nombre === '') {
            echo json_encode(array(
                'success' => false,
                'message' => 'El nombre de la sede es obligatorio',
                'sedes' => $repository->getHeadquartersList(),
            ));
            exit;
        }

        $ok = $repository->createHeadquarters($nombre);
        echo json_encode(array(
            'success' => $ok,
            'message' => $ok ? 'Sede creada con exito' : 'No fue posible crear la sede. Revisa el esquema de la tabla sedes.',
            'sedes' => $repository->getHeadquartersList(),
        ));
        exit;
    }

    if ($action === 'delete') {
        $sedeId = isset($_POST['sede']) ? trim((string) $_POST['sede']) : '';
        if ($sedeId === '') {
            echo json_encode(array(
                'success' => false,
                'message' => 'Sede no valida',
                'sedes' => $repository->getHeadquartersList(),
            ));
            exit;
        }

        if ($repository->countUsersBySede($sedeId) > 0) {
            echo json_encode(array(
                'success' => false,
                'message' => 'No se puede eliminar: la sede tiene usuarios asignados',
                'sedes' => $repository->getHeadquartersList(),
            ));
            exit;
        }

        $ok = $repository->deleteHeadquarters($sedeId);
        echo json_encode(array(
            'success' => $ok,
            'message' => $ok ? 'Sede eliminada con exito' : 'No fue posible eliminar la sede',
            'sedes' => $repository->getHeadquartersList(),
        ));
        exit;
    }

    echo json_encode(array(
        'success' => false,
        'message' => 'Accion no soportada',
        'sedes' => $repository->getHeadquartersList(),
    ));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error al administrar sedes: ' . $e->getMessage(),
        'sedes' => array(),
    ));
}
