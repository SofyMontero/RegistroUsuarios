<?php
class DB_mssql  /* La clase DB_mssql se encarga de gestionar todas las conexiones y transacciones que tienen que ver con la base de */
{
	/* Definici&oacute;n de variables principales */
	var $BaseDatos;
	var $Servidor;
	var $Usuario;
	var $Clave;
	var $US_Sesion; 
	/* identificador de conexi&oacute;n y consulta */
	var $Conexion_ID = 0;
	var $Consulta_ID = 0;
	var $Errno = 0;
	var $Error = "";
	/* --------------------------------------- M&eacute;todos de ejecuci&oacute;n de base de datos ---------------------------------------  */
	function conecxion_mssql($bd = "", $host = "localhost", $user = "nobody", $pass = "") 
	{
		/* El m&eacute;todo constructor de la clase se encarga de inicializar los valores de las variables de la cadena de conexi&oacute;n */
		$this->BaseDatos = $bd;
		$this->Servidor = $host;
		$this->Usuario = $user;
		$this->Clave = $pass;
		$this->US_Sesion = "smspush";
	}
	
	function conectar()
	{
	/* 	$bd="transml9_transmillas"; 
		$host="transmillasweb.com";
		$user="transml9_jose";
		$pass="dobarli23t";
		$Usu_ses="vive";
		$salt = "transmi2344fsdfd";   */
		include("variables.php"); 

		/* Llama el archivo donde se definen las variables, este archivo se debe configurar de acuerdo a los par�metros de 
		conexi&oacute;n de cada servidor */
		//header ("Location: index.php?error_login=2");
		if ($bd != "") $this->BaseDatos = $bd;
	
		if ($host != "") $this->Servidor = $host;
		if ($user != "") $this->Usuario = $user;
		if ($pass != "") $this->Clave = $pass;
/* 		echo $this->Servidor;
		echo $this->Usuario;
		echo $this->Clave; */

		$this->Conexion_ID = mysqli_connect($this->Servidor, $this->Usuario, $this->Clave) 	or die("Unable to Connect to '$host'"); // Trata de establecer conexi&oacute;n con la base de datos
		//mysqli_set_charset($this->Conexion_ID,'utf8');
		//mysqli_query($this->Conexion_ID,"SET NAMES 'utf8'"); 
		if (!$this->Conexion_ID) 
		{
			echo"herror no se conecto";
			$this->Error = "Ha fallado la conexi&oacute;n.";
			return 0;
		}
		// Si la conexi&oacute;n es exitosa se debe seleccionar la base de datos definida y asociarla a la conexi&oacute;n.
		if (!@mysqli_select_db($this->Conexion_ID,$this->BaseDatos)) 
		{
			$this->Error = "Imposible abrir ".$this->BaseDatos ;
			return 0;
		}
		@mysqli_query($this->Conexion_ID,"SET NAMES 'utf8'"); 
		return $this->Conexion_ID; // Regresa el identificador de conexi&oacute;n a la base de datos.
		
		
	}
	/* Este m&eacute;todo se encarga de ejecutar una consulta SQL, el par�metro de entrada es la consulta como tal y este ejecuta la consulta 
	a trav&eacute;s de la conexi&oacute;n previamente establecida */
	function Execute($sql = "") 
	{
		if ($sql == "") 
		{
			$this->Error = "No ha especificado una consulta SQL";
			return 0;
		}
		
		$this->Consulta_ID = @mysqli_query($this->Conexion_ID,$sql);
		if (!$this->Consulta_ID) 
		{
			$this->Errno = mysqli_connect_errno();
			
			$this->Error = mysqli_connect_error();
		}
		return $this->Consulta_ID;
	}

