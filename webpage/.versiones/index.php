<!DOCTYPE html>
<html>
	<head>
		<title>Casa Inteligente</title>
		<meta charset="UTF-8">
		<meta name="description" content="Controle toda su casa desde cualquier parte del mundo!"/>
		<meta name="author" content="Maximiliano Castro"/>
		<!--<script language="javascript" src="scripts.js" type="text/javascript"></script>-
		<script language="javascript" src="index.js" type="text/javascript"></script>-->
	</head>
	<body>
		<h1>Inicio de sesión</h1>
		<form method="POST" enctype="multipart/form-data" action="main.php">
			<table>
				<tr>
					<td><label for="user">Usuario: </label></td><td><input id="user" name="user" type="text"></td>
				</tr>
				<tr>
					<td><label for="pass">Contraseña: </label></td><td><input id="pass" name="pass" type="password"></td>
				</tr>
				<tr>
					<td><input type="submit" value="Ingresar"></td>
				</tr>
			</table>
		</form>
		<div id="errormsg"><?php if (isset($_GET['msg']) && $_GET['msg']==1) echo "Usuario y/o contraseña incorrectos"?></div>
	</body>
</html>

<!-- Proceso de comunicación:
		1) Pedir usuario
		2) Confirmar usuario consultando base de datos (o archivo)
		3) Comunicar con el cliente de la casa del usuario
		4) Pedir información de dispositivos del usuario (dispositivos, status)
		5) Dibujar interfaz de acuerdo a la información obtenida
-->