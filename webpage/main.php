<!DOCTYPE html>
<?php session_start(); ?>
<html>
	<head>
		<title>Casa Inteligente</title>
		<meta charset="UTF-8">
		<link rel="stylesheet" href="styles.css" type="text/css">
<?php
	function backLink(){
		echo "<br><br><a href='index.php'>Volver</a>";
		exit(1);
	}
	// function drawTable($respuesta_shell){
	// 	$disp_array=explode(" ", trim($respuesta_shell));
	// 	/* obtener parámetros configurables hasta ahora, es decir power, tiempo inicio quizás, etc.*/
	// 	$fila_sample=explode(".",$disp_array[0]);
	// 	$param_names=array();
	// 	for ($i=1;$i<sizeof($fila_sample);$i++){ //comienza en 1 porque 0 es el nombre del dispositivo
	// 		/* recorre los parámetros, elimina el valor de cada uno y guarda sus nombres en arreglo de parametros */
	// 		$todo=explode(":",$fila_sample[$i]);
	// 		array_push($param_names,$todo[0]);
	// 	}
	// 	/* $param_names tiene nombres de cada parámetro de los dispositivos. Ej: power */
	// 	/* Fila 1: Nombre dispositivo, Power, etc */
	// 	$html="<table><tr>\n";
	// 	$html="$html<td>Nombre Dispositivo</td>";
	// 	for ($i=0;$i<sizeof($param_names);$i++){
	// 		$html="$html<td>".$param_names[$i]."</td>";
	// 	}
	// 	$html="$html</tr>";
	// 	/* Fila N: Nompre dispositivo, Botón power, editar */
	// 	for ($i=0;$i<sizeof($disp_array);$i++){
	// 		$html="$html<tr>";
	// 		$fila=explode(".",$disp_array[$i]);
	// 		for ($j=0;$j<sizeof($fila);$j++){
	// 			$texto=explode(":",$fila[$j]);
	// 			$first=$texto[0];
	// 			//	$html="$html<td>".str_replace("_", " ", $first)."</td>";
	// 			switch ($first){
	// 				case "power":
	// 					$html="$html<td><input type='image' src='images/switch_".$texto[1].".png' alt='switch_".$texto[1].".png' onclick='' /></td>";
	// 					break;
	// 				default:
	// 					$html="$html<td>".str_replace("_", " ", $first)."</td>";
	// 			}
	// 		}
	// 		$html="$html</tr>";
	// 	}

	// 	$html="$html</tr>\n</table>\n";
	// 	return $html;
	// }
	/* echoDisps escribe texto que será procesado por javascript. Crea arreglos con los datos necesarios para que javascript sea
	capaz de dibujar la tabla de dispositivos */
	function echoDisps($respuesta_shell){
		$disp_array=explode(" ", trim($respuesta_shell));
		/* obtener parámetros configurables hasta ahora, es decir power, tiempo inicio quizás, etc.*/
		$fila_sample=explode(".",$disp_array[0]);
		$param_names=array();
		for ($i=1;$i<sizeof($fila_sample);$i++){ //comienza en 1 porque 0 es el nombre del dispositivo
			/* recorre los parámetros, elimina el valor de cada uno y guarda sus nombres en arreglo de parametros */
			$todo=explode(":",$fila_sample[$i]);
			array_push($param_names,$todo[0]);
		}
		/* $param_names tiene nombres de cada parámetro de los dispositivos. Ej: power */
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
				$html=$html.$valor;
				if ($param<sizeof($param_names)-1)
					$html=$html.",";
			}
			//val valores = [[1],[0],[1]];
			$html=$html."]";
			if ($disp<sizeof($disp_array)-1)
				$html="$html,";
		}
		$html=$html."];\n";
		echo $html;
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
	<!--<script type="text/javascript" src="jquery-2.1.1.js"></script>-->
	<script type="text/javascript">
		function enviar(dispositivo, parametro, estado) {
			//recibe input del tipo 0,power,ON
			output=document.getElementById("output");
			output.innerHTML = "Esperando respuesta...<img src='images/loading2.gif' width='70% 'alt='loading2.gif'>";
			input=document.getElementById("input");
			getRequest(
		    	<?php echo "\"ssh_request.php?user=$user_id&disp=\""; ?>.concat(dispositivo).concat("&param=").concat(parametro).concat("&value=").concat(estado), // URL for the PHP file
		    	drawOutput,  // handle successful request
		    	drawError,    // handle error
		    	dispositivo, parametro, estado
			);

			return false;
		}  
		// handles drawing an error message
		function drawError () {
		    var container = document.getElementById('output');
		    container.innerHTML = 'Bummer: there was an error!';
		}
		// handles the response, adds the html
		function drawOutput(responseText, dispositivo, parametro, estado) {
		    var container = document.getElementById('output');
		    container.innerHTML = responseText;
		    if (responseText == ""){
		    	//operación exitosa: actualizar variables
		    	switch (parametro){
		    		case 0: //power. Hay que setearlo en 0 si era 1, y en 1 si era 0.
		    			valores[dispositivo][parametro]= estado;
		    	}
		    	//actualizar la tabla
		    	var divtabla = document.getElementById("divtabla");
		    	var tabla = document.getElementById("tabla");
		    	var thead = document.getElementById("thead");
		    	var header = document.getElementById("tabla_header");
		    	thead.removeChild(header);
		    	var tbody = document.getElementById("tbody");
		    	var filas = document.getElementsByClassName("tabla_fila");
		    	for (var i=0;i<filas.length;i++){
		    		tbody.removeChild(filas[i]);
		    	}
		    	tabla.removeChild(thead);
		    	tabla.removeChild(tbody);
		    	divtabla.removeChild(tabla);
		    	drawTable(); //POSIBLEMENTE PODRÍA MEJORARSE ESTO REFRESCANDO SOLO LA FILA INVOLUCRADA Y NO TODA LA TABLA
		    }
		}
		function getRequest(url, success, error, dispositivo, parametro, estado) {
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
		                success(req.responseText, dispositivo, parametro, estado) : error(req.status)
		            ;
		        }
		    }
		    req.open("GET", url, true);
		    req.send(null);
		    return req;
		}
	</script>
	</head>
	<body onload="drawTable();">
	<div id="body_wrap">
	<?php
		if (!isset($_SESSION['user_id']))
			$_SESSION['user_id']=$user_id;
		if ($debug) echo "sesion: ".session_id();
		echo "<h2>Bienvenido/a, $user_nombre!</h2>
		<p>Su dirección es: $user_direccion.</p>";
