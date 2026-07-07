<?php

include_once './bd.php';
$con = new bd();
$delete = "delete from huellas_temp where pc_serial = '" . $_POST['token'] . "'";
$con->exec($delete);
$insert = "insert into huellas_temp (pc_serial, texto, statusPlantilla, opc, update_time) "
        . "values ('" . $_POST['token'] . "', 'El sensor de huella dactilar esta activado', 'Muestras Restantes: 4', 'capturar', NOW())";
$row = $con->exec($insert);
$con->desconectar();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('filas' => (int) $row));
