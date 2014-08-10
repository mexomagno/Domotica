var boton_colores={ normal:"#aaf", hover:"#66d", active:"#008"};
$(document).ready(function() {
	//todo esto correrá una vez que la página se haya cargado completamente. Es casi necesario siempre.
	$('.boton').hover(
	    function() {
	        $(this).stop().animate({backgroundColor:boton_colores['hover']}, 180);
	        },
	    function () {
	        $(this).stop().animate({backgroundColor:boton_colores['normal']}, 180);
	    });
	$('.boton').mousedown(
		function() {
	        $(this).stop().animate({backgroundColor:boton_colores['active']}, 180);
		});
	$(window).load(
		function() {
			$("#screen").fadeOut(900, function(){});
		});
});