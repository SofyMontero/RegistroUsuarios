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

function colaboradores_normalizar_hora($valor, $fallback)
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

$documentoActual = isset($_POST['documento_actual']) ? trim((string) $_POST['documento_actual']) : '';
$documento = isset($_POST['documento']) ? trim((string) $_POST['documento']) : '';
$nombre = isset($_POST['nombre']) ? trim((string) $_POST['nombre']) : '';
$sede = isset($_POST['sede']) ? trim((string) $_POST['sede']) : '';
$horaInicio = colaboradores_normalizar_hora(isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : '', '07:00:00');
$horaFin = colaboradores_normalizar_hora(isset($_POST['hora_fin']) ? $_POST['hora_fin'] : '', '15:00:00');
$diariaHoras = isset($_POST['jornada_diaria']) ? (float) str_replace(',', '.', (string) $_POST['jornada_diaria']) : 0;
$semanalHoras = isset($_POST['jornada_semanal']) ? (float) str_replace(',', '.', (string) $_POST['jornada_semanal']) : 0;
$diaDescanso = isset($_POST['dia_descanso']) ? (int) $_POST['dia_descanso'] : 0;
$ingresaCedula = isset($_POST['ingresa_cedula']) && (string) $_POST['ingresa_cedula'] === '1';

if ($documentoActual === '' || $documento === '' || $nombre === '') {
    echo json_encode(array('success' => false, 'message' => 'Nombre y cédula son obligatorios'));
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

try {
    $repository->updateCollaborator(array(
        'documento_actual' => $documentoActual,
        'documento' => $documento,
        'nombre' => $nombre,
        'sede' => $sede,
        'hora_inicio' => $horaInicio,
        'hora_fin' => $horaFin,
        'jornada_diaria_minutos' => (int) round($diariaHoras * 60),
        'jornada_semanal_minutos' => (int) round($semanalHoras * 60),
        'dia_descanso' => $diaDescanso,
        'ingresa_cedula' => $ingresaCedula,
    ));
} catch (\InvalidArgumentException $e) {
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
    exit;
} catch (\RuntimeException $e) {
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
    exit;
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'No fue posible guardar el colaborador',
    ));
    exit;
}

echo json_encode(array(
    'success' => true,
    'message' => 'Colaborador actualizado',
));
