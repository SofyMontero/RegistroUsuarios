<?php



require_once './bd.php';
set_time_limit(0);
date_default_timezone_set("America/Bogota");
// include("../../varglob.php"); 
// $sedesesion=$_SESSION['usu_idsede'];

// $sedeaux=$_POST['sede'];

// $sedeaux1=$sedeaux;
// $sede1 =1;  

$fechaActual = date('Y-m-d');
    $horaActual = date('H:i:s');
$fecha_actual = 0;
$fecha_bd = 0;
// if ($_SERVER['REQUEST_METHOD'] == "POST") {
//     $fecha_actual = (isset($_POST['timestamp']) && $_POST['timestamp'] != 'null') ? $_POST['timestamp'] : 0;
// } else {
//     if (isset($_GET['timestamp']) && $_GET['timestamp'] != 'null') {
//         $fecha_actual = $_GET['timestamp'];
//     }
// }

$conn = new bd();

// while ($fecha_bd <= $fecha_actual){
//     $sql = "SELECT update_time FROM huellas_temp where pc_serial = '" . $_POST['token'] . "'  ORDER BY update_time DESC LIMIT 1";
//     $rows = $conn->findAll($sql);
//     usleep(100000);
//     clearstatcache();

//     if (count($rows) > 0) {
//         $fecha_bd = strtotime($rows[0]['update_time']);
//     }
// }

// $sql = "SELECT pc_serial,imgHuella,update_time,texto,statusPlantilla,documento,nombre, opc, foto_usu"
//         . " FROM huellas_temp ORDER BY update_time DESC LIMIT 1";
// $rows = $conn->findAll($sql);





//  $rs3= "SELECT sed_estactual from sedes WHERE   idsedes = '".$sedeaux1."'";


// $row3 = $conn->findAll($rs3);



// // if($row3[0]['sed_estactual']!=''){
// //      $estadoactual=$row3[0]['sed_estactual'];
    

// // }else{
// // $estadoactual=1;
// // }



// // if ($estadoactual==1) {
$horacero = strtotime( "00:00:00" );
// // $hora2 = strtotime( "19:00" );
// // $horacero="00:00:00";

   $sql1 = "SELECT seg_iduser,  seg_horaingreso,seg_ingresoAlmuerzo,seg_salioAlmuerzo,seg_horaSalida FROM seguimientousers where seg_iduser = '1073711329' and seg_fechaingreso ='" .$fechaActual. "'";
     $rows1 = $conn->findAll($sql1);
     $horaingreso = $rows1[0]['seg_horaingreso'] ;
     $horaalmuerzo =  $rows1[0]['seg_ingresoAlmuerzo'];
     $horaalmuerzosalio =  $rows1[0]['seg_salioAlmuerzo'];   

// $newDate = strtotime ( '+10 minute' , $horaalmuerzo ) ; 
//     $newDate = date ( 'H:i:s', $newDate); 
//         echo $newDate;

// $hora = $horaalmuerzosalio;

     function SumaHoras( $hora, $minutos_sumar ) 
{ 
   $minutoAnadir=$minutos_sumar;
   $segundos_horaInicial=strtotime($hora);
   $segundos_minutoAnadir=$minutoAnadir*60;
   $nuevaHora=date("H:i:s",$segundos_horaInicial+$segundos_minutoAnadir);
   return $nuevaHora;
} //fin función


$minutos_sumarle   = 30;

//EJECUTO LA FUNCIÓN y asigno resultado a una variable
// $resultado = SumaHoras( $horaalmuerzosalio , $minutos_sumarle );

// //IMPRIMO RESULTADO
// echo 'A esta Hora: '.$horaalmuerzosalio.' se le sumaran: '.$minutos_sumarle.' minutos : '.$resultado;

   echo' ingresa '.$horaingreso = $rows1[0]['seg_horaingreso'] ;
      echo' almuerzo'.$horaalmuerzo = $rows1[0]['seg_ingresoAlmuerzo'] ;
    echo' sale de almuerzo'.$horasaliodealmuerzo = $rows1[0]['seg_salioAlmuerzo'] ;
                $horasalida = $rows1[0]['seg_horaSalida'];
     
    echo'suma ingresa '.$horaingresosuma = SumaHoras( $horaingreso , $minutos_sumarle );
    echo'suma almuerzo'.$horaalmuerzosuma  = SumaHoras( $horaalmuerzo, $minutos_sumarle );
    echo'suma sale de almuerzo'.$horasaliodealmuerzosuma = SumaHoras( $horasaliodealmuerzo, $minutos_sumarle );


     // if (count($rows1) > 0) {

