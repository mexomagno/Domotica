<?php
	if (!isset($_POST['user'])||!isset($_POST['pass'])){
		header("Location: index.php");
		exit();
	}
/*************** FUNCIONES ***************/
	function backLink(){
		//echo "<br><br><a href='index.php'>Volver</a>";
		exit(1);
	}
	function debug($str){
		$debug=0;
		if ($debug){
			echo $str;
			//backLink();
		}
	}
	function error($str){
		echo $str;
		backLink();
	}
/**************** CÓDIGO *****************/
	require("credenciales.php");
	$db_link=new mysqli($db_host, $db_user, $db_pass,$db_name);
	if ($db_link->connect_errno) {
    	error("baddb");/*"Error al conectar con MySQL: (" . $db_link->connect_errno . ") " . $db_link->connect_error);*/
	}
	else{
		debug("Conectado a la base de datos<br>");
		$db_link->set_charset("utf8");
	}
	/* verificar usuario */
	if (!($stmt = $db_link->prepare("SELECT nombre_usuario, password
									 FROM usuarios_web
									 WHERE nombre_usuario=? AND password=? ")))
    	error("badquery"); /*"Error al preparar consulta: (" . $db_link->errno . ") " . $db_link->error;*/
	$stmt->bind_param('ss',$user, $pass);
	$user=strtolower($_POST['user']);
	$pass=strtolower($_POST['pass']);
	$stmt->execute();
	$stmt->bind_result($user_nombre, $user_pass);
	$stmt->store_result();
	$filas=$stmt->num_rows;
	$stmt->fetch();
	if ($filas==0)
		error("baduser");
	/* Usuario autenticado */
	if (!isset($_SESSION)){
		//session_write_close();
		session_start();
		$_SESSION['user_name']=$user_nombre;
	}
	echo "ok";
	$stmt->close();
	$db_link->close();
?>