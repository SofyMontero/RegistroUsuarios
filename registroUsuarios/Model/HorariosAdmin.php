<?php

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../inc/auth.php';

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;

requerir_admin_api();

header('Content-Type: application/json; charset=utf-8');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Use POST'));
    exit;
}

function horarios_normalizar_hora($valor, $fallback)
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        $valor = $fallback;
    }
    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $valor, $partes)) {
        $hora = (int) $partes[1];
        $minuto = (int) $partes[2];
        $segundo = isset($partes[3]) ? (int) $partes[3] : 0;
        if ($hora <= 23 && $minuto <= 59 && $segundo <= 59) {
            return sprintf('%02d:%02d:%02d', $hora, $minuto, $segundo);
        }
    }

    return null;
}

$documento = isset($_POST['documento']) ? trim((string) $_POST['documento']) : '';
$horaInicio = horarios_normalizar_hora(isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : '', '07:00:00');
$horaFin = horarios_normalizar_hora(isset($_POST['hora_fin']) ? $_POST['hora_fin'] : '', '15:00:00');
$diariaHoras = isset($_POST['jornada_diaria']) ? (float) str_replace(',', '.', (string) $_POST['jornada_diaria']) : 0;
$semanalHoras = isset($_POST['jornada_semanal']) ? (float) str_replace(',', '.', (string) $_POST['jornada_semanal']) : 0;
$diaDescanso = isset($_POST['dia_descanso']) ? (int) $_POST['dia_descanso'] : 0;

if ($documento === '') {
    echo json_encode(array('success' => false, 'message' => 'El documento es obligatorio'));
    exit;
}

if ($horaInicio === null || $horaFin === null) {
    echo json_encode(array('success' => false, 'message' => 'La hora de inicio o de fin no es válida'));
    exit;
}

if ($diariaHoras < 1 || $diariaHoras > 24) {
    echo json_encode(array('success' => false, 'message' => 'La jornada diaria debe estar entre 1 y 24 horas'));
    exit;
}

if ($semanalHoras < 1 || $semanalHoras > 72) {
    echo json_encode(array('success' => false, 'message' => 'La jornada semanal debe estar entre 1 y 72 horas'));
    exit;
}

if ($diaDescanso < 0 || $diaDescanso > 6) {
    echo json_encode(array('success' => false, 'message' => 'El día de descanso no es válido'));
    exit;
}

$repository = new BiometricRepository(new Database());
if (!$repository->getUserRowByIdentification($documento)) {
    echo json_encode(array('success' => false, 'message' => 'No existe un trabajador con ese documento'));
    exit;
}

try {
    $filas = $repository->updateWorkerSchedule(
        $documento,
        $horaInicio,
        $horaFin,
        (int) round($diariaHoras * 60),
        (int) round($semanalHoras * 60),
        $diaDescanso
    );
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'No fue posible guardar el horario',
    ));
    exit;
}

echo json_encode(array(
    'success' => $filas >= 0,
    'message' => 'Horario actualizado',
));
