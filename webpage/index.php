<!DOCTYPE html>
<html>
	<head>
		<title>Casa Inteligente</title>
		<meta charset="UTF-8">
		<meta name="description" content="Controle toda su casa desde cualquier parte del mundo!"/>
		<meta name="author" content="Maximiliano Castro"/>
		<link rel="shortcut icon" href="http://sstatic.net/stackoverflow/img/favicon.ico">
		<!--<script language="javascript" src="scripts.js" type="text/javascript"></script>-
		<script language="javascript" src="index.js" type="text/javascript"></script>-->
		<link rel="stylesheet" type="text/css" href="styles.css">
		<script type="text/javascript">
			function clearError(){
				document.getElementById("errormsg").innerHTML="";
			}
			function writeError(errno){
				var div=document.getElementById("errormsg");
				switch (errno){
					case 1: 
						div.innerHTML="Usuario y/o contraseña incorrectos";
						break;
					//Agregue más mensajes de error aquí
				}
			}
		</script>
	</head>
	<body>
		<div id="div_body">
			<div id="barra_recuadro">
				<div id="recuadro">
					<h1>Inicio de sesión</h1>
					<form method="POST" enctype="multipart/form-data" action="main.php" id="form">
						<table>
							<tr>
								<td><label for="user">Usuario: </label></td><td><input id="user" name="user" type="text" class="text_input" onclick="clearError();"></td>
							</tr>
							<tr>
								<td><label for="pass">Contraseña: </label></td><td><input id="pass" name="pass" type="password" class="text_input" onclick="clearError();"></td>
							</tr>
						</table>
						<br>
						<input type="submit" value="Ingresar" id="boton_ingresar" class="boton">
					</form>
					<br><br>
					<div id="errormsg">
						<?php 
							if (isset($_GET['msg'])) 
							//echo "Usuario y/o contraseña incorrectos"
								echo "<script type='text/javascript'>\nwriteError(".$_GET['msg'].");</script>";
						?></div>
				</div>
			</div>
		</div>
	</body>
</html>

<!-- Proceso de comunicación:
		1) Pedir usuario
		2) Confirmar usuario consultando base de datos (o archivo)
		3) Comunicar con el cliente de la casa del usuario
		4) Pedir información de dispositivos del usuario (dispositivos, status)
		5) Dibujar interfaz de acuerdo a la información obtenida
-->