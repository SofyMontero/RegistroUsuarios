<?php

//Api Rest
header("Acces-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once './bd.php';
$con = new bd();

$method = $_SERVER['REQUEST_METHOD'];

function leerJsonEntrada()
{
    $jsonString = file_get_contents("php://input");
    if ($jsonString === false || $jsonString === '') {
        return null;
    }

    $datos = json_decode($jsonString, true);
    return is_array($datos) ? $datos : null;
}

function responderErrorJson($mensaje, $codigo = 400)
{
    http_response_code($codigo);
    echo json_encode(array('error' => $mensaje));
    exit;
}

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
        $arrayResponse[] = $arrayObject;
    }
    echo json_encode($arrayResponse);
    exit;
}

// Metodo para peticiones tipo POST
if ($method == "POST") {
    $jsonOBJ = leerJsonEntrada();
    if (!$jsonOBJ) {
        responderErrorJson('JSON invalido');
    }

    // Fallback: algunos hostings bloquean PUT; el plugin puede reenviar progreso por POST.
    if (isset($jsonOBJ['option']) && $jsonOBJ['option'] === 'actualizar') {
        $row = $con->executePrepared(
            "UPDATE huellas_temp SET imgHuella = :imgHuella, update_time = NOW(), "
            . "statusPlantilla = :statusPlantilla, texto = :texto "
            . "WHERE pc_serial = :serial",
            array(
                'imgHuella' => isset($jsonOBJ['imageHuella']) ? $jsonOBJ['imageHuella'] : '',
                'statusPlantilla' => isset($jsonOBJ['statusPlantilla']) ? $jsonOBJ['statusPlantilla'] : '',
                'texto' => isset($jsonOBJ['texto']) ? $jsonOBJ['texto'] : '',
                'serial' => isset($jsonOBJ['serial']) ? $jsonOBJ['serial'] : '',
            )
        );
        $con->desconectar();
        echo json_encode(array('filas' => (int) $row));
        exit;
    }

    $row = $con->executePrepared(
        "UPDATE huellas_temp SET huella = :huella, imgHuella = :imgHuella, update_time = NOW(), "
        . "statusPlantilla = :statusPlantilla, texto = :texto, foto_usu = :foto_usu, opc = 'stop' "
        . "WHERE pc_serial = :serial",
        array(
            'huella' => isset($jsonOBJ['huella']) ? $jsonOBJ['huella'] : '',
            'imgHuella' => isset($jsonOBJ['imageHuella']) ? $jsonOBJ['imageHuella'] : '',
            'statusPlantilla' => isset($jsonOBJ['statusPlantilla']) ? $jsonOBJ['statusPlantilla'] : '',
            'texto' => isset($jsonOBJ['texto']) ? $jsonOBJ['texto'] : '',
            'foto_usu' => isset($jsonOBJ['foto_usu']) ? $jsonOBJ['foto_usu'] : '',
            'serial' => isset($jsonOBJ['serial']) ? $jsonOBJ['serial'] : '',
        )
    );
    $con->desconectar();
    echo json_encode(array('filas' => (int) $row));
    exit;
}