//       if( $horaActual > $resultado){
// // echo$rows1[0]['seg_ingresoAlmuerzo'];
//     echo'si es igual'.$resultado;
    // $insert1 = "UPDATE seguimientousers SET seg_ingresoAlmuerzo='" .$horaActual. "' WHERE seg_iduser='" .$rows[0]['documento']. "' and seg_ingresoAlmuerzo = '" .$fechaActual. "'  ";
    //  $row1 = $conn->exec($insert1);

  // $insert1 = "UPDATE seguimientousers SET seg_ingresoAlmuerzo='" .$horaActual. "' WHERE seg_iduser='1073711329' and seg_fechaingreso = '" .$fechaActual. "'  ";
  //    $row1 = $conn->exec($insert1);
      // }
     //elseif( $rows1[0]['seg_ingresoAlmuerzo'] == $horacero){
  //      $insert1 = "UPDATE seguimientousers SET seg_salioAlmuerzo='" .$horaActual. "' WHERE seg_iduser='" .$rows[0]['documento']. "' and seg_fechaingreso = '" .$fechaActual. "'  ";
  // $row1 = $conn->exec($insert1);

  //     }elseif($rows1[0]['seg_salioAlmuerzo'] == $horacero){

  // $insert1 = "UPDATE seguimientousers SET seg_horaSalida='" .$horaActual. "' WHERE seg_iduser='" .$rows[0]['documento']. "' and seg_fechaingreso = '" .$fechaActual. "'  ";
  // $row1 = $conn->exec($insert1);
  //     }

        
     // }else{ 

       //  if ($rows[0]['documento']== '' or $rows[0]['documento']== 0 ) {
       //  }else{

       // $insert = "insert into seguimientousers(seg_iduser,seg_fechaingreso,seg_horaingreso,seg_idestado)"
       //       . "values('" .$rows[0]['documento']. "','" .$fechaActual. "','" .$horaActual. "','" .$_POST['tipo']. "')";
       //       $row = $conn->exec($insert);
       //      }          

          // }


// }else if ($estadoactual==2) {




//   $sql1 = "SELECT seg_iduser FROM seguimientousers where seg_iduser = '" . $rows[0]['documento'] . "' and seg_fechaingreso ='" .$fechaActual. "' and seg_ingresoAlmuerzo >'00:00:00'";
//      $rows1 = $conn->findAll($sql1);
//      if (count($rows1) > 0) {

        
//      }else{
//      $insert1 = "UPDATE seguimientousers SET seg_ingresoAlmuerzo='" .$horaActual. "' WHERE seg_iduser='" .$rows[0]['documento']. "' and seg_fechaingreso = '" .$fechaActual. "'  ";
//      $row1 = $conn->exec($insert1);
//      }


// }else if ($estadoactual==3) {

// $sql1 = "SELECT seg_iduser FROM seguimientousers where seg_iduser = '" . $rows[0]['documento'] . "' and seg_fechaingreso ='" .$fechaActual. "' and seg_salioAlmuerzo >'00:00:00'";
//      $rows1 = $conn->findAll($sql1);
//      if (count($rows1) > 0) {

//      }else{   

// $insert1 = "UPDATE seguimientousers SET seg_salioAlmuerzo='" .$horaActual. "' WHERE seg_iduser='" .$rows[0]['documento']. "' and seg_fechaingreso = '" .$fechaActual. "'  ";
//   $row1 = $conn->exec($insert1);

// }
    
// }else if ($estadoactual==4) {

// $sql1 = "SELECT seg_iduser FROM seguimientousers where seg_iduser = '" . $rows[0]['documento'] . "' and seg_fechaingreso ='" .$fechaActual. "' and seg_horaSalida >'00:00:00'";
//      $rows1 = $conn->findAll($sql1);
//      if (count($rows1) > 0) {

//      }else{ 

//     $insert1 = "UPDATE seguimientousers SET seg_horaSalida='" .$horaActual. "' WHERE seg_iduser='" .$rows[0]['documento']. "' and seg_fechaingreso = '" .$fechaActual. "'  ";
//   $row1 = $conn->exec($insert1);


//     }
// }




// $rs2= "SELECT foto, ext from usuarios_huella WHERE documento = '" .$rows[0]['documento'] . "'";
// $row2 = $conn->findAll($rs2);





// $reponse = array();
// $reponse["id"] = $rows[0]['pc_serial'];
// $reponse["timestamp"] = strtotime($rows[0]['update_time']);
// $reponse["texto"] = $rows[0]['texto'];
// $reponse["statusPlantilla"] = $rows[0]['statusPlantilla'];
// $reponse["nombre"] = $rows[0]['nombre'];
// $reponse["documento"] = $rows[0]['documento'];
// $reponse["imgHuella"] = $rows[0]['imgHuella'];
// $reponse["tipo"] = $rows[0]['opc'];
// $reponse["foto_usu"] = $row2[0]['ext'];



// $datosJson = json_encode($reponse);
// $conn->desconectar();
// echo $datosJson;




