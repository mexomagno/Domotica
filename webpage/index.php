<!DOCTYPE html>
<?php
	session_start();
	if (isset($_SESSION['user_name'])){
		header("Location: main.php");
	}
?>
<html>
	<head>
		<title>Casa Inteligente</title>
		<meta charset="UTF-8">
		<meta name="description" content="Controle toda su casa desde cualquier parte del mundo!"/>
		<meta name="author" content="Maximiliano Castro"/>
		<link rel="shortcut icon" href="images/favicon.ico">
		<link rel="stylesheet" type="text/css" href="styles.css">
	</head>
	<body>
		<div id="background_filter"></div>
		<div id="screen"></div>
		<div id="barra_recuadro_index">
			<div id="recuadro_index">
				<img id="index_logo" src="images/logo.png" alt="index_logo.png">
				<h1>Inicio de sesión</h1>
				<form method="POST" enctype="multipart/form-data" action="login.php" id="form">
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
				<div id="errormsg"></div>
			</div>
		</div>
		<!-- JAVASCRIPT -->
		<!-- JQUERY para animaciones -->
		<script src="include/jquery-1.10.2.js"></script>
		<script src="include/jquery-ui.js"></script>
		<script src="jquery_css.js" type="text/javascript"></script>
		<script src="index.js" type="text/javascript"></script>
	</body>
</html>

<!-- Proceso de comunicación:
		1) Pedir usuario
		2) Confirmar usuario consultando base de datos (o archivo)
		3) Comunicar con el cliente de la casa del usuario
		4) Pedir información de dispositivos del usuario (dispositivos, status)
		5) Dibujar interfaz de acuerdo a la información obtenida
-->