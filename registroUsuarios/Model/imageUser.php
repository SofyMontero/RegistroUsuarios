<?php

include_once './bd.php';
$con = new bd();
$ext = "jpg";
if (isset($_GET['documento']) && !empty($_GET['documento'])) {
    $documento = $_GET['documento'];
    $rs= "SELECT foto, ext from usuarios_huella WHERE documento = '" . $documento . "'";


$row = $con->findAll($rs);
    if (count($row) > 0) {
        echo $img = "../imagenes/". $row[0]['ext'];

        $dat = file_get_contents($img);
        echo $dat;
    } else {
        $img = "../imagenes/default.png";
        $dat = file_get_contents($img);
        echo $dat;
    }
} else {
    header("Content-type: image/" . $ext);
    $img = "../imagenes/default.png";
    $dat = file_get_contents($img);
    echo $dat;
}
?>








