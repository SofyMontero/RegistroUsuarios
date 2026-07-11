<?php

/**
 * Polling de enrollment (Home.php). Usa bd.php directamente para compatibilidad en Hostinger.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
set_time_limit(0);
date_default_timezone_set('America/Bogota');

include_once './bd.php';

$token = isset($_POST['token']) ? trim($_POST['token']) : '';
if ($token === '' && isset($_GET['token'])) {
    $token = trim($_GET['token']);
}

$timestampRaw = isset($_POST['timestamp']) ? $_POST['timestamp'] : (isset($_GET['timestamp']) ? $_GET['timestamp'] : 0);
$currentTimestamp = ($timestampRaw === 'null' || $timestampRaw === null || $timestampRaw === '') ? 0 : (int) $timestampRaw;

$con = new bd();
$tokenSql = addslashes($token);
$dbTimestamp = 0;

while ($dbTimestamp <= $currentTimestamp) {
    $rows = $con->findAll(
        "SELECT update_time FROM huellas_temp WHERE pc_serial = '" . $tokenSql . "' ORDER BY update_time DESC LIMIT 1"
    );
    usleep(100000);
    clearstatcache();

    if (count($rows) > 0 && !empty($rows[0]['update_time'])) {
        $dbTimestamp = strtotime($rows[0]['update_time']);
    } else {
        break;
    }
}

$rows = $con->findAll(
    "SELECT pc_serial, imgHuella, update_time, texto, statusPlantilla, documento, nombre, opc, foto_usu
     FROM huellas_temp
     WHERE pc_serial = '" . $tokenSql . "'
     ORDER BY update_time DESC
     LIMIT 1"
);

if (count($rows) === 0) {
    echo json_encode(array(
        'id' => $token,
        'timestamp' => $currentTimestamp,
        'texto' => '---',
        'statusPlantilla' => 'Esperando lectura',
        'nombre' => '------',
        'documento' => '',
        'imgHuella' => null,
        'tipo' => '',
        'foto_usu' => 'mujer.png',
    ));
    $con->desconectar();
    exit;
}

$row = $rows[0];
$foto = 'mujer.png';

if (!empty($row['documento'])) {
    $fotoRows = $con->findAll(
        "SELECT ext FROM usuarios_huella WHERE documento = '" . addslashes($row['documento']) . "'"
    );
    if (count($fotoRows) > 0 && !empty($fotoRows[0]['ext'])) {
        $foto = $fotoRows[0]['ext'];
    }
}

echo json_encode(array(
    'id' => $row['pc_serial'],
    'timestamp' => strtotime($row['update_time']),
    'texto' => $row['texto'],
    'statusPlantilla' => $row['statusPlantilla'],
    'nombre' => !empty($row['nombre']) ? $row['nombre'] : '------',
    'documento' => $row['documento'],
    'imgHuella' => $row['imgHuella'],
    'tipo' => $row['opc'],
    'foto_usu' => $foto,
));

$con->desconectar();