// Metodo para peticiones tipo PUT
if ($method == "PUT") {
    $jsonOBJ = leerJsonEntrada();
    if (!$jsonOBJ) {
        responderErrorJson('JSON invalido');
    }

    $option = isset($jsonOBJ['option']) ? $jsonOBJ['option'] : '';

    if ($option === 'verificar') {
        $row = $con->executePrepared(
            "UPDATE huellas_temp SET imgHuella = :imgHuella, update_time = NOW(), "
            . "statusPlantilla = :statusPlantilla, texto = :texto, documento = :documento, "
            . "nombre = :nombre, dedo = :dedo, foto_usu = :foto_usu "
            . "WHERE pc_serial = :serial",
            array(
                'imgHuella' => isset($jsonOBJ['imageHuella']) ? $jsonOBJ['imageHuella'] : '',
                'statusPlantilla' => isset($jsonOBJ['statusPlantilla']) ? $jsonOBJ['statusPlantilla'] : '',
                'texto' => isset($jsonOBJ['texto']) ? $jsonOBJ['texto'] : '',
                'documento' => isset($jsonOBJ['documento']) ? $jsonOBJ['documento'] : '',
                'nombre' => isset($jsonOBJ['nombre']) ? $jsonOBJ['nombre'] : '',
                'dedo' => isset($jsonOBJ['dedo']) ? $jsonOBJ['dedo'] : '',
                'foto_usu' => isset($jsonOBJ['foto_usu']) ? $jsonOBJ['foto_usu'] : '',
                'serial' => isset($jsonOBJ['serial']) ? $jsonOBJ['serial'] : '',
            )
        );
    } elseif ($option === 'actualizar') {
        $row = $con->executePrepared(
            "UPDATE huellas_temp SET imgHuella = :imgHuella, update_time = NOW(), "
            . "statusPlantilla = :statusPlantilla, texto = :texto "
            . "WHERE pc_serial = :serial",
            array(
                'imgHuella' => isset($jsonOBJ['imageHuella']) ? $jsonOBJ['imageHuella'] : '',
                'statusPlantilla' => isset($jsonOBJ['statusPlantilla']) ? $jsonOBJ['statusPlantilla'] : '',
                'texto' => isset($jsonOBJ['texto']) ? $jsonOBJ['texto'] : '',
                'serial' => isset($jsonOBJ['serial']) ? $jsonOBJ['serial'] : '',
            )
        );
    } else {
        $row = $con->executePrepared(
            "UPDATE huellas_temp SET imgHuella = :imgHuella, update_time = NOW(), "
            . "statusPlantilla = :statusPlantilla, texto = :texto, opc = 'stop' "
            . "WHERE pc_serial = :serial",
            array(
                'imgHuella' => isset($jsonOBJ['imageHuella']) ? $jsonOBJ['imageHuella'] : '',
                'statusPlantilla' => isset($jsonOBJ['statusPlantilla']) ? $jsonOBJ['statusPlantilla'] : '',
                'texto' => isset($jsonOBJ['texto']) ? $jsonOBJ['texto'] : '',
                'serial' => isset($jsonOBJ['serial']) ? $jsonOBJ['serial'] : '',
            )
        );
    }

    $con->desconectar();
    echo json_encode(array('filas' => (int) $row));
    exit;
}

// Metodo para peticiones tipo PATCH
if ($method == "PATCH") {
    $jsonOBJ = leerJsonEntrada();
    if (!$jsonOBJ) {
        responderErrorJson('JSON invalido');
    }

    $row = $con->executePrepared(
        "UPDATE huellas_temp SET imgHuella = :imgHuella, update_time = NOW(), "
        . "statusPlantilla = :statusPlantilla, texto = :texto, documento = :documento, "
        . "nombre = :nombre, dedo = :dedo, foto_usu = :foto_usu "
        . "WHERE pc_serial = :serial",
        array(
            'imgHuella' => isset($jsonOBJ['imgHuella']) ? $jsonOBJ['imgHuella'] : '',
            'statusPlantilla' => isset($jsonOBJ['statusPlantilla']) ? $jsonOBJ['statusPlantilla'] : '',
            'texto' => isset($jsonOBJ['texto']) ? $jsonOBJ['texto'] : '',
            'documento' => isset($jsonOBJ['documento']) ? $jsonOBJ['documento'] : '',
            'nombre' => isset($jsonOBJ['nombre']) ? $jsonOBJ['nombre'] : '',
            'dedo' => isset($jsonOBJ['dedo']) ? $jsonOBJ['dedo'] : '',
            'foto_usu' => isset($jsonOBJ['foto_usu']) ? $jsonOBJ['foto_usu'] : '',
            'serial' => isset($jsonOBJ['serial']) ? $jsonOBJ['serial'] : '',
        )
    );
    $con->desconectar();
    echo json_encode(array('filas' => (int) $row));
    exit;
}
