<?php

//Api Rest
header("Acces-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once './bd.php';
$con = new bd();

$method = $_SERVER['REQUEST_METHOD'];


// Metodo para peticiones tipo GET
if ($method == "GET") {
//    eliminar el token
    $token = $_GET['token'];
    $documento = isset($_GET['documento']) ? $_GET['documento'] : '';

    if (!empty($documento)) {
        // Sprint 6: verificación 1:1 — solo plantillas de un usuario
        $sql = "select u.documento, u.nombre_completo, h.nombre_dedo, h.huella, h.imgHuella, u.ext "
                . "from usuarios_huella u "
                . "inner join huellas h on u.documento = h.documento "
                . "where u.documento = '" . $documento . "'";
        $rs = $con->findAll($sql);
        $rs_c = array(array('total' => count($rs)));
    } else {
        $desde = $_GET['desde'];
        $hasta = $_GET['hasta'];

        $sql = "select u.documento, u.nombre_completo, h.nombre_dedo, h.huella, h.imgHuella, u.ext "
                . "from usuarios_huella u "
                . "inner join huellas h on u.documento  = h. documento limit " . $desde . "," . $hasta . " ";
        $rs = $con->findAll($sql);

        $sql_ = "select count(documento) total from usuarios_huella";
        $rs_c = $con->findAll($sql_);
    }

    $arrayResponse = array();
    for ($index = 0; $index < count($rs); $index++) {
        $arrayObject = array();
        $arrayObject["count"] = $rs_c[0]['total'];
        $arrayObject["documento"] = $rs[$index]["documento"];
        $arrayObject["nombre_completo"] = $rs[$index]["nombre_completo"];
        $arrayObject["nombre_dedo"] = $rs[$index]["nombre_dedo"];
        $arrayObject["huella"] = $rs[$index]["huella"];
        $arrayObject["imgHuella"] = $rs[$index]["imgHuella"];
        $arrayObject["foto_usu"] = $rs[$index]["ext"];
        // $arrayObject["ext"] = $rs[$index]["ext"];



        



        $arrayResponse[] = $arrayObject;
    }
//echo count($arrayResponse); die;
    echo json_encode($arrayResponse);
}

// Metodo para peticiones tipo POST
if ($method == "POST") {
    $jsonString = file_get_contents("php://input");
    $jsonOBJ = json_decode($jsonString, true);
    $query = "update huellas_temp set huella = '" . $jsonOBJ['huella'] . "', imgHuella = '" . $jsonOBJ['imageHuella'] . "',"
            . "update_time = NOW(), statusPlantilla = '" . $jsonOBJ['statusPlantilla'] . "',"
            . "texto = '" . $jsonOBJ['texto'] . "',foto_usu = '" . $jsonOBJ['foto_usu'] . "' "
            . "where pc_serial = '" . $jsonOBJ['serial'] . "'";


//    echo $query;
    $row = $con->exec($query);
    $con->desconectar();
    echo json_encode("Filas Agregadas: " . $row);
}


// Metodo para peticiones tipo PUT
if ($method == "PUT") {
    $jsonString = stripslashes(file_get_contents("php://input"));
    $jsonOBJ = json_decode($jsonString);

    if ($jsonOBJ->option == "verificar") {
        $query = "update huellas_temp set imgHuella = '" . $jsonOBJ->imageHuella . "',"
                . "update_time = NOW(),"
                . "statusPlantilla = '" . $jsonOBJ->statusPlantilla . "',"
                . "texto = '" . $jsonOBJ->texto . "',"
                . "documento =  '" . $jsonOBJ->documento . "',"
                . "nombre = '" . $jsonOBJ->nombre . "',"
                . "dedo =  '" . $jsonOBJ->dedo . "', "
                . "foto_usu = '" . $jsonOBJ->foto_usu . "'"
                . "where pc_serial = '" . $jsonOBJ->serial . "'";
    } else {
        $query = "update huellas_temp set imgHuella = '" . $jsonOBJ->imageHuella . "',"
                . "update_time = NOW(), statusPlantilla = '" . $jsonOBJ->statusPlantilla . "',"
                . " texto = '" . $jsonOBJ->texto . "', opc = 'stop' "
                . "where pc_serial = '" . $jsonOBJ->serial . "'";
    }


    $row = $con->exec($query);
    $con->desconectar();
    echo json_encode("Filas Actualizadas: " . $row);
}



// Metodo para peticiones tipo PATCH
if ($method == "PATCH") {
    $jsonString = file_get_contents("php://input");
    $jsonOBJ = json_decode($jsonString, true);
    $query = "update huellas_temp set imgHuella = '" . $jsonOBJ['imgHuella'] . "',"
            . "update_time = NOW(), statusPlantilla = '" . $jsonOBJ['statusPlantilla'] . "', texto = '" . $jsonOBJ['texto'] . "', "
            . "documento = '" . $jsonOBJ['documento'] . "', nombre = '" . $jsonOBJ['nombre'] . "',"
            . "dedo = '" . $jsonOBJ['dedo'] . "',foto_usu = '" . $jsonOBJ['foto_usu'] . "' where pc_serial = '" . $jsonOBJ['serial'] . "'";
    $row = $con->exec($query);
    $con->desconectar();
    echo json_encode("Filas Actualizadas: " . $row);
}