	function Executeid($sql = "") 
	{
		if ($sql == "") 
		{
			$this->Error = "No ha especificado una consulta SQL";
			return 0;
		}
		
		$this->Consulta_ID = @mysqli_query($this->Conexion_ID,$sql);
		if (!$this->Consulta_ID) 
		{
			//$this->Errno = mysqli_errno();
			$this->Errno =mysqli_query($this->Conexion_ID,$sql) or die ("Problemas al insertar".mysqli_error($this->Conexion_ID));
			
			//$this->Error = mysqli_error();
		}
		$idexec=mysqli_insert_id($this->Conexion_ID);
		return $idexec;
	}
	
	/* Este m&eacute;todo se devuelve un unico valor de una consulta ejecutada */
	function recogedato($dato)
	{
		$row = mysqli_fetch_row($this->Consulta_ID); 
		return $row[$dato];
	}

	/* Este m&eacute;todo al igual que el anterior recoge un �nico dato de una consulta, la diferencia es que ejecuta la misma y devuelve el valor */
	function recogedato1($dato,$sql)
	{
		$this->Execute($sql);
		$row = mysqli_fetch_row($this->Consulta_ID); 
		return $row[$dato];
	}

	/* Tiene la funci&oacute;n de recoger m�ltiples datos de una misma consulta ejecutada, permite recoger hasta 4 datos simul�neos. */
	function recogedatos($d1,$d2,$d3,$d4)
	{
		$row = mysqli_fetch_row($this->Consulta_ID); 
		$r1=$row[$d1];
		$r2=$row[$d2];
		$r3=$row[$d3];
		$r4=$row[$d4];
	}
	/* Devuelve el n�mero de campos de una consulta determinada */
	function numcampos() 
	{
		return mysqli_num_fields($this->Consulta_ID);
	}
	/* Devuelve el n�mero de registros como resultado de una consulta ejecutada */
	function numregistros()
	{
		return mysqli_num_rows($this->Consulta_ID);
	}
	/* Devuelve el nombre de un campo determinado de una consulta */
	function nombrecampo($numcampo) 
	{
		return mysqli_field_name($this->Consulta_ID, $numcampo);
	}
	/* Este m&eacute;todo cierra una consulta de la base de datos */
	function cerrarconsulta()
	{
		mysqli_close($this->Conexion_ID);
	}

	function edites2($id_p, $nom, $sin, $condecion)
	{
		if($sin==1){
			echo "&nbsp;<a href='resultados6.php?id_encuesta=$id_p&tabla=$condecion'>
			<button class='btn btn-default btn-sm' data-widget='edit' data-toggle='tooltip' style='height:29px;'>Consulta</button></a>";
			if($condecion!="Consulta"){
				echo "&nbsp;<a href='carga.php?id_encuesta=$id_p&tabla=$condecion'>
				<button class='btn btn-default btn-sm' data-widget='edit' data-toggle='tooltip' style='height:29px;'>Carga Masiva</button></a>";
			}
			echo "<a href='cambio_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion'>
			<button class='btn btn-default btn-sm' data-widget='edit' data-toggle='tooltip' style='height:29px;'>Editar</button></a>";
			echo "<a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
			onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'>
			<button class='btn btn-default btn-sm' data-widget='remove' data-toggle='tooltip' title='Remove' style='height:29px;'><i class='fa fa-times'></i></button></a>";
		}
		if($sin==2){
			echo "<a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
			onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'>
			<button class='btn btn-default btn-sm' data-widget='remove' data-toggle='tooltip' title='Remove' style='height:29px;'><i class='fa fa-times'></i></button></a>";
		}
		if($sin==3){
			echo "<a href='cambio_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion'>
			<button class='btn btn-default btn-sm' data-widget='edit' data-toggle='tooltip' style='height:29px;'>Editar</button></a>";
		}
		elseif($sin==0){}
		else {} 
	}

