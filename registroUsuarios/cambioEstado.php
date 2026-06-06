<?php

require_once 'Model/bd.php';
set_time_limit(0);
date_default_timezone_set("America/Bogota");

$con = new bd();
$foto = null;
$ext = null;

$fechaActual = date('Y-m-d');
    $horaActual = date('H:i:s');
$fecha_actual = 0;
$fecha_bd = 0;

// $delete = "delete fromestados where pc_serial = '" . $_POST['token'] . "'";

// $rowd = $con->exec($delete);

//    echo$insert1 = "UPDATE estados SET offOn='0'";
// $row1 = $con->exec($insert1);

    echo$insert2 = "UPDATE sedes SET sed_estactual='" . $_GET['param1'] . "' WHERE idsedes ='" . $_GET['sede'] . "'";
$row2 = $con->exec($insert2);

//     if (count($rows) > 0) {
//         $fecha_bd = strtotime($rows[0]['update_time']);
//     }
// }

    // echo$delete = "delete from gastos";

    //         $rowd = $con->exec($delete);

    //         if ($delete) {
    //           echo'si ñero';
    //         }


// $sql = "SELECT pc_serial,imgHuella,update_time,texto,statusPlantilla,documento,nombre, opc"
//         . " FROM huellas_temp ORDER BY update_time DESC LIMIT 1";
// $rows = $conn->findAll($sql);




