function mayus(string){
	return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}
function mayusCadaLetra(str){
	return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}
function drawTable(){
	//obtener div donde irá la tabla
	var divdebug=document.getElementById("debug");
	var divtabla=document.getElementById("divtabla");
	var tabla=document.createElement("table");
	tabla.setAttribute("class","table table-striped table-condensed");
	tabla.setAttribute("id","tabla");
	var thead = document.createElement("thead");
	thead.setAttribute("id","thead");
	var tabla_header=document.createElement("tr");
	tabla_header.innerHTML="<th class='th_switches'>Nombre Dispositivo</th>"
	for (var param=0;param<param_names.length;param++){
		tabla_header.innerHTML=tabla_header.innerHTML+"<th class='th_switches'>"+mayus(param_names[param])+"</th>";
	}
	tabla_header.setAttribute("id","tabla_header");
	thead.appendChild(tabla_header);
	tabla.appendChild(thead);
	var tbody = document.createElement("tbody");
	tbody.setAttribute("id","tbody");
	/* agregar filas según cuántos elementos haya */
	for (var i=0;i<disp_names.length;i++){
		var fila=document.createElement("tr");
		fila.innerHTML+="<td class=''>"+mayusCadaLetra(disp_names[i])+"</td>";
		for (var j=0; j<param_names.length; j++){
			var texto="";
			switch (param_names[j]){
				case "power":
					texto="\
					<td class='switch_container'>\
						<div id='switch_"+i+"' class='switch switch_"+valores[i][j]+"' \
						onclick='enviarSSH("+i+","+0+","+(valores[i][j]==0?1:0)+");'></div>\
						<div class='loading_icon' id='loading_icon_"+i+"' style='display:none;'></div>\
					</td>";
					break;
				default:
					texto="<td class='td_switches'>"+valores[i][j]+"</td>";
			}
			fila.innerHTML+=texto;
		}
		fila.setAttribute("class","tabla_fila");
		tbody.appendChild(fila);
	}
	tabla.appendChild(tbody);
	divtabla.appendChild(tabla);
}
/**************** AJAX ****************/
function enviarSSH(dispositivo, parametro, estado){
	var datos={disp:dispositivo, param:parametro, state:estado};
	var fade_speed=200;
	$.ajax({
		beforeSend: function (){
			//$('#errormsg').html("BEFORE SEND!!!!!");
			$('#switch_'+dispositivo).fadeOut(fade_speed,function(){$('#loading_icon_'+dispositivo).fadeIn(fade_speed);});
		},
		url: "ssh_request2.php",
		type: "POST",
		data: datos,
		success: function (respuesta){
			var msg;
			switch(respuesta){
				case "":
					msg="Respuesta correcta. "+respuesta;
					var prev_state;
					switch (parametro){
			    		case 0: //power. Hay que setearlo en 0 si era 1, y en 1 si era 0.
			    			prev_state=valores[dispositivo][parametro];
			    			valores[dispositivo][parametro]= estado;
			    	}
			    	var newonclick="enviarSSH("+dispositivo+","+parametro+","+prev_state+");";
					$("#switch_"+dispositivo).removeClass('switch_'+prev_state).addClass('switch_'+estado).attr("onclick",newonclick);
					break;
				default:
					msg="Respuesta mala: "+respuesta;
			}
			$('#output').html(msg);
			$('#loading_icon_'+dispositivo).fadeOut(fade_speed,function(){$('#switch_'+dispositivo).fadeIn(fade_speed);});
			console.log("ssh_request2: "+respuesta);
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
		timeout: 10000
	});
}