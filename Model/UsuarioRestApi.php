<?php

//Api Rest
header("Acces-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once './bd.php';
$con = new bd();

$method = $_SERVER['REQUEST_METHOD'];


// Metodo para peticiones tipo GET
// Parametros: token (pc cliente, opcional en SQL), desde = OFFSET, hasta = CANTIDAD de filas (LIMIT page size).
// Contrato alineado con el plugin C#: paginacion LIMIT offset, count (no "desde..hasta" como rango absoluto).
if ($method == "GET") {
    $token = isset($_GET['token']) ? $_GET['token'] : '';

    $offset = isset($_GET['desde']) ? intval($_GET['desde']) : 0;
    $limit = isset($_GET['hasta']) ? intval($_GET['hasta']) : 200;
    if ($offset < 0) {
        $offset = 0;
    }
    if ($limit < 1) {
        $limit = 200;
    }
    if ($limit > 500) {
        $limit = 500;
    }

    // Total de filas del mismo JOIN que se pagina (un usuario puede tener varias huellas / dedos).
    $sql_count = "SELECT COUNT(*) AS total FROM usuarios_huella u "
            . "INNER JOIN huellas h ON u.documento = h.documento";
    $rs_c = $con->findAll($sql_count);
    $total_filas = (count($rs_c) > 0) ? intval($rs_c[0]['total']) : 0;

    $sql = "SELECT u.documento, u.nombre_completo, h.nombre_dedo, h.huella, h.imgHuella, u.ext "
            . "FROM usuarios_huella u "
            . "INNER JOIN huellas h ON u.documento = h.documento "
            . "LIMIT " . $offset . "," . $limit;
    $rs = $con->findAll($sql);

    $arrayResponse = array();
    for ($index = 0; $index < count($rs); $index++) {
        $arrayObject = array();
        $arrayObject["count"] = $total_filas;
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