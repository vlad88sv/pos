function agregar_info(texto) {
    $("#estadisticas").append('<div style="padding-top:5px;">' + texto + '</div>');
}

function agregar_panel(titulo, contenido) {
	var html = '';
	
	html += '<div class="panel panel-default">';
		html += '<div class="panel-heading">';
			html += '<h3 class="panel-title">' + titulo + '</h3>';
		html += '</div>';

		html += '<div class="panel-body">';
			html += contenido;
		html += '</div>';
	html += '</div>';
	$("#estadisticas").append(html);
}

function estadisticas() {
    var periodo_inicio = $('#periodo_inicio').val() + ' 00:00:00';
    var periodo_final = $('#periodo_final').val() + ' 23:59:59';

    $("#estadisticas").html('<b>{cargando estadísticas}</b>');
    rsv_solicitar('estadisticas',{periodo_inicio: periodo_inicio, periodo_final: periodo_final},function(datos){
        $("#estadisticas").empty();
        $("#estadisticas").html(datos.html);      
    });
}

$(function(){
    $("#actualizar").click(function(){
        estadisticas();
    });
});