<?php
	if (!isset($_SESSION))
		session_start();
	//echo $_POST['disp'].", ".$_POST['param'].", ".$_POST['state'];
	//echo "LED_AZUL_CHICO.power:0 LED_AZUL_LARGO.power:0";
	/****** ETAPA 1: validaciones internas ******/
	/* 1: validar dispositivo */
	if ((!isset($_POST['disp']))||(!isset($_POST['param']))||(!isset($_POST['state']))){
		if (!isset($_SESSION['getDisps'])){
			echo "Ingreso ilegal";
			header("Location: main.php"); //Si no habia iniciado sesion, main.php lo envía a index.php
			exit(1);
		}
	}
	if (isset($_SESSION['getDisps'])){
		$disp="getDisps";
		$param="";
		$value="";
		unset($_SESSION['getDisps']);
	}
	else{
		$disp=intval($_POST['disp']);
		/* 2: validar parametro */
		$param=$_POST['param'];
		switch ($param){
			case '0':
				$param="power";
				break;
			default:
				exit("Error (web):Parámetro para el dispositivo no es válido");
		}
		/* 3: validar estado*/
		switch ($_POST['state']){
			case '1':
				$value="ON";
				break;
			case '0':
				$value="OFF";
				break;
			default:
				exit("Error (web): Estado para el dispositivo no es válido");
		}
	}
	/***** ETAPA 2: enviar solicitud ssh *****/
	set_include_path('include/phpseclib');
	include('Net/SSH2.php');
	$dom_hostname=$_SESSION['hostname'];
	$dom_puerto=$_SESSION['puerto'];
	$dom_usuario=$_SESSION['usuario'];
	$dom_password=$_SESSION['password'];
	$ssh_link = new Net_SSH2($dom_hostname,$dom_puerto);
	if (!$ssh_link->login($dom_usuario, $dom_password)) {
		exit ("<script>alert('El servidor de domótica no responde');</script>");
	}
	$comando = "$disp $param $value";
	$respuesta_shell=$ssh_link->exec("./client $comando");
	if (strstr($respuesta_shell, "Error")){
		exit("Error (web): no se pudo enviar comando al Raspberry: ".$respuesta_shell);
	}
	/* En un futuro, fijar un caracter en el string que indique error */
	/*if ($respuesta_shell != "OK"){
		exit("Error (web): Algo rarísimo pasó. Respuesta shell: $respuesta_shell");
	}*/
	echo "";
?>