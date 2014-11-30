<!DOCTYPE html>
<?php
	/* Comprobar que el usuario ingresa a este vínculo por haber iniciado sesión */
	session_start();
	if (!isset($_SESSION['user_name'])){
		//echo "<script>console.log('not set!')</script>";
		session_destroy();
		header("Location: index.php");
		exit();
	}
	/* Establecer conexión SSH con su servidor de domótica */
	/* 1: Obtener información desde base de datos para conectarse con el servidor domótico.
			Específicamente obtener: nombre del host, puerto, usuario del host, contraseña del mismo. */
	require("db_connect.php");
	if (!($stmt = $db_link->prepare("SELECT hostname, puerto, usuarios_domotica.usuario, usuarios_domotica.password, usuarios_web.tipo
									 FROM (usuarios_web JOIN servidores_domotica 
									 		ON usuarios_web.id_servidor_domotica = servidores_domotica.id_servidor_domotica) JOIN usuarios_domotica 
													ON usuarios_domotica.id = servidores_domotica.id_user
									 WHERE usuarios_web.nombre_usuario = ?")))
		{
	    	/*echo "Error al preparar consulta: (" . $db_link->errno . ") " . $db_link->error;
	    	exit(1);*/
	    	echo "<script>alert('Ha habido un problema al preparar la consulta para la base de datos')</script>";
	    	sleep(5000);
	    	header("Location: logout.php");
		}
	$stmt->bind_param('s',$_SESSION['user_name']);
	$stmt->execute();
	$stmt->bind_result($dom_hostname, $dom_puerto, $dom_usuario, $dom_password, $user_type);
	$stmt->store_result();
	$filas=$stmt->num_rows;
	$stmt->fetch();
	if ($filas==0){
		/*echo "Error al transferir información desde main.php";
		backLink();*/
		echo "<script>alert('Ha habido un problema al buscar la información del usuario')</script>";
		sleep(5000);
		header("Location: logout.php");
	}
	/* 1.5: Si el usuario es administrador del sistema, llevarlo a la página de agregar o quitar usuarios.*/
	if ($user_type == 'A'){
		header("Location: agregar_cliente.php");
		//exit();
	}
	/* 2: Establecer la conexión */
	$_SESSION['getDisps']=1;
	$_SESSION['hostname']=$dom_hostname;
	$_SESSION['puerto']=$dom_puerto;
	$_SESSION['usuario']=$dom_usuario;
	$_SESSION['password']=$dom_password;
	require("ssh_request2.php");

	/* echoDisps escribe texto que será procesado por javascript. Crea arreglos con los datos necesarios para que javascript sea
	capaz de dibujar la tabla de dispositivos */
	function echoDisps($respuesta_shell){
		/* arreglo con strings de info de cada dispositivo. 
		Ej: LED_ROJO.power:0.start_time:h1-NULL.stop_time:h1-NULL */
		$disp_array=explode(" ", trim($respuesta_shell));
		/* arreglo con strings con todos los parámetros del dispositivo.
		Ej: {"LED_ROJO", "power:0", "start_time:h1-NULL", "stop_time:h1-NULL"} */
		$fila_sample=explode(".",$disp_array[0]);
		/* arreglo con los nombres de todos los parámetros
		Ej: {"power","start_time","stop_time"}*/
		$param_names=array();
		for ($i=1;$i<sizeof($fila_sample);$i++){ //comienza en 1 porque 0 es el nombre del dispositivo
			/* recorre los parámetros, elimina el valor de cada uno y guarda sus nombres en arreglo de parametros */
			$todo=explode(":",$fila_sample[$i]);
			array_push($param_names,$todo[0]);
		}
		/* $param_names tiene nombres de cada parámetro de los dispositivos. Ej: power, start_time, stop_time */
		/* Crear arreglo con nombres de los parámetros */
		$html="var param_names=[";
		for ($i=0;$i<sizeof($param_names);$i++){
			$html="$html\"".$param_names[$i]."\"";
			if ($i<sizeof($param_names)-1)
				$html="$html,";
		}
		$html="$html];\n";
		/* Crear arreglo con los nombres de los dispositivos*/
		$html="$html var disp_names=[";
		for ($i=0;$i<sizeof($disp_array);$i++){
			$separar=explode(".",$disp_array[$i]);
			$html="$html\"".str_replace("_"," ",$separar[0])."\"";
			if ($i<sizeof($disp_array)-1)
				$html="$html,";
		}
		$html="$html];\n";
		/* Crear matriz bidimensional A[m,n] con m=nro de dispositivo, n=valor del parámetro n*/
		$html="$html var valores=[";
		for ($disp=0;$disp<sizeof($disp_array);$disp++){
			$html=$html."[";
			$line=$disp_array[$disp];
			//$line tiene los datos del dispositivo, de la forma LED_ROJO.power:1.otroparametro:1000
			for ($param=0;$param<sizeof($param_names);$param++){
				$separar=explode(".",$line); //LED_ROJO power:1 otroparametro:1000
				$valores=explode(":",$separar[$param+1]); //power 1
				$valor=$valores[1];
				$html="$html\"$valor\"";
				if ($param<sizeof($param_names)-1)
					$html=$html.",";
			}
			$html=$html."]";
			if ($disp<sizeof($disp_array)-1)
				$html="$html,";
		}
		$html=$html."];\n";
		echo $html;
	}
	$debug = 0;
	?>
<html>
	<head>
		<title>Casa Inteligente</title>
		<meta charset="UTF-8">
		<meta name="description" content="Controle toda su casa desde cualquier parte del mundo!"/>
		<meta name="author" content="Maximiliano Castro"/>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="styles.css" type="text/css">
		<link rel="shortcut icon" href="images/favicon.ico">
		<link rel="stylesheet" href="include/bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="include/TimePicki/css/timepicki.css">
		<script type="text/javascript">
		<?php
			$comando="getDisps";
			$respuesta_shell=$ssh_link->exec("./client $comando");
			echoDisps($respuesta_shell);
		?>
		var frases = [	"Ahora su casa es <strong>más inteligente</strong> que usted...",
						"Lo echamos <strong>mucho</strong> de menos!", 
						"Lorea ta entera <strong>wena</strong> la página!",
						"Al final, Walter White <strong>muere</strong>",
						"( <strong>͡°</strong> ͜ʖ <strong>͡°</strong>)"];
		var fraseindex=0;
		function toggleFrase(){
			var fadespeed=600;
			$("#frases").fadeOut(fadespeed,function (){$("#frases").html(frases[fraseindex]).fadeIn(fadespeed);});
			fraseindex = (fraseindex + 1) % frases.length;
		}
		window.setInterval(toggleFrase, 10000);
		</script>
	</head>
	<body onload="drawTable(); toggleFrase();">
		<div class="container fill">
			<div id="background_filter"></div>
			<div id="screen"></div>
			<div class="col-md-10 col-md-offset-1">
				<div class="row">
					<div class="col-md-12 text-right" id="user_name">
						<div class="col-md-6 col-md-offset-6">
							<div class="col-md-6">¡Bienvenido <strong><?php echo $_SESSION['user_name']?></strong>!</div>
							<div class="col-md-6"><div onclick="location.href='logout.php'" class="boton">Logout</div></div>
						</div>
					</div>
				</div>
				<!-- Fila con el logo -->
				<div class="row">
					<!-- logo -->
					<div class="col-md-2 fila_logo col_izquierda">
						<img class="img-responsive img-rounded" src="images/logo.png" alt="logo.png" id="logo_main">
					</div>
					<!-- frase y adornos -->
					<div class="col-md-6 fila_logo col_derecha"><div id="frases"></div></div>
					<div class="col-md-4 fila_logo imagen_frase"><img id="imagen_frase" src="images/modern_house.jpg" alt="modern_house.jpg"></div>
				</div>	
				<!-- Fila con botones y contenido -->
				<div class="row" id="fila_contenido">
					<!--Botones-->
					<div class="col-md-2 col_izquierda" id="panel_botones">
						<ul class="nav nav-pills nav-stacked" role="tablist">
							<li class="active"><a href="main.php">Dispositivos</a></li>
							<li><a href="#">Cuenta</a></li>
						</ul>
					</div>
					<!--Contenido-->
					<div class="col-md-10 col_derecha" id="panel_contenido">
						<br>
						<div id="divtabla" class="panel panel-default"></div>
						<!-- DIBUJAR TABLA -->
					</div>
				</div>
				<!-- fila para agrandar la página -->
				<div class="row relleno">
					<div class="col-md-2 col_izquierda"  id="fila_agrandar"></div>
					<div class="col-md-10 col_derecha">
						<div id="output"></div>
						<div id="debug"></div>
					</div>
				</div>
			</div>
		</div>
		<!-- JAVASCRIPT -->
			<!--JQUERY-->
			<script src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
			<script src="include/jquery-ui.js"></script>
			<script src="jquery_css.js" type="text/javascript"></script>
			<!--TimePicki-->
			<script src="include/TimePicki/js/timepicki.js"></script>
			<!--BOOTSTRAP-->
			<script src="include/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
			<!--CUSTOM-->
			<script src="main.js" type="text/javascript"></script>
	</body>
</html>
<?php 	
		/*$stmt->close();
		$db_link->close();*/
		?>