	function edites23($id_p, $nom, $sin, $condecion, $cond, $div)
	{
		if($sin==1){
			echo "<td align='center' class='Intabla'><a href='#' title='Editar' onClick='
			MostrarConsulta2(\"resultados3.php?nom=$nom&id_p=$id_p&condecion=$condecion&div=$div&cond=2\", \"$div\");'><i class='fa fa-edit'></i></a></td>";
			echo "<td align='center' class='Intabla'><a href='#' title='Eliminar' onClick='MostrarConsulta2(\"resultados2.php?cond=$cond&id_p=$id_p&\", \"$div\");'>
			<i class='fa fa-trash-o'></i></a></td>";
		}
		if($sin==2){
			echo "<td align='center' class='Intabla'><a href='del_admin.php?id_param=$id_p&tabla=$nom' title='Eliminar' 
			onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\"); 
			MostrarConsulta2(\"resultados2.php?cond=$cond&id_p=$id_p&\", \"$div\");'><i class='fa fa-trash-o'></i></a></td>";
		}
		if($sin==3){
			echo "<td align='center' class='Intabla'><a href='#' title='Editar' onClick='
			MostrarConsulta2(\"resultados3.php?nom=$nom&id_p=$id_p&condecion=$condecion&div=$div&cond=2\", \"$div\");'><i class='fa fa-edit'></i></a></td>";
		}
		if($sin==4){
			echo "<div class='pull-right btn btn-default'>
			<a href='#' onclick='MostrarConsulta2(\"resultados3.php?nom=$nom&id_p=$id_p&condecion=$condecion&div=$div&cond=1\", \"$div\");'
			<div style='color:#000'>Agregar <i class='fa fa-plus'></i></div></a></div>";
		}
		if($sin==5){
			echo "<td align='center' class='Intabla'><a href='#' title='Editar' onClick='pop_dis($id_p, \"$nom\");'><i class='fa fa-edit'></i></a></td>";
			echo "<td align='center' class='Intabla'><a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
			onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\");'><i class='fa fa-trash-o'></i></a></td>";
		}
		if($sin==6){
			echo "<td align='center' class='Intabla'><a href='#' title='Editar' onClick='pop_dis($id_p, \"$nom\");'><i class='fa fa-edit'></i></a></td>";
		}
	}

	function edites22($id_p, $nom, $sin, $condecion)
	{
		if($sin==1){
			echo "<a href='cambio_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion'><button class='btn btn-default btn-sm' data-widget='edit' data-toggle='tooltip' 
			style='height:30px;'>Editar</button></a>"; echo "<a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
			onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'>
			<button class='btn btn-default btn-sm' data-widget='remove' data-toggle='tooltip' title='Remove' style='height:30px;'><i class='fa fa-times'></i></button></a>";
		}
		if($sin==2){
			echo "<a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
			onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'>
			<button class='btn btn-default btn-sm' data-widget='remove' data-toggle='tooltip' title='Remove' style='height:30px;'><i class='fa fa-times'></i></button></a>";
		}
		if($sin==3){
			echo "<a href='cambio_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion'>
			<button class='btn btn-default btn-sm' data-widget='edit' data-toggle='tooltip' style='height:30px;'>Editar</button></a>";
		}
		elseif($sin==0){}
		else {} 
	}


	function edites($id_p, $nom, $sin, $condecion)
	{
			if($sin==1){
				echo "<td align='center' class='Intabla'><a href='cambio_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Editar'><i class='fa fa-edit'></i></a>
				</td><td align='center' class='Intabla'>";
				echo "<a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
				onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'><i class='fa fa-trash-o'></i></a></td>";
			}
			elseif($sin==2){
				echo "<td align='center' class='Intabla'><a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
				onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'><i class='fa fa-trash-o'></i></a></td>";
			}
			elseif($sin==3){
				echo "<td align='center' class='Intabla'><a href='cambio_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Editar'>
				<i class='fa fa-edit'></i></a></td>";
			}
			elseif($sin==0){}
			else {} 
	}
	
	
		function editar($archivo,$id_p, $nom, $sin, $condecion)
	{
			if($sin==1){
				echo "<td align='center' class='Intabla'><a href='$archivo?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Editar'><i class='fa fa-edit'></i></a>
				</td><td align='center' class='Intabla'>";
				echo "<a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
				onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'><i class='fa fa-trash-o'></i></a></td>";
			}
			elseif($sin==2){
				echo "<td align='center' class='Intabla'><a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
				onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'><i class='fa fa-trash-o'></i></a></td>";
			}
			elseif($sin==3){
				echo "<td align='center' class='Intabla'><a href='$archivo?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Editar'>
				<i class='fa fa-edit'></i></a></td>";
			}	
			else if($sin==4){
				echo "<td align='center' class='Intabla'><a onclick='pop_dis2($id_p, \"$nom\")'; class='fa fa-edit'></i></a>
				</td><td align='center' class='Intabla'>";
				echo "<a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
				onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'><i class='fa fa-trash-o'></i></a></td>";
			}
			elseif($sin==0){}
			else {} 
	}

	function editesp($id_p, $nom, $sin, $condecion, $pend, $pend2)
	{
			if($sin==1){
				echo "<td align='center' class='Intabla'><table class='Intabla'><tr><td>";
				if($pend>0){ echo "<span class='rojito'>* faltan $pend preguntas en total </span>&nbsp;<br>"; }
				if($pend2>0){ echo "<span class='rojito'>* faltan $pend2 preguntas obligatorias</span>&nbsp;"; }
				echo "</td><td><a href='cambio_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Editar'><i class='fa fa-edit'></i></a></td></tr></table>
				</td><td align='center' class='Intabla'>";
				echo "<a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
				onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'><i class='fa fa-trash-o'></i></a></td>";
			}
			elseif($sin==2){
				echo "<td align='center' class='Intabla'><a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
				onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'><i class='fa fa-trash-o'></i></a></td>";
			}
			elseif($sin==3){
				echo "<td align='center' class='Intabla'>";
				if($pend>0){ echo "<span class='rojito'>* faltan $pend preguntas </span>&nbsp;"; }
				echo "<a href='cambio_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Editar'> <i class='fa fa-edit'></i></a></td>";
			}
			elseif($sin==0){
				echo "<td align='center' class='Intabla'><table class='Intabla'><tr><td>";
				if($pend>0){ echo "<span class='rojito'>* faltan $pend preguntas en total </span>&nbsp;<br>"; }
				if($pend2>0){ echo "<span class='rojito'>* faltan $pend2 preguntas obligatorias</span>&nbsp;"; }
				echo "</td></tr></table>";
			}
			else {} 
	}

	
	function alarmas($tabla, $id_param, $desc, $alarma, $desc2, $desc3){
		$sqls="INSERT INTO alarmas (idalarmas, ala_fecha, ala_tabla, idviende, ala_alarma, ala_descripcion, usuarios_idusuarios, ala_estado) 
		VALUES ('', '".date("Y-m-d H:i:s")."', '$tabla', '$id_param', '$desc', '$desc2', '$desc3', 0)";
		$this->Execute($sqls);
	}
	function edites3($id_p, $nom, $sin, $condecion, $novedad)
	{
		$sql1="SELECT COUNT(*) FROM novedades WHERE nov_idvienede='$id_p' AND nov_tabla IN ($novedad) ";
		echo "<td align='center'><a href='#' onclick='pop_dis6($id_p, \"$nom\", $condecion)' ";
		$this->Execute($sql1); $colo="";
		if($this->recogedato(0)>0){ 
			echo "title='"; $colo="color='#FF0000'";
			$sql1="SELECT nov_tabla, nov_fecha FROM novedades WHERE nov_idvienede='$id_p' AND nov_tabla IN ('Agregar Docente', 'Datos no disponibles', 
			'Docente Trasladado sin reemplazo', 'Agregar Docente con reemplazo') ORDER BY nov_fecha DESC ";
			$this->Execute($sql1);
			while($rw2=mysqli_fetch_row($this->Consulta_ID)){
				echo $rw2[1]."\n".$rw2[0]."\n\n";
				if($rw2[0]=="Docente Trasladado sin reemplazo"){ $colo="color='#FF6633'"; } 
				elseif($rw2[0]=="Datos no disponibles"){ $colo="color='#FF0000'"; } 
				elseif($rw2[0]=="Agregar Docente con reemplazo"){ $colo="color='#FFCC00'";}
			}
			echo "'>"; 
		}
		if($sin==1){ echo "<font $colo><i class='fa fa-bell'></i></font>"; }
		elseif($sin==0){}
		else {} 
		echo "</a></td>";
	}

	function edites1($id_p, $nom, $sin, $condecion, $ide)
	{
		if($sin==1){
			echo "<td align='center'><a href='#' onclick='pop_dis7($id_p, \"$nom\", $condecion, $ide)' title='Editar'><i class='fa fa-edit'></i></a></td>";
			echo "<td align='center' class='Intabla'><a href='del_admin.php?id_param=$id_p&tabla=$nom&condecion=$condecion' title='Eliminar' 
			onClick='return confirm(\"".utf8_encode("Est&aacute; seguro de eliminar este registro?")."\")'><i class='fa fa-trash-o'></i></a></td>";
		}
		else {} 
	}
	/* Devuelve el orden predeterminado de una tabla al ejecutar una consulta */
	function orden()
	{
		if(!isset($dre)){$dre="ASC";}
		if($dre=="ASC"){$dre="DESC";}
		else{$dre="ASC";}
		return $dre;
	}

	function selimagen($imagen)
	{
		$ex=explode(".",$imagen);
		if(isset($ex[1])){		
			if($ex[1]=="docx"){$imagen="img/word.gif";}
			else if($ex[1]=="doc"){$imagen="img/word.gif";}
			else if($ex[1]=="xls"){$imagen="img/excel.jpg";}
			else if($ex[1]=="xlsx"){$imagen="img/excel.jpg";}
			else if($ex[1]=="xlsm"){$imagen="img/excel.jpg";}
			else if($ex[1]=="pdf"){$imagen="img/pdf.png";}
			else if($ex[1]=="PDF"){$imagen="img/pdf.png";}
			else if($ex[1]=="jpg"){$imagen="img/imagen.png";}
			else if($ex[1]=="gif"){$imagen="img/imagen.png";}
			else if($ex[1]=="png"){$imagen="img/imagen.png";}
			else if($ex[1]=="ppt"){$imagen="img/ppt.png";}
			else if($ex[1]=="pptx"){$imagen="img/ppt.png";}
			else {$imagen="img/nuevo.png";}
		}
		else {$imagen="img/nuevo.png";}
		return $imagen;
	}
	

	function edita($tabla, $FB)
	{
		$id_param=$_REQUEST["id_param"];
		$param=$_REQUEST["param"];
		$param=substr($param,0,strlen($param)-1);
		$param1=explode(",",$param);
		$bandera=0;
		switch($tabla)
		{
			case "Rol":
			$sql_q="UPDATE roles SET rol_nombre=$param1[0] WHERE idroles='$id_param' ";
			break;
			default:
			$sql_q="Select 1";  $bandera=9;
			break;
		}
		if ($this->Execute($sql_q)){ if($bandera!=9){$bandera=5;}}
		else{$bandera=6;}
		$FB->mensaje_bandera($bandera); 
	}

	function elimi($tabla, $FB)
	{
		$id_param=$_REQUEST["id_param"];
		$param=$_REQUEST["param"];
		$param=substr($param,0,strlen($param)-1);
		$param1=explode(",",$param);
		$bandera=0;
		switch($tabla)
		{
			case "Rol":
			$sql_q="DELETE FROM roles WHERE idroles='$id_param'";
			break;
			default:
			$sql_q="Select 1";  $bandera=9;
			break;
		}
		if ($this->Execute($sql_q)){ if($bandera!=9){$bandera=2;}}
		else{$bandera=3;}
		$FB->mensaje_bandera($bandera); 
	}
}
?>