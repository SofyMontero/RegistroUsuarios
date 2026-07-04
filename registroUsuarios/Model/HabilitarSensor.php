<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json; charset=utf-8");

include_once './bd.php';
date_default_timezone_set("America/Bogota");

// Respuesta inmediata para "Probar conexión" del plugin .NET.
if (isset($_GET['ping']) && $_GET['ping'] === '1') {
    echo json_encode(array('fecha_creacion' => 0, 'opc' => 'reintentar', 'documento' => ''));
    exit;
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token === '' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
}

$con = new bd();

// Consulta inmediata: el plugin .NET hace polling en bucle local (evita 504 Gateway Timeout).
$query = "Select update_time, opc, documento from huellas_temp where pc_serial = '" . $token . "' ORDER BY id DESC LIMIT 1";
$datos_query = $con->findAll($query);

$array = array('fecha_creacion' => 0, 'opc' => 'reintentar', 'documento' => '');
for ($i = 0; $i < count($datos_query); $i++) {
    $array['fecha_creacion'] = strtotime($datos_query[$i]['update_time']);
    $array['opc'] = $datos_query[$i]['opc'];
    if (!empty($datos_query[$i]['documento'])) {
        $array['documento'] = $datos_query[$i]['documento'];
    }
}
$con->desconectar();
echo json_encode($array);
