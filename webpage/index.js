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
/************** AJAX ****************/
$(document).on('ready', function() {
	/* Act on the event */
	var pet = $('#form').attr('action');
	var met = $('#form').attr('method');
	$('#form').on('submit',function(e){
		/* evitar que se envíe formulario por default */
		e.preventDefault();
		$.ajax({
			beforeSend: function (){
				//$('#errormsg').html("BEFORE SEND!!!!!");
			},
			url: pet,
			type: met,
			data: $('#form').serialize(),
			success: function (respuesta){
				var msg;
				switch(respuesta){
					case "baduser":
						msg="Usuario y/o contraseña incorrectos";
						break;
					case "baddb":
						msg="Error al conectar con base de datos";
						break;
					case "badquery":
						msg="Error al consultar base de datos";
						break;
					case "ok":
						location.href = "main.php";
						break;
					default:
						msg="Error desconocido :O!";
				}
				if (respuesta != "ok") $('#errormsg').html(msg);
				console.log(respuesta);
			},
			error: function (jqXHR, estado, error){
				//$('#errormsg').html("ERROR!!!!!!<br>");	
				console.log(estado);
				console.log(error);
			},
			complete: function(jqXHR, estado){
				//$('#errormsg').html("COMPLETE!!!! <br>");
				console.log(estado);
			},
			timeout: 5000
		})
	})
});