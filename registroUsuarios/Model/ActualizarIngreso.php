<?php

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/tiempo_asistencia.php';

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;

requerir_admin_api();

header('Content-Type: application/json; charset=utf-8');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Use POST'));
    exit;
}

$campos = array(
    'seg_horaingreso' => 'Ingreso',
    'seg_ingresoAlmuerzo' => 'Sale almuerzo',
    'seg_salioAlmuerzo' => 'Regresa almuerzo',
    'seg_ingresoBreak' => 'Sale break',
    'seg_salioBreak' => 'Regresa break',
    'seg_horaSalida' => 'Salida',
);

$documento = isset($_POST['documento']) ? trim((string) $_POST['documento']) : '';
$fecha = isset($_POST['fecha']) ? trim((string) $_POST['fecha']) : '';
$campo = isset($_POST['campo']) ? trim((string) $_POST['campo']) : '';
$hora = normalizar_hora_asistencia(isset($_POST['hora']) ? $_POST['hora'] : '');

if ($documento === '' || $fecha === '') {
    echo json_encode(array('success' => false, 'message' => 'Documento y fecha son obligatorios'));
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(array('success' => false, 'message' => 'La fecha no es válida'));
    exit;
}

if (!isset($campos[$campo])) {
    echo json_encode(array('success' => false, 'message' => 'El campo de hora no es válido'));
    exit;
}

if ($hora === null) {
    echo json_encode(array('success' => false, 'message' => 'Use el formato 00:00:00'));
    exit;
}

$repository = new BiometricRepository(new Database());
if (!$repository->getAttendanceRow($documento, $fecha)) {
    echo json_encode(array('success' => false, 'message' => 'No existe ese registro de ingreso'));
    exit;
}

try {
    $filas = $repository->updateAttendanceField($documento, $fecha, $campo, $hora);
} catch (\Throwable $exception) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'No fue posible guardar la hora',
    ));
    exit;
}

echo json_encode(array(
    'success' => $filas >= 0,
    'message' => 'Hora actualizada',
    'campo' => $campo,
    'hora' => $hora,
    'hora_texto' => formatear_hora_asistencia($hora),
));