?>		<p>Testeando control remoto</p>
		<div id="divtabla"></div>
<?php 	/* generar botones de control */
		set_include_path('phpseclib');
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
			$respuesta_shell=$conexion->exec("./client $comando");
			//echo $respuesta_shell;
			if ((strstr($respuesta_shell, "Error"))||(strstr($respuesta_shell, "bash"))){
				echo "Error (web): cliente no disponible: '$respuesta_shell'";
			}
			else{
				/* si se está acá, se recibió los nombres de los dispositivos.*/
				/* Generar tabla con los elementos disponibles.*/
				?><script type='text/javascript'><?php
				/* crear arreglos con nombres de dispositivos, parámetros ajustables y valores de parámetros para cada dispositivo*/
				echoDisps($respuesta_shell);
				?>
				//Espacio para los scripts
				function drawTable(){
					//obtener div donde irá la tabla
					var divdebug=document.getElementById("debug");
					var divtabla=document.getElementById("divtabla");
					var tabla=document.createElement("table");
					tabla.setAttribute("id","tabla");
					var thead = document.createElement("thead");
					thead.setAttribute("id","thead");
					var tabla_header=document.createElement("tr");
					tabla_header.innerHTML="<td class='th_switches'>Nombre Dispositivo</td>"
					for (var param=0;param<param_names.length;param++){
						tabla_header.innerHTML=tabla_header.innerHTML+"<td class='th_switches'>"+param_names[param]+"</td>";
					}
					tabla_header.setAttribute("id","tabla_header");
					thead.appendChild(tabla_header);
					tabla.appendChild(thead);
					var tbody = document.createElement("tbody");
					tbody.setAttribute("id","tbody");
					/* agregar filas según cuántos elementos haya */
					for (var i=0;i<disp_names.length;i++){
						var fila=document.createElement("tr");
						fila.innerHTML+="<td class='td_switches'>"+disp_names[i]+"</td>";
						for (var j=0; j<param_names.length; j++){
							var texto="";
							switch (param_names[j]){
								case "power":
									texto="<td class='td_switches'><input type='image' id='switch' width='95%' src='images/switch_"+valores[i][j]+".png' alt='switch_"+valores[i][j]+".png' onclick='enviar("+i+","+0+","+(valores[i][j]==0?1:0)+");' /></td>";
									break;
								default:
									texto="<td class='td_switches'>"+valores[i][j]+"</td>";
							}
							fila.innerHTML+=texto;
						}
						fila.setAttribute("class","tabla_fila");
						tbody.appendChild(fila);
					}
					tabla.appendChild(tbody);
					divtabla.appendChild(tabla);
				}
				</script><?php
			}
		}
	}// fin del else que permite meterse a la sesión
		?>
		<br><div id="output"></div>
		<div id="debug"></div>
		<br><a href="logout.php" class="boton">Logout</a>
	</div>	
	</body>
</html>
<?php 	
		$stmt->close();
		$db_link->close();
		?>