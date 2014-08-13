<?php
	/* conectar con el servidor de su casa */
	/*Protocolo de comunicación: 
		Se envía al raspberry tres secciones: dispositivo a setear, separador (guion), caracteristica a setear, 
		separador (guion) y valor a setear (Ejemplo: ON, OFF).
		Los mensajes para el raspberry se ven de la forma "a-b-c" que significa "configura propiedad b del dispositivo
		a con valor c" */
	function backLink(){
		echo "<br><br><a href='index.php'>Volver</a>";
		exit(1);
	}
	session_start();
	$debug=0;
	if ($debug) echo "sesion: ".session_id();
	if (!isset($_SESSION['user_id'])){
		echo "No ha iniciado sesión";
		header("Location: index.php");
		exit(1);
	}
	else{
		/* obtener datos del usuario a partir de su id */
		/* conectar con base de datos */
		require("credenciales.php");
		$db_link=new mysqli($db_host, $db_user, $db_pass,$db_name);
		if ($db_link->connect_errno) {
	    	echo "Error al conectar con MySQL: (" . $db_link->connect_errno . ") " . $db_link->connect_error;
	    	backLink();
		}
		else{
			$db_link->set_charset("utf8");
		}
		/* verificar usuario */
		if (!($stmt = $db_link->prepare("SELECT server_host, server_username, server_pass, server_port
										 FROM usuarios, credenciales
										 WHERE credenciales.casa_id=?")))
		{
	    	echo "Error al preparar consulta: (" . $db_link->errno . ") " . $db_link->error;
	    	exit(1);
		}
		$stmt->bind_param('d',$_SESSION['user_id']);
		$stmt->execute();
		$stmt->bind_result($server, $user, $pass, $port);
		$stmt->store_result();
		$filas=$stmt->num_rows;
		$stmt->fetch();
		if ($filas==0){
			echo "Error al transferir información desde main.php";
			backLink();
		}
	}
	$value;
	/****** ETAPA 1: validaciones internas ******/
	/* 1: validar dispositivo */
	if ((!isset($_GET['disp']))||(!isset($_GET['param']))||(!isset($_GET['value']))){
		echo "Error: no ha entregado argumentos para setear";
		header("Location: main.php"); //porque si tenia sesion iniciada
		exit(1);
	}
	$disp=intval($_GET['disp']);
	/* 2: validar parametro */
	$param=$_GET['param'];
	switch ($param){
		case '0':
			$param="power";
			break;
		default:
			exit("Error (web):Parámetro para el dispositivo no es válido");
	}
	/* 3: validar estado*/
	switch ($_GET['value']){
		case '1':
			$value="ON";
			break;
		case '0':
			$value="OFF";
			break;
		default:
			exit("Error (web): Estado para el dispositivo no es válido");
	}
	/****** ETAPA 2: preparar conexión SSH ******/
	set_include_path('include/phpseclib');
	include('Net/SSH2.php');
	$conexion = new Net_SSH2($server,$port);
	if (!$conexion->login($user, $pass)){
		exit("Error (web): No se pudo establecer conexión con el servidor");
	}

	/****** ETAPA 3: enviar comando al arduino, cumpliendo protocolo ******/
	$comando = "$disp $param $value";
	//$ruta_arduino= "/dev/serial/by-id/".$conexion->exec("ls /dev/serial/by-id/ | grep arduino");
	//if ($debug) echo "ruta al arduino: ".$ruta_arduino."<br>";
	/* validar que arduino está conectado */
	/*if (!(strrpos($ruta_arduino, "no se puede acceder a")===false)){
		exit("Error (web): Arduino no está disponible");
	}*/
	/* enviar comando al raspberry */

	// ESTE DIRECTORIO DEBIERA SER DEPENDIENTE DEL NOMBRE DE USUARIO. EJEMPLO, CARPETA PERSONAL DEL COMPUTADOR.
	$respuesta_shell=$conexion->exec("./client $comando");
	//echo $respuesta_shell;
	if (strstr($respuesta_shell, "Error")){
		exit("Error (web): no se pudo enviar comando al Raspberry: ".$respuesta_shell);
	}
	/****** ETAPA 4: leer lo que el arduino tenga que decir ******/
	/****** ETAPA 5 Y FINAL: responder a la aplicación web ******/
	/* 
		Ideas futuras: generar este archivo a partir de otro, generado tras configurar 
			qué dispositivos habrá disponibles para configurar. Por ahora, sólo está el led 13,
			que se asigna como dispositivo 0. Al parecer el archivo a compilar por el arduino también
			debiera generarse según esta configuración.
	*/
?>