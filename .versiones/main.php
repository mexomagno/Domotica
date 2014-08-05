<!DOCTYPE html>
<?php session_start(); ?>
<html>
	<head>
		<title>Casa Inteligente</title>
		<meta charset="UTF-8">
<?php
	function backLink(){
		echo "<br><br><a href='index.php'>Volver</a>";
		exit(1);
	}
	$debug = 0;
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
	if (!($stmt = $db_link->prepare("SELECT nombre, direccion, casa_id,server_host, server_username, server_pass, server_port
									 FROM usuarios, credenciales
									 WHERE credenciales.username=? AND credenciales.server_pass=? AND credenciales.casa_id = usuarios.id")))
	{
    	echo "Error al preparar consulta: (" . $db_link->errno . ") " . $db_link->error;
    	exit(1);
	}
	$stmt->bind_param('ss',$user, $pass);
	if (!isset($_POST['user'])){
		header("Location: index.php");
		exit(1);
	}
	$user=strtolower($_POST['user']);
	$pass=strtolower($_POST['pass']);
	$stmt->execute();
	$stmt->bind_result($user_nombre, $user_direccion, $user_id, $user_server_host, $user_server_username, $user_server_pass, $user_server_port);
	$stmt->store_result();
	$filas=$stmt->num_rows;
	$stmt->fetch();
	if ($filas==0){
		/* Usuario o contraseña incorrectos */
		header("Location: index.php?msg=1");
		exit(1);
	}
	else{
?>	
	<script type="text/javascript" src="jquery-2.1.1.js"></script>
	<script type="text/javascript">
		function enviar(dispositivo, parametro, estado) {
			output=document.getElementById("output");
			output.innerHTML = "Esperando respuesta...";
			input=document.getElementById("input");
			//params=input.value;
			//input.value="";
			getRequest(
		    	<?php echo "\"ssh_request.php?user=$user_id&disp=\""; ?>.concat(dispositivo).concat("&param=").concat(parametro).concat("&state=").concat(estado), // URL for the PHP file
		    	drawOutput,  // handle successful request
		    	drawError    // handle error
			);
			return false;
		}  
		// handles drawing an error message
		function drawError () {
		    var container = document.getElementById('output');
		    container.innerHTML = 'Bummer: there was an error!';
		}
		// handles the response, adds the html
		function drawOutput(responseText) {
		    var container = document.getElementById('output');
		    container.innerHTML = responseText;
		}
		function getRequest(url, success, error) {
		    var req = false;
		    try{
		        // most browsers
		        req = new XMLHttpRequest();
		    } catch (e){
		        // IE
		        try{
		            req = new ActiveXObject("Msxml2.XMLHTTP");
		        } catch (e) {
		            // try an older version
		            try{
		                req = new ActiveXObject("Microsoft.XMLHTTP");
		            } catch (e){
		                return false;
		            }
		        }
		    }
		    if (!req) return false;
		    if (typeof success != 'function') success = function () {};
		    if (typeof error!= 'function') error = function () {};
		    req.onreadystatechange = function(){
		        if(req .readyState == 4){
		            return req.status === 200 ? 
		                success(req.responseText) : error(req.status)
		            ;
		        }
		    }
		    req.open("GET", url, true);
		    req.send(null);
		    return req;
		}
	</script>
	<script type="text/javascript">
		function sendRequest(){
			$.post("ssh_request.php")
		}
	</script>
	</head>
	<body>
	<?php
		if (!isset($_SESSION['user_id']))
			$_SESSION['user_id']=$user_id;
		if ($debug) echo "sesion: ".session_id();
		echo "<h2>Bienvenido/a, $user_nombre!</h2>
		<p>Su dirección es: $user_direccion.</p>";
?>		<p>Testeando control remoto</p>
<?php 	/* generar botones de control */
		set_include_path(get_include_path().'/phpseclib');
		include('Net/SSH2.php');
		$conexion = new Net_SSH2($user_server_host,$user_server_port);
		if (!$conexion->login($user_server_username, $user_server_pass)){
			echo("Error (web): No se pudo establecer conexión con el servidor");
		}
		else{
			/****** ETAPA 3: enviar comando al arduino, cumpliendo protocolo ******/
			$comando = "getDisps";
			// $ruta_arduino= "/dev/serial/by-id/".$conexion->exec("ls /dev/serial/by-id/ | grep arduino");
			// if ($debug) echo "ruta al arduino: ".$ruta_arduino."<br>";
			// /* validar que arduino está conectado */
			// if (!(strrpos($ruta_arduino, "no se puede acceder a")===false)){
			// 	exit("Error (web): Arduino no está disponible");
			// }
			/* enviar comando al arduino */

			// ESTE DIRECTORIO DEBIERA SER DEPENDIENTE DEL NOMBRE DE USUARIO. EJEMPLO, CARPETA PERSONAL DEL COMPUTADOR.
			$respuesta_shell=$conexion->exec("cd /var/www/domotica/code; ./client $comando");
			//echo $respuesta_shell;
			if ((strstr($respuesta_shell, "Error"))||(strstr($respuesta_shell, "bash"))){
				echo "Error (web): cliente no disponible: '$respuesta_shell'";
			}
			else{
			/* si se está acá, se recibió los nombres de los dispositivos.*/
			/* Generar tabla con los elementos disponibles.*/
			$html="<table><tr>\n";
			$disp_array=explode(" ", trim($respuesta_shell));
			/* obtener parámetros configurables hasta ahora, es decir power, tiempo inicio quizás, etc.*/
			$fila_sample=explode(".",$disp_array[0]);
			$param_names=array();
			for ($i=1;$i<sizeof($fila_sample);$i++){
				/* recorre los parámetros, elimina el valor de cada uno y guarda sus nombres en arreglo de parametros */
				$todo=explode(":",$fila_sample[$i]);
				array_push($param_names,$todo[0]);
			}
			print_r($param_names);
			for ($i=0;$i<sizeof($disp_array);$i++){
				$html= "$html<td><div>".$disp_array[$i]."</div></td>\n";
			}
			$html= "$html</tr><tr>\n";
			for ($i=0;$i<sizeof($disp_array);$i++){
				$html="$html<td><button onclick='enviar($i, \"power\", \"ON\")'><h3>ON</h3></button></td>\n";
			}
			$html = "$html</tr><tr>\n";
			for ($i=0;$i<sizeof($disp_array);$i++){
				$html= "$html<td><button onclick='enviar($i, \"power\", \"OFF\")'><h3>OFF</h3></button></td>\n";
			}
			$html="$html</tr>\n</table>\n";
			echo $html;
		}
		}
	}// fin del else que permite meterse a la sesión
		?>
		<br><div id="output"></div>
		<br><a href="logout.php">Logout</a>
		
	</body>
</html>
<?php 	
		$stmt->close();
		$db_link->close();
		?>