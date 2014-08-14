<?php
	require("credenciales.php");
	$db_link=new mysqli($db_host, $db_user, $db_pass,$db_name);
		if ($db_link->connect_errno) {
	    	echo "Error al conectar con MySQL: (" . $db_link->connect_errno . ") " . $db_link->connect_error;
	    	sleep(5000);
	    	header("Location: logout.php");
		}
		else{
			$db_link->set_charset("utf8");
		}
?>