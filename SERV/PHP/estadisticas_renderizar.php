<?php

$stat_html = '';

function estadisticas_agregar_info($texto) {
    global $stat_html;
    $stat_html .= '<div style="padding-top:5px;">' . $texto . '</div>';
}

function estadisticas_agregar_panel($titulo, $contenido) {
    global $stat_html;

    $stat_html .= '<div class="panel panel-default">';
    $stat_html .='<div class="panel-heading">';
    $stat_html .='<h3 class="panel-title">' . $titulo . '</h3>';
    $stat_html .='</div>';

    $stat_html .='<div class="panel-body">';
    $stat_html .= $contenido;
    $stat_html .='</div>';
    $stat_html .='</div>';
}

function estadisticas_renderizar($datos) {
    $buffer = '';

    if (!empty($datos['aux']['dsn'])) {
        $buffer = '';

        foreach ($datos['aux']['dsn'] as $usuario => $valor) {
            $buffer .= "<li>" . $datos['aux']['dsn'][$usuario]['usuario'] . " : " . $datos['aux']['dsn'][$usuario]['porcentaje'] . "%</li>";
        }

        estadisticas_agregar_panel('Distribución de carga de servicio entre meseros', $buffer);
    } else {
        estadisticas_agregar_panel('Distribución de carga de servicio entre meseros', 'Sin datos de rendimiento de meseros');
    }

    if (!empty($datos['aux']['venta_por_horas'])) {
        $buffer = '';

        foreach ($datos['aux']['venta_por_horas'] as $hora => $valor) {
            $buffer .= "<li>" . $datos['aux']['venta_por_horas'][$hora]['hora'] . ":00 : $" . $datos['aux']['venta_por_horas'][$hora]['total'] . " : " . $datos['aux']['venta_por_horas'][$hora]['porcentaje'] . "%</li>";
        }

        estadisticas_agregar_panel('Distribución de ventas por hora', '<ul>' . $buffer . '</ul>');
    } else {
        estadisticas_agregar_panel('Distribución de ventas por hora', 'Sin datos de rendimiento de ventas por hora');
    }

    if (!empty($datos['aux']['venta_por_dias'])) {
        $buffer = '';

        foreach ($datos['aux']['venta_por_dias'] as $dia => $valor) {
            $buffer .= "<li>" . $datos['aux']['venta_por_dias'][$dia]['dia'] . " : $" . $datos['aux']['venta_por_dias'][$dia]['total'] . "</li>";
        }
        estadisticas_agregar_panel('Total de ventas por día', '<ul>' . $buffer . '</ul>');
    } else {
        estadisticas_agregar_info('Sin datos de rendimiento de ventas por día');
    }

    if (!empty($datos['aux']['venta_por_mes'])) {
        $buffer = '';

        foreach ($datos['aux']['venta_por_mes'] as $indice => $valor) {
            $buffer .= "<li>" . $datos['aux']['venta_por_mes'][$indice]['mes'] . " : $" . $datos['aux']['venta_por_mes'][$indice]['total'] . "</li>";
        }
        estadisticas_agregar_panel('Total de ventas por mes', '<ul>' . $buffer . '</ul>');
    } else {
        estadisticas_agregar_info('Sin datos de rendimiento de ventas por mes');
    }

    if (!empty($datos['aux']['cuentas_por_horas'])) {
        $buffer = '';

        foreach ($datos['aux']['cuentas_por_horas'] as $hora => $valor) {
            $buffer .= "<li>" . $datos['aux']['cuentas_por_horas'][$hora]['hora'] . " - " . $datos['aux']['cuentas_por_horas'][$hora]['num_cuentas'] . "</li>";
        }
        estadisticas_agregar_panel('Cuentas abiertas por hora', '<ul>' . $buffer . '</ul>');
    } else {
        estadisticas_agregar_info('Sin datos de rendimiento de cuentas por hora');
    }

    if (!empty($datos['aux']['cortez_sum'])) {
        $buffer .= '<table class="table table-striped table-condensed" style="width: 300px;"><tbody>';
        $buffer .= '<thead><tr><th>Concepto</th><th>Total</th></tr></thead>';

        foreach ($datos['aux']['cortez_sum'] as $indice => $valor) {
            $buffer .= "<tr><th>" . $indice . "</th><td>$" . $datos['aux']['cortez_sum'][$indice] . "</td></tr>";
        }

        $buffer .= '</tbody></table>';

        estadisticas_agregar_panel('Totales de corte Z para periodo', $buffer);
    } else {
        estadisticas_agregar_info('Sin datos de corte Z para el periodo especificado');
    }

    if (!empty($datos['aux']['pizzas_por_dia'])) {
        $buffer = '';

        foreach ($datos['aux']['pizzas_por_dia'] as $index => $valor) {
            $buffer .= "<li>" . $datos['aux']['pizzas_por_dia'][$index]['dia'] . " - " . $datos['aux']['pizzas_por_dia'][$index]['cantidad'] . "</li>";
        }
        estadisticas_agregar_panel('Platos vendidos por día', '<ul>' . $buffer . '</ul>');
    } else {
        estadisticas_agregar_info('Sin datos de rendimiento de venta de platos por día');
    }

    if (!empty($datos['aux']['productos_por_categoria'])) {
        $buffer .= '<table class="table table-striped table-condensed" style="width: 500px;"><tbody>';
        $buffer .= '<thead><tr><th>Grupo</th><th>Producto</th><th>Cantidad</th></tr></thead>';

        foreach ($datos['aux']['productos_por_categoria'] as $producto => $valor) {
            $buffer .= "<tr><td>" . $datos['aux']['productos_por_categoria'][$producto]['grupo'] . "</td><td>" . $datos['aux']['productos_por_categoria'][$producto]['nombre'] . "</td><td>" . $datos['aux']['productos_por_categoria'][$producto]['cantidad'] . "</td></tr>";
        }
        $buffer .= '</tbody></table>';
        estadisticas_agregar_panel('Productos más vendidos por categoría', $buffer);
    } else {
        estadisticas_agregar_info('Sin datos de rendimiento de productos vendidos');
    }

    if (!empty($datos['aux']['uso_mesas'])) {
        $buffer .= '<table class="table table-striped table-condensed" style="width: 300px;"><tbody>';

        foreach ($datos['aux']['uso_mesas'] as $indice => $valor) {
            $buffer .= "<tr><td>Mesa #" . $datos['aux']['uso_mesas'][$indice]['ID_mesa'] . "</td><td>" . $datos['aux']['uso_mesas'][$indice]['cantidad'] . "</td></tr>";
        }
        $buffer .= '</tbody></table>';
        estadisticas_agregar_panel('Mesas más utilizadas', $buffer);
    } else {
        estadisticas_agregar_info('Sin datos de uso de mesa');
    }


    estadisticas_agregar_info('<h2>Tiempos de despacho</h2>');
    estadisticas_agregar_info('Tiempo promedio de despacho: ' . $datos['aux']['tps'] . ' minuto(s)');
    estadisticas_agregar_info('Tiempo máximo de despacho: ' . $datos['aux']['tms'] . '<br><br>');

    if (!empty($datos['aux']['anulaciones'])) {
        $buffer .= '<table class="table table-striped table-condensed" style=""><tbody>';
        $cta = "";

        foreach ($datos['aux']['anulaciones'] as $idx => $valor) {
            if ($cta !== $datos['aux']['anulaciones'][$idx]['ID_cuenta']) {
                $cta = $datos['aux']['anulaciones'][$idx]['ID_cuenta'];
                if ($buffer !== "")
                    $buffer .= '</td></tr>';
                $buffer .= '<tr><td>';
                $buffer .= '<br><p><b>Cuenta:</b> ' . $datos['aux']['anulaciones'][$idx]['ID_cuenta'] . '</p> ';
                $buffer .= '<p><b>Mesa:</b> ' . $datos['aux']['anulaciones'][$idx]['ID_mesa'] . '</p> ';
                $buffer .= '<p><b>Mesero:</b> ' . $datos['aux']['anulaciones'][$idx]['ID_mesero'] . '-' . $datos['aux']['anulaciones'][$idx]['usuario'] . '</p> ';
                $buffer .= '<p><b>Fecha Hora Anulacion:</b> ' . $datos['aux']['anulaciones'][$idx]['fechahora_anulado'] . '</p> ';
                $buffer .= '---------';
            }

            $buffer .= '<p><b>Producto:</b> ' . $datos['aux']['anulaciones'][$idx]['nombre'] . ' ';
            $buffer .= '<b>Fecha Hora Pedido:</b> ' . $datos['aux']['anulaciones'][$idx]['fechahora_pedido'] . '</p> ';
            $buffer .= '<p><b>Precio Original $:</b> ' . $datos['aux']['anulaciones'][$idx]['precio_original'] . ' ';
            $buffer .= '<b>Precio Gravado $:</b> ' . $datos['aux']['anulaciones'][$idx]['precio_grabado'] . '</p> <br> ';
        }
        $buffer .= '</tbody></table>';
        estadisticas_agregar_panel('Anulaciones', $buffer);
    } else {
        estadisticas_agregar_info('Sin datos de anulaciones');
    }

    if (!empty($datos['aux']['listacompras'])) {
        $buffer .= '<table class="table table-striped table-condensed" style=""><tbody>';

        foreach ($datos['aux']['listacompras'] as $indice => $valor) {
            $buffer .= '<tr>';
            $buffer .= '<td><p><b>Empresa:</b> ' . $datos['aux']['listacompras'][$indice]['empresa'] . '</p> ';
            $buffer .= '<p><b>Fecha compra:</b> ' . $datos['aux']['listacompras'][$indice]['fechatiempo'] . '</p> ';
            $buffer .= '<p><b>Descripcion:</b> ' . $datos['aux']['listacompras'][$indice]['descripcion'] . '</p> ';
            $buffer .= '<p><b>Valor $:</b> ' . $datos['aux']['listacompras'][$indice]['precio'] . '</p> ';
            $buffer .= '<p><b>Via:</b> ' . $datos['aux']['listacompras'][$indice]['via'] . '</p> <br></td> ';
            $buffer .= '</tr>';
        }
        $buffer .= '</tbody></table>';
        estadisticas_agregar_panel('Lista Compras', $buffer);
    } else {
        estadisticas_agregar_info('Sin datos de Compras');
    }


    if (!empty($datos['aux']['ctasproddel'])) {
        $buffer .= '<table class="table table-striped table-condensed" style=""><tbody>';
        $cta = "";

        foreach ($datos['aux']['ctasproddel'] as $idx => $valor) {
            if (cta !== $datos['aux']['ctasproddel'][$idx]['ID_cuenta']) {
                $cta = $datos['aux']['ctasproddel'][$idx]['ID_cuenta'];
                if ($buffer !== "")
                    $buffer .= '</td></tr>';
                $buffer .= '<tr><td>';
                $buffer .= '<br><p><b>Cuenta:</b> ' . $datos['aux']['ctasproddel'][$idx]['ID_cuenta'] . '</p> ';
                $buffer .= '<p><b>Mesa:</b> ' . $datos['aux']['ctasproddel'][$idx]['ID_mesa'] . '</p> ';
                $buffer .= '<p><b>Mesero:</b> ' . $datos['aux']['ctasproddel'][$idx]['ID_mesero'] . '-' . $datos['aux']['ctasproddel'][$idx]['usuario'] . '</p> ';
                $buffer .= '<p><b>Fecha Hora :</b> ' . $datos['aux']['ctasproddel'][$idx]['fechahora_pedido'] . '</p> ';
                $buffer .= '---------';
            }

            $buffer .= '<p><b>Producto:</b> ' . $datos['aux']['ctasproddel'][$idx]['nombre'] . '</p> ';
            $buffer .= '<p><b>Fecha Hora Pedido:</b> ' . $datos['aux']['ctasproddel'][$idx]['fechahora_pedido'] . ' ';
            $buffer .= '<b>Fecha Hora Eliminado:</b> ' . $datos['aux']['ctasproddel'][$idx]['fechahora'] . '</p> ';
            $buffer .= '<p><b>Precio Original $:</b> ' . $datos['aux']['ctasproddel'][$idx]['precio_original'] . ' ';
            $buffer .= '<b>Precio Gravado $:</b> ' . $datos['aux']['ctasproddel'][$idx]['precio_grabado'] . '</p> ';
            $buffer .= '<b>Comentario:</b> ' . $datos['aux']['ctasproddel'][$idx]['nota'] . '</p> <br> ';
        }
        $buffer .= '</tbody></table>';
        estadisticas_agregar_panel('Cuentas con Productos eliminados', $buffer);
    } else {
        estadisticas_agregar_info('Sin datos de Cuentas con Productos eliminados');
    }

    if (!empty($datos['aux']['ctasprodpreciosdiff'])) {
        $buffer .= '<table class="table table-striped table-condensed" style=""><tbody>';
        $cta = "";

        foreach ($datos['aux']['ctasprodpreciosdiff'] as $idx => $valor) {
            if ($cta !== $datos['aux']['ctasprodpreciosdiff'][$idx]['ID_cuenta']) {
                $cta = $datos['aux']['ctasprodpreciosdiff'][$idx]['ID_cuenta'];
                if ($buffer !== "")
                    $buffer .= '</td></tr>';
                $buffer .= '<tr><td>';
                $buffer .= '<br><p><b>Cuenta:</b> ' . $datos['aux']['ctasprodpreciosdiff'][$idx]['ID_cuenta'] . '</p> ';
                $buffer .= '<p><b>Mesa:</b> ' . $datos['aux']['ctasprodpreciosdiff'][$idx]['ID_mesa'] . '</p> ';
                $buffer .= '<p><b>Mesero:</b> ' . $datos['aux']['ctasprodpreciosdiff'][$idx]['ID_mesero'] . '-' . $datos['aux']['ctasprodpreciosdiff'][$idx]['usuario'] . '</p> ';
                $buffer .= '<p><b>Fecha Hora :</b> ' . $datos['aux']['ctasprodpreciosdiff'][$idx]['fechahora_pedido'] . '</p> ';
                $buffer .= '---------';
            }

            $buffer .= '<p><b>Producto:</b> ' . $datos['aux']['ctasprodpreciosdiff'][$idx]['nombre'] . '</p> ';
            $buffer .= '<p><b>Fecha Hora Pedido:</b> ' . $datos['aux']['ctasprodpreciosdiff'][$idx]['fechahora_pedido'] . ' ';
            $buffer .= '<b>Fecha Hora Eliminado:</b> ' . $datos['aux']['ctasprodpreciosdiff'][$idx]['fechahora'] . '</p> ';

            if ($datos['aux']['ctasprodpreciosdiff'][$idx]['precio_grabado'] < $datos['aux']['ctasprodpreciosdiff'][$idx]['precio_original']) {
                $buffer .= '<p><b>Precio Original $:</b> ' . $datos['aux']['ctasprodpreciosdiff'][$idx]['precio_original'] . ' ';
                $buffer .= '<b>Precio Gravado $:</b> <font color="red">' . $datos['aux']['ctasprodpreciosdiff'][$idx]['precio_grabado'] . '</font></p> ';
            } else {
                $buffer .= '<p><b>Precio Original $:</b> ' . $datos['aux']['ctasprodpreciosdiff'][$idx]['precio_original'] . ' ';
                $buffer .= '<b>Precio Gravado $:</b> ' . $datos['aux']['ctasprodpreciosdiff'][$idx]['precio_grabado'] . '</p> ';
            }

            $buffer .= '<b>Comentario:</b> ' . $datos['aux']['ctasprodpreciosdiff'][$idx]['nota'] . '</p> <br> ';
        }
        $buffer .= '</tbody></table>';
        estadisticas_agregar_panel('Cuentas con Productos precio cambiado', $buffer);
    } else {
        estadisticas_agregar_info('Sin datos de Cuentas con  Productos precio cambiado');
    }

    global $stat_html;
    return $stat_html;
}
