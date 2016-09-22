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
        if (typeof datos.aux.dsn != 'undefined') {
           var buffer = '';
    
           for (usuario in datos.aux.dsn)
           {
              buffer += "<li>" + datos.aux.dsn[usuario].usuario + " : " + datos.aux.dsn[usuario].porcentaje + "%</li>";
           }
    
		   agregar_panel('Distribución de carga de servicio entre meseros', buffer);
        } else {
			agregar_panel('Distribución de carga de servicio entre meseros', 'Sin datos de rendimiento de meseros');
        }
                
        if (typeof datos.aux.venta_por_horas != 'undefined') {
           var buffer = '';
   
           for (hora in datos.aux.venta_por_horas)
           {
			  console.log(datos.aux.venta_por_horas[hora]);
              buffer += "<li>" + datos.aux.venta_por_horas[hora].hora + ":00 : $" + datos.aux.venta_por_horas[hora].total + " : " + datos.aux.venta_por_horas[hora].porcentaje + "%</li>";
           }
		   
			agregar_panel('Distribución de ventas por hora', '<ul>' + buffer + '</ul>');
        } else {
			agregar_panel('Distribución de ventas por hora', 'Sin datos de rendimiento de ventas por hora');
        }
        
        if (typeof datos.aux.venta_por_dias != 'undefined') {
           var buffer = '';
   
           for (dia in datos.aux.venta_por_dias)
           {
              buffer += "<li>" + datos.aux.venta_por_dias[dia].dia + " : $" + datos.aux.venta_por_dias[dia].total + "</li>";
           }
           agregar_panel('Total de ventas por día','<ul>' + buffer + '</ul>');
        } else {
           agregar_info('Sin datos de rendimiento de ventas por día');
        }
        
        if (typeof datos.aux.venta_por_mes != 'undefined') {
           var buffer = '';
   
           for (indice in datos.aux.venta_por_mes)
           {
              buffer += "<li>" + datos.aux.venta_por_mes[indice].mes + " : $" + datos.aux.venta_por_mes[indice].total + "</li>";
           }
           agregar_panel('Total de ventas por mes','<ul>' + buffer + '</ul>');
        } else {
           agregar_info('Sin datos de rendimiento de ventas por mes');
        }

        if (typeof datos.aux.cuentas_por_horas != 'undefined') {
           var buffer = '';
   
           for (hora in datos.aux.cuentas_por_horas )
           {
              buffer += "<li>" + datos.aux.cuentas_por_horas[hora].hora + " - " + datos.aux.cuentas_por_horas[hora].num_cuentas + "</li>";
           }
		   agregar_panel('Cuentas abiertas por hora','<ul>' + buffer + '</ul>');
        } else {
           agregar_info('Sin datos de rendimiento de cuentas por hora');
        }
        
        if (typeof datos.aux.cortez_sum != 'undefined') {
           var buffer = '<table class="table table-striped table-condensed" style="width: 300px;"><tbody>';
		   buffer += '<thead><tr><th>Concepto</th><th>Total</th></tr></thead>';
   
           for (indice in datos.aux.cortez_sum)
           {
              buffer += "<tr><th>" + indice + "</th><td>$" + datos.aux.cortez_sum[indice] + "</td></tr>";
           }
		   
		   buffer += '</tbody></table>';
		   
		   agregar_panel('Totales de corte Z para periodo', buffer);
        } else {
           agregar_info('Sin datos de corte Z para el periodo especificado');
        }
        
        if (typeof datos.aux.pizzas_por_dia != 'undefined') {
           var buffer = '';
   
           for (index in datos.aux.pizzas_por_dia )
           {
              buffer += "<li>" + datos.aux.pizzas_por_dia[index].dia + " - " + datos.aux.pizzas_por_dia[index].cantidad + "</li>";
           }
		   agregar_panel('Platos vendidos por día','<ul>' + buffer + '</ul>');
        } else {
           agregar_info('Sin datos de rendimiento de venta de platos por día');
        }

        if (typeof datos.aux.productos_por_categoria != 'undefined') {
           var buffer = '<table class="table table-striped table-condensed" style="width: 500px;"><tbody>';
		   buffer += '<thead><tr><th>Grupo</th><th>Producto</th><th>Cantidad</th></tr></thead>';
   
           for (producto in datos.aux.productos_por_categoria )
           {
              buffer += "<tr><td>" + datos.aux.productos_por_categoria[producto].grupo + "</td><td>" + datos.aux.productos_por_categoria[producto].nombre + "</td><td>" + datos.aux.productos_por_categoria[producto].cantidad + "</td></tr>";
           }
           buffer += '</tbody></table>';
		   agregar_panel('Productos más vendidos por categoría', buffer);
        } else {
           agregar_info('Sin datos de rendimiento de productos vendidos');
        }
        
        if (typeof datos.aux.uso_mesas != 'undefined') {
           var buffer = '<table class="table table-striped table-condensed" style="width: 300px;"><tbody>';
   
           for (indice in datos.aux.uso_mesas )
           {
              buffer += "<tr><td>Mesa #" + datos.aux.uso_mesas[indice].ID_mesa+ "</td><td>" + datos.aux.uso_mesas[indice].cantidad+ "</td></tr>";
           }
           buffer += '</tbody></table>';
		   agregar_panel('Mesas más utilizadas',buffer);
        } else {
           agregar_info('Sin datos de uso de mesa');
        }

        
        agregar_info('<h2>Tiempos de despacho</h2>');
        agregar_info('Tiempo promedio de despacho: ' + datos.aux.tps + ' minuto(s)');
        agregar_info('Tiempo máximo de despacho: ' + datos.aux.tms + '<br><br>');
        
        if (typeof datos.aux.anulaciones != 'undefined') {
            var buffer = '<table class="table table-striped table-condensed" style=""><tbody>';
            var cta="";
            
            for (idx in datos.aux.anulaciones)
            {            
                if(cta!==datos.aux.anulaciones[idx].ID_cuenta){
                    cta=datos.aux.anulaciones[idx].ID_cuenta;
                    if(buffer!=="")
                        buffer +=  '</td></tr>';
                    buffer += '<tr><td>';
                    buffer += '<br><p><b>Cuenta:</b> ' + datos.aux.anulaciones[idx].ID_cuenta + '</p> ';
                    buffer += '<p><b>Mesa:</b> ' + datos.aux.anulaciones[idx].ID_mesa + '</p> ';
                    buffer += '<p><b>Mesero:</b> ' + datos.aux.anulaciones[idx].ID_mesero + '-' + datos.aux.anulaciones[idx].usuario + '</p> ';
                    buffer += '<p><b>Fecha Hora Anulacion:</b> ' + datos.aux.anulaciones[idx].fechahora_anulado + '</p> ';
                    buffer += '---------';
                }                   

                buffer += '<p><b>Producto:</b> ' + datos.aux.anulaciones[idx].nombre + ' ';
                buffer += '<b>Fecha Hora Pedido:</b> ' + datos.aux.anulaciones[idx].fechahora_pedido + '</p> ';
                buffer += '<p><b>Precio Original $:</b> ' + datos.aux.anulaciones[idx].precio_original + ' ';
                buffer +=  '<b>Precio Gravado $:</b> ' + datos.aux.anulaciones[idx].precio_grabado + '</p> <br> ';
                
                
            }
            buffer += '</tbody></table>';
            agregar_panel('Anulaciones',buffer);
        } else {
           agregar_info('Sin datos de anulaciones');
        }
        
        if (typeof datos.aux.listacompras != 'undefined') {
            var buffer = '<table class="table table-striped table-condensed" style=""><tbody>';           
            
            for (indice in datos.aux.listacompras)
            {                                            
                buffer += '<tr>';
                buffer += '<td><p><b>Empresa:</b> ' + datos.aux.listacompras[indice].empresa + '</p> ';
                buffer += '<p><b>Fecha compra:</b> ' + datos.aux.listacompras[indice].fechatiempo + '</p> ';
                buffer += '<p><b>Descripcion:</b> ' +datos.aux.listacompras[indice].descripcion + '</p> ';
                buffer += '<p><b>Valor $:</b> ' + datos.aux.listacompras[indice].precio + '</p> ';
                buffer +=  '<p><b>Via:</b> ' + datos.aux.listacompras[indice].via + '</p> <br></td> ';
                buffer +=  '</tr>';
                
            }
            buffer += '</tbody></table>';
            agregar_panel('Lista Compras',buffer);
        } else {
           agregar_info('Sin datos de Compras');
        }        

        
        if (typeof datos.aux.ctasproddel != 'undefined') {
            var buffer = '<table class="table table-striped table-condensed" style=""><tbody>';
            var cta="";
            
            for (idx in datos.aux.ctasproddel)
            {            
                if(cta!==datos.aux.ctasproddel[idx].ID_cuenta){
                    cta=datos.aux.ctasproddel[idx].ID_cuenta;
                    if(buffer!=="")
                        buffer +=  '</td></tr>';
                    buffer += '<tr><td>';
                    buffer += '<br><p><b>Cuenta:</b> ' + datos.aux.ctasproddel[idx].ID_cuenta + '</p> ';
                    buffer += '<p><b>Mesa:</b> ' + datos.aux.ctasproddel[idx].ID_mesa + '</p> ';
                    buffer += '<p><b>Mesero:</b> ' + datos.aux.ctasproddel[idx].ID_mesero + '-' + datos.aux.ctasproddel[idx].usuario + '</p> ';
                    buffer += '<p><b>Fecha Hora :</b> ' + datos.aux.ctasproddel[idx].fechahora_pedido + '</p> ';
                    buffer += '---------';
                }                   

                buffer += '<p><b>Producto:</b> ' + datos.aux.ctasproddel[idx].nombre + '</p> ';
                buffer += '<p><b>Fecha Hora Pedido:</b> ' + datos.aux.ctasproddel[idx].fechahora_pedido + ' ';
                buffer += '<b>Fecha Hora Eliminado:</b> ' + datos.aux.ctasproddel[idx].fechahora + '</p> ';
                buffer += '<p><b>Precio Original $:</b> ' + datos.aux.ctasproddel[idx].precio_original + ' ';
                buffer +=  '<b>Precio Gravado $:</b> ' + datos.aux.ctasproddel[idx].precio_grabado + '</p> ';
                buffer +=  '<b>Comentario:</b> ' + datos.aux.ctasproddel[idx].nota + '</p> <br> ';                
                
            }
            buffer += '</tbody></table>';
            agregar_panel('Cuentas con Productos eliminados',buffer);
        } else {
           agregar_info('Sin datos de Cuentas con Productos eliminados');
        }

        if (typeof datos.aux.ctasprodpreciosdiff != 'undefined') {
            var buffer = '<table class="table table-striped table-condensed" style=""><tbody>';
            var cta="";
            
            for (idx in datos.aux.ctasprodpreciosdiff)
            {            
                if(cta!==datos.aux.ctasprodpreciosdiff[idx].ID_cuenta){
                    cta=datos.aux.ctasprodpreciosdiff[idx].ID_cuenta;
                    if(buffer!=="")
                        buffer +=  '</td></tr>';
                    buffer += '<tr><td>';
                    buffer += '<br><p><b>Cuenta:</b> ' + datos.aux.ctasprodpreciosdiff[idx].ID_cuenta + '</p> ';
                    buffer += '<p><b>Mesa:</b> ' + datos.aux.ctasprodpreciosdiff[idx].ID_mesa + '</p> ';
                    buffer += '<p><b>Mesero:</b> ' + datos.aux.ctasprodpreciosdiff[idx].ID_mesero + '-' + datos.aux.ctasprodpreciosdiff[idx].usuario + '</p> ';
                    buffer += '<p><b>Fecha Hora :</b> ' + datos.aux.ctasprodpreciosdiff[idx].fechahora_pedido + '</p> ';
                    buffer += '---------';
                }                   

                buffer += '<p><b>Producto:</b> ' + datos.aux.ctasprodpreciosdiff[idx].nombre + '</p> ';
                buffer += '<p><b>Fecha Hora Pedido:</b> ' + datos.aux.ctasprodpreciosdiff[idx].fechahora_pedido + ' ';
                buffer += '<b>Fecha Hora Eliminado:</b> ' + datos.aux.ctasprodpreciosdiff[idx].fechahora + '</p> ';
                
                if(datos.aux.ctasprodpreciosdiff[idx].precio_grabado<datos.aux.ctasprodpreciosdiff[idx].precio_original){
                  buffer += '<p><b>Precio Original $:</b> ' + datos.aux.ctasprodpreciosdiff[idx].precio_original + ' ';
                  buffer +=  '<b>Precio Gravado $:</b> <font color="red">' + datos.aux.ctasprodpreciosdiff[idx].precio_grabado + '</font></p> ';
                }
                else{
                    buffer += '<p><b>Precio Original $:</b> ' + datos.aux.ctasprodpreciosdiff[idx].precio_original + ' ';
                    buffer +=  '<b>Precio Gravado $:</b> ' + datos.aux.ctasprodpreciosdiff[idx].precio_grabado + '</p> ';
                }
                
                buffer +=  '<b>Comentario:</b> ' + datos.aux.ctasprodpreciosdiff[idx].nota + '</p> <br> ';                
                
            }
            buffer += '</tbody></table>';
            agregar_panel('Cuentas con Productos precio cambiado',buffer);
        } else {
           agregar_info('Sin datos de Cuentas con  Productos precio cambiado');
        }        
        
    });
}

$(function(){
    $("#actualizar").click(function(){
        estadisticas();
    });
});