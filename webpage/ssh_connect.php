<?php
	set_include_path('include/phpseclib');
	include('Net/SSH2.php');
	$conexion = new Net_SSH2($dom_hostname,$dom_puerto);
	if (!$conexion->login($dom_usuario, $dom_password)){
		echo "<script>alert('El servidor de domótica no responde');</script>";
		//sleep(5000);
		//header("Location: logout.php");
	}
	$_SESSION['ssh_connection']=$conexion;
?>