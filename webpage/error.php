<!DOCTYPE html>
<html>
	<head>
		<title>Error</title>
		<meta charset="UTF-8">
		<meta name="description" content="Controle toda su casa desde cualquier parte del mundo!"/>
		<meta name="author" content="Maximiliano Castro"/>
		<link rel="stylesheet" href="styles.css" type="text/css">
		<link rel="shortcut icon" href="images/favicon.ico">

	</head>
	<body>
		<div class="container">
			<div id="background_filter"></div>
			<div id="screen"></div>
			<div id="barra_recuadro_index">
				<h1><?php
				if (isset($_GET['errno'])){
					$errno=$_GET['errno'];
				}
				else $errno=-1;
				switch ($errno){
					case 400:
						echo "Bad Request";
						break;
					case 401:
						echo "Unauthorized";
						break;
					case 403:
						echo "Forbidden";
						break;
					case 404:
						echo "Not Found";
						break;
					case 500:
						echo "Internal Server Error";
						break;
					default:
						header("Location: error.php?errno=404");
				}
			?></h1>
				<a href="../" class="boton">Inicio</a>
			</div>
		</div>
		<!-- JAVASCRIPT -->
		<script src="include/jquery-1.10.2.js"></script>
		<script src="include/jquery-ui.js"></script>
		<script src="jquery_css.js" type="text/javascript"></script>
	</body>
</html>