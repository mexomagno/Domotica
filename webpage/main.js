function mayus(string){
	return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}
function mayusCadaLetra(str){
	return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}
function text2hora(str){
	aux=str;
	aux=aux.substr(str.indexOf("-")+1);
	array=aux.split("-");
	if (array[0]=="NULL")
		return [-1,-1,-1];
	else{
		n_array=[Number(array[0]),Number(array[1]),Number(array[2])];
		//return ""+(n_array[0] <= 9 ? "0" : "")+n_array[0]+":"+(n_array[1] <= 9 ? "0" : "")+n_array[1]+":"+(n_array[2] <= 9 ? "0" : "")+n_array[2];
		return n_array;
	}
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
		switch (param_names[param]){
			case "power":
				tabla_header.innerHTML+="<th class='th_switches'>"+"ON/OFF"+"</th>";
				break;
			case "start_time":
				tabla_header.innerHTML+="<th class='th_switches' style='display: none;'>"+mayus("horarios")+"</th>";
				break;
			case "stop_time":
				break;
		}
		//tabla_header.innerHTML=tabla_header.innerHTML+"<th class='th_switches'>"+mayus(param_names[param])+"</th>";
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
				case "start_time":
					horarios_ini=valores[i][param_names.findIndex(function(elem){return elem == "start_time";})].split(",");
					horarios_fin=valores[i][param_names.findIndex(function(elem){return elem == "stop_time";})].split(",");
					list_html="<div style='display: none;'>";
					for (var k=0;k<horarios_ini.length;k++){
						horaini=text2hora(horarios_ini[k]);
						horafin=text2hora(horarios_fin[k]);
						list_html+="<div class='time_element filas_h fila_h"+(k+1)+"' disabled='true'>Horario "+(k+1)+":";
						list_html+="<div><div>\
										ON <input type='text' class=' time_element hora_texto tf_ on"+(k+1)+"' value='--:--' disabled='true'>\
										OFF<input type='text' class=' time_element hora_texto tf_ off"+(k+1)+"' value='--:--' disabled='true'></div>\
											<input type='checkbox' id='chb_h"+(k+1)+"' onclick='enableHorario(\""+(k+1)+"\")'></div>";
						/*list_html+="ON <input class='hora_num' type='number' min='0' max='23' value='"+horaini[0]+"'>:\
											<input class='hora_num' type='number' min='0' max='59' value='"+horaini[1]+"'>:\
											<input class='hora_num' type='number' min='0' max='59' value='"+horaini[2]+"'>\
										 OFF <input class='hora_num' type='number' min='0' max='23' value='"+horafin[0]+"'>:\
										 	<input class='hora_num' type='number' min='0' max='59' value='"+horafin[1]+"'>:\
										 	<input class='hora_num' type='number' min='0' max='59' value='"+horafin[2]+"'>\
										 	<input class='hora_num' type='checkbox'></div>";/*+text2hora(horarios_ini[k])+" | OFF "+text2hora(horarios_fin[k]);*/
					
						list_html+="</div>";
					}
					list_html+="<div class='boton' style='display: inline-flex;' onclick='enviarHorarios("+i+")' disabled='false'>Enviar</div>";
					texto="<td class='td_horarios'>"+list_html+"</td>";
					break;
				case "stop_time":
					break;
				/* #paramdef: agregue parámetros*/
				default:
					texto="<td class='td_switches'>"+valores[i][j]+"</td>";
			}
			fila.innerHTML+=texto;
		}
		//fila.setAttribute("class","tabla_fila");
		tbody.appendChild(fila);
		//tabla.appendChild(fila);
	}
	tabla.appendChild(tbody);
	divtabla.appendChild(tabla);
	//Dibujar horarios
	retrieveHorarios();
	$(".time_element").timepicki();
}
/**************** AJAX ****************/
function enviarSSH(dispositivo, parametro, estado){
	var datos={disp:dispositivo, param:parametro, state:estado};
	var fade_speed=200;
	$.ajax({
		beforeSend: function (){
			//$('#errormsg').html("BEFORE SEND!!!!!");
			/* desactivar switch, animar fade out y fade in para gif de loading */
			$('#switch_'+dispositivo).attr("onclick","").fadeOut(fade_speed,function(){$('#loading_icon_'+dispositivo).fadeIn(fade_speed);});
		},
		url: "ssh_request2.php",
		type: "POST",
		data: datos,
		success: function (respuesta){
			var msg;
			switch(respuesta){
				case "":
					msg=""+respuesta;
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
function enableHorario(num){
	checkbox = document.getElementById("chb_h"+num);
	//fila = document.getElementById("fila_h"+num);
	if (checkbox.checked){
		//document.getElementById("debug").innerHTML="CHECKED: "+num;
		$('.tf_'+num).attr('disabled', false).attr('value','00:00 am');
		$('.fila_h'+num).attr('disabled',false);
	}
	else{
		//document.getElementById("debug").innerHTML="UNCHECKED: "+num;
		$('.tf_'+num).attr('disabled', true).attr('value','--:--');
		$('.fila_h'+num).attr('disabled',true);
	}
}
function enviarHorarios(disp){
	nhorarios= (valores[i][param_names.findIndex(function(elem){return elem == "start_time";})].split(",")).length;
	for (var i=0;i<nhorarios;i++){
		if (document.getElementById("chb_h"+i).checked){
			alert("se enviará "+$('.on .tf_'+i).value+" y "+$('.off .tf_'+i));
		}
	}
}
/* retrieveHorarios() recupera los horarios ya guardados, y los fija en la página */
function retrieveHorarios(){
	/* horarios de inicio están en valores[[indice de "start_time" en param_names]] */
	/* recorrer dispositivos */
	for (var i=0;i<disp_names.length;i++){
		//alert("dispositivo "+i);
		horarios_ini=valores[i][param_names.findIndex(function(elem){return elem == "start_time";})].split(",");
		horarios_fin=valores[i][param_names.findIndex(function(elem){return elem == "stop_time";})].split(",");
		//alert("horarios_ini tiene "+horarios_ini.length+" elementos");
		var hini_gotten=[];
		var hfin_gotten=[];
		/* recorrer horarios */
		for (var j=0;j<horarios_ini.length;j++){
			if ((horarios_ini[j].indexOf("NULL") < 0) && (horarios_fin[j].indexOf("NULL") < 0)){
				/*hini_gotten[j]=horarios_ini[j];
				hfin_gotten[j]=horarios_fin[j];
				alert("se agregaron las horas "+horarios_ini[j]+" y "+horarios_fin[j]);*/
				/* Se ha encontrado un intervalo seteado */
				document.getElementById("chb_h"+(j+1)).checked = true;
				enableHorario(j+1);
			}
			else{
				hini_gotten[j]=hfin_gotten[j]="NULL";
				document.getElementById("chb_h"+(j+1)).checked = false;
				enableHorario(j+1);
			}
		}
	}
}