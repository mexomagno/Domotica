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
?>
<html>
	<head>
		<title>Casa Inteligente</title>
		<meta charset="UTF-8">
		<meta name="description" content="Controle toda su casa desde cualquier parte del mundo!"/>
		<meta name="author" content="Maximiliano Castro"/>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="shortcut icon" href="images/favicon_jake.ico">
		<link rel="stylesheet" href="include/bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" href="styles.css" type="text/css">
	</head>
	<body>
		<div id="background_filter"></div>
		<div id="screen"></div>
		<div class="container container_agregarcliente">
			<div class="row">
				<div class="col-md-3 col-md-offset-5">
					<div onclick="location.href='logout.php'" class="boton">Logout</div>
					<br><br>
				</div>
			</div>
			<div class="row">
				<div class="col-md-4 col-md-offset-4 ">
					<!--form[action="sendUser();"]>table>(tr>(td>label[for="input$"])+(td>input[type="text" id="input$"]))*9-->
					<form action="sendUser();">
						<table>
							<tr>
								<td><label for="input1">Nick user:</label></td>
								<td><input type="text" id="input1"></td>
							</tr>
							<tr>
								<td><label for="input2">Pass user:</label></td>
								<td><input type="text" id="input2"></td>
							</tr>
							<tr>
								<td><label for="input3">Repita pass:</label></td>
								<td><input type="text" id="input3"></td>
							</tr>
							<tr>
								<td><label for="input4">Hostname:</label></td>
								<td><input type="text" id="input4"></td>
							</tr>
							<tr>
								<td><label for="input5">Puerto:</label></td>
								<td><input type="text" id="input5"></td>
							</tr>
							<tr>
								<td><label for="input6">Host normal user:</label></td>
								<td><input type="text" id="input6"></td>
							</tr>
							<tr>
								<td><label for="input7">Host normal pass:</label></td>
								<td><input type="text" id="input7"></td>
							</tr>
							<tr>
								<td><label for="input8">Host super user:</label></td>
								<td><input type="text" id="input8"></td>
							</tr>
							<tr>
								<td><label for="input9">Host super pass:</label></td>
								<td><input type="text" id="input9"></td>
							</tr>
						</table>
						<input type="submit" value="Añadir" id="boton_añadir" class="boton">
					</form>
				</div>
			</div>

		</div>
		<!-- JAVASCRIPT -->
			<!--JQUERY-->
			<script src="include/jquery-1.10.2.js"></script>
			<script src="include/jquery-ui.js"></script>
			<script src="jquery_css.js" type="text/javascript"></script>
			<!--BOOTSTRAP-->
			<script src="include/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
			<!--CUSTOM-->
			<script>
				/****** AJAX ******/
				function sendUser(){
					$.ajax({
						beforeSend: function(){

						},
						url: "",
						type: "POST",
						data: "",
						success: function (respuesta){

						},
						error: function(jqXHR, estado, error){

						},
						complete: function(jqXHR, estado){

						},
						timeout: 10000
					});
				}
			</script>
	</body>
</html>