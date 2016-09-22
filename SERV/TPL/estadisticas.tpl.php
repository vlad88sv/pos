<?php
$periodo_inicio = mysql_date(). ' 00:00:00';
$periodo_final = mysql_date(). ' 23:59:59';

// Si se definió un periodo, restringir las estadísticas a este periodo.
// la fecha debe ser DATE
if (isset($_POST['fecha'])) {
    $periodo_inicio = db_codex($_POST['fecha']) . ' 00:00:00';
    $periodo_final = db_codex($_POST['fecha']) . ' 23:59:00';    
}

// las fechas deben ser DATETIME.
if (isset($_POST['periodo_inicio']) && isset($_POST['periodo_final']))
{
    $periodo_inicio = db_codex($_POST['periodo_inicio']);
    $periodo_final = db_codex($_POST['periodo_final']);
}

// Distrubición de Servicio Normalizado (DSN)
/* Calcula la distribución (en porcentaje) de atención de los
 * meseros en base al monto vendido no en base al número de mesas
 */

$c = 'SELECT ID_mesero, IFNULL(usuario, CONCAT("#",ID_mesero) ) AS usuario, '
        . 'SUM(precio_grabado) AS subtotal '
        . 'FROM `pedidos` LEFT JOIN `cuentas` USING(ID_cuenta) LEFT JOIN `usuarios` ON ID_mesero = ID_usuarios '
        . 'WHERE `fechahora_pedido` BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'" AND '
        . 'flag_anulado = 0 AND flag_cancelado = 0 GROUP BY ID_mesero';
$r = db_consultar($c);

// Calculamos el total aproximado en ventas (sin propinas/IVAs, etc) - no es necesario
$total = 0.00;
$dsn = array();
while ($f = db_fetch($r))
{
    $dsn[$f['ID_mesero']] = $f;
    $total += $f['subtotal'];
}

foreach ($dsn as $ID_mesero => $bdsn)
{
    $dsn[$ID_mesero]['porcentaje'] = round((($bdsn['subtotal'] / $total) * 100),2);
    $json['aux']['dsn'][$ID_mesero] = $dsn[$ID_mesero];
}

/***********************************************/
// Estadisticas de corte
$c = 'SELECT SUM(COALESCE(`total_a_cuadrar`,0)) AS "Venta", SUM(COALESCE(`total_diferencia`,0)) AS "Diferencia", '
        . 'SUM(COALESCE(`total_pos`,0)) AS "POS", SUM(COALESCE(`total_efectivo`,0)) AS "Efectivo", '
        . 'SUM(COALESCE(`total_compras`,0)) AS "Compras", '
        . 'SUM(COALESCE(`total_comprasG`,0)) AS "ComprasG", '
        . 'SUM(COALESCE(`total_comprasO`,0)) AS "ComprasO" '
        . 'FROM `cortez` WHERE DATE(`fechatiempo`) BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'"';
$r = db_consultar($c);
$f = db_fetch($r);

$json['aux']['cortez_sum'] = $f;

/***********************************************/

/***********************************************/
// Tiempo Promedio de Servicio (TPS)
$c = 'SELECT (STDDEV(TIME_TO_SEC(TIMEDIFF(`fechahora_despachado`, `fechahora_pedido`))) / 60) AS stddev_tps, '
        . '(AVG(TIME_TO_SEC(TIMEDIFF(`fechahora_despachado`, `fechahora_pedido`))) / 60) AS tps '
        . 'FROM `pedidos` WHERE  `fechahora_pedido` BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'" AND '
        . 'flag_despachado=1 AND fechahora_despachado <> "0000-00-00 00:00:00"';
$r = db_consultar($c);
$f = db_fetch($r);

$json['aux']['tps'] = (isset($f['tps']) ? ceil($f['tps']).'±'.floor($f['stddev_tps']).'' : '0');


/***********************************************/
// Tiempo Máximo de Servicio (TMS)

$c = 'SELECT CEIL((TIME_TO_SEC(TIMEDIFF(`fechahora_despachado`, `fechahora_pedido`))) / 60) AS tms, '
        . 'COUNT(*) AS tms_count FROM `pedidos`  LEFT JOIN `cuentas` USING(ID_cuenta) '
        . 'WHERE  `fechahora_pedido` BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'" AND '
        . 'flag_despachado=1  AND fechahora_despachado <> "0000-00-00 00:00:00" '
        . 'GROUP BY tms ORDER BY tms DESC LIMIT 15';
$r = db_consultar($c);

$json['aux']['tms'] = '';

while ($f = db_fetch($r))
{
    $json['aux']['tms'] .= $f['tms']."'x".$f['tms_count'].', ';
}

$json['aux']['tms'] = !empty($json['aux']['tms']) ? rtrim($json['aux']['tms'],', ') : '0';


/************************************************/
// Ventas por hora

$c_adicionales = '( SELECT COALESCE(SUM(precio_grabado),0 ) FROM `pedidos_adicionales` AS t3 '
        . 'WHERE t3.tipo="poner" AND t3.ID_pedido=t2.ID_pedido )';

$c_total_bruto = '(COALESCE(t2.precio_grabado,0) + '.$c_adicionales.')';
$c_total = 'ROUND(SUM( ('.$c_total_bruto.' / IF(flag_exento = 0, 1, 1.13)) * IF(flag_nopropina = 0, 1.10, 1) ),2) AS total';

$c = 'SELECT  hour(fechahora_pedido) AS hora, '.$c_total.' '
        . 'FROM `pedidos` AS t2  LEFT JOIN `cuentas` USING(ID_cuenta) '
        . 'WHERE `fechahora_pedido` BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'" AND '
        . 'flag_pagado=1 AND flag_anulado=0 AND flag_cancelado=0 '
        . 'GROUP BY HOUR(fechahora_pedido) ORDER BY HOUR(fechahora_pedido) DESC';
$r = db_consultar($c);

$horas = array();
$total = 0;

while ($f = db_fetch($r))
{
    $horas[] = $f;
    $total += $f['total'];
}

foreach ($horas as $indice => $hora)
{
    $hora['porcentaje'] = round((($hora['total'] / $total) * 100),2);
    $json['aux']['venta_por_horas'][] = $hora;
}


/************************************************/
// Ventas por día

$c_adicionales = '( SELECT COALESCE(SUM(precio_grabado),0 ) FROM `pedidos_adicionales` AS t3 '
        . 'WHERE t3.tipo="poner" AND t3.ID_pedido=t2.ID_pedido )';
$c_total_bruto = '(COALESCE(t2.precio_grabado,0) + '.$c_adicionales.')';
$c_total = 'ROUND(SUM( ('.$c_total_bruto.' / IF(flag_exento = 0, 1, 1.13)) * IF(flag_nopropina = 0, 1.10, 1) ),2) AS total';

$c = 'SELECT  DATE(fechahora_pedido) AS dia, '.$c_total.' '
        . 'FROM `pedidos` AS t2  LEFT JOIN `cuentas` USING(ID_cuenta) '
        . 'WHERE `fechahora_pedido` BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'" AND '
        . 'flag_pagado=1 AND flag_anulado=0 AND flag_cancelado=0 '
        . 'GROUP BY DATE(fechahora_pedido) ORDER BY DATE(fechahora_pedido) DESC';
$r = db_consultar($c);

while ($f = db_fetch($r))
{
    $json['aux']['venta_por_dias'][] = $f;
}

/************************************************/
// Ventas por mes

$c_adicionales = '( SELECT COALESCE(SUM(precio_grabado),0 ) FROM `pedidos_adicionales` AS t3 '
        . 'WHERE t3.tipo="poner" AND t3.ID_pedido=t2.ID_pedido )';
$c_total_bruto = '(COALESCE(t2.precio_grabado,0) + '.$c_adicionales.')';
$c_total = 'ROUND(SUM( ('.$c_total_bruto.' / IF(flag_exento = 0, 1, 1.13)) * IF(flag_nopropina = 0, 1.10, 1) ),2) AS total';

$c = 'SELECT DATE_FORMAT(fechahora_pedido, "%Y-%M") AS mes, '.$c_total.' '
        . 'FROM `pedidos` AS t2 LEFT JOIN `cuentas` USING(ID_cuenta)  '
        . 'WHERE `fechahora_pedido` BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'" AND '
        . 'flag_pagado=1 AND flag_anulado=0 AND flag_cancelado=0 '
        . 'GROUP BY DATE_FORMAT(fechahora_pedido, "%Y%m") ORDER BY DATE_FORMAT(fechahora_pedido, "%Y%m") DESC';
$r = db_consultar($c);
while ($f = db_fetch($r))
{
    $json['aux']['venta_por_mes'][] = $f;
}

/************************************************/
// Cuentas (mesas servidas) por hora
$c= 'SELECT t1.hora, COUNT(*) AS num_cuentas FROM (SELECT CONCAT(HOUR(fechahora_pedido),":",'
        . 'LPAD(FLOOR(MINUTE(fechahora_pedido)/15)*15,2,"00")) AS hora '
        . 'FROM `pedidos` LEFT JOIN `cuentas` USING(ID_cuenta) '
        . 'WHERE `fechahora_pedido` BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'" AND '
        . 'flag_anulado = 0 GROUP BY ID_mesa, ID_cuenta) AS t1 GROUP BY t1.hora ORDER BY t1.hora';
$r = db_consultar($c);

while ($f = db_fetch($r))
{
    $json['aux']['cuentas_por_horas'][] = $f;
}


/************************************************/
// Prods por dia
$c = "SELECT DATE(fechahora_pedido) AS dia, COUNT(*) as cantidad FROM `pedidos` "
        . "LEFT JOIN `cuentas` USING(ID_cuenta)  LEFT JOIN `productos` AS t3  USING(ID_producto) "
        . "LEFT JOIN `productos_grupos` AS t4 USING(ID_grupo) WHERE `fechahora_pedido` BETWEEN '".$periodo_inicio
        . "' AND '".$periodo_final."' AND flag_anulado = 0 AND flag_cancelado = 0 AND nodo_sugerido ='comida' "
        . "GROUP BY DATE(fechahora_pedido) ORDER BY cantidad DESC";
$r = db_consultar($c);
while ($f = db_fetch($r))
{
    $json['aux']['pizzas_por_dia'][] = $f;
}


/************************************************/
// Productos más vendidos por categoría
$c = 'SELECT t3.nombre, t4.descripcion AS grupo, COUNT(*) as cantidad  '
        . 'FROM `pedidos` LEFT JOIN `cuentas` USING(ID_cuenta) '
        . 'LEFT JOIN `productos` AS t3  USING(ID_producto) LEFT JOIN `productos_grupos` AS t4 USING(ID_grupo) '
        . 'WHERE ID_producto IS NOT NULL AND `fechahora_pedido` '
        . 'BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'" AND flag_anulado = 0 AND '
        . 'flag_cancelado = 0 GROUP BY ID_producto ORDER BY ID_grupo ASC, t3.nombre ASC, cantidad DESC';
$r = db_consultar($c);
while ($f = db_fetch($r))
{
    $json['aux']['productos_por_categoria'][] = $f;
}


/************************************************/
// Mesas mas utilizadas
$c = 'SELECT ID_mesa, COUNT(DISTINCT `ID_cuenta`) as cantidad  '
        . 'FROM `pedidos` LEFT JOIN `cuentas` USING(ID_cuenta) '
        . 'WHERE ID_producto IS NOT NULL AND `fechahora_pedido` '
        . 'BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'" AND flag_anulado = 0 AND '
        . 'flag_cancelado = 0 GROUP BY ID_mesa ORDER BY cantidad DESC';
$r = db_consultar($c);
while ($f = db_fetch($r))
{
    $json['aux']['uso_mesas'][] = $f;
}

/************************************************/
// Anulaciones
$c = 'SELECT t1.ID_cuenta, t1.ID_mesa, t1.ID_mesero, t4.usuario, t1.fechahora_anulado,  '
     .'         t3.nombre, t2.precio_grabado, t2.precio_original, t2.fechahora_pedido '
     .' FROM `cuentas` AS t1 LEFT JOIN `pedidos` AS t2 on t1.ID_cuenta=t2.ID_cuenta '
     .'         inner join productos as t3 on t2.id_producto=t3.id_producto '
     .'         inner join usuarios as t4 on t1.ID_mesero = t4.ID_usuarios '
     .' WHERE fechahora_pedido BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'"  ' 
     .' AND flag_anulado=1 '
     .' ORDER BY t1.ID_cuenta DESC ';                
       
$r = db_consultar($c);
while ($f = db_fetch($r))
{
    $json['aux']['anulaciones'][] = $f;
}

/************************************************/
// lista compras
$c = 'select * from compras where fechatiempo BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'"  ' 
     .' order by via, fechatiempo asc';                
       
$r = db_consultar($c);
while ($f = db_fetch($r))
{
    $json['aux']['listacompras'][] = $f;
}


/************************************************/
// cuentas con prod eliminado
$c = 'SELECT t1.ID_cuenta, t1.ID_mesa, t1.ID_mesero, t4.usuario,   '
        . 't3.nombre, t2.precio_grabado, t2.precio_original, t2.fechahora_pedido , t5.nota, t5.fechahora '
        . 'FROM `cuentas` AS t1 LEFT JOIN `pedidos` AS t2 on t1.ID_cuenta=t2.ID_cuenta '
        . 'inner join productos as t3 on t2.id_producto=t3.id_producto ' 
        . 'inner join usuarios as t4 on t1.ID_mesero = t4.ID_usuarios ' 
        . 'inner join historial as t5 on t1.ID_cuenta = t5.ID_cuenta and t2.ID_pedido= t5.ID_pedido ' 
        . 'WHERE flag_anulado=0 AND t2.flag_cancelado="1"  and t5.grupo!="ordenes" AND ' 
        . 'fechahora_pedido  BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'"  '
        . 'ORDER BY t1.ID_cuenta DESC ';    
       
$r = db_consultar($c);
while ($f = db_fetch($r))
{
    $json['aux']['ctasproddel'][] = $f;
}

/************************************************/
// cuentas con prod y precios cambiados
$c = 'SELECT t1.ID_cuenta, t1.ID_mesa, t1.ID_mesero, t4.usuario, t1.fechahora_anulado,  '
        . '    t3.nombre, t2.precio_grabado, t2.precio_original, t2.fechahora_pedido , t5.nota, t5.fechahora ' 
        . '    FROM `cuentas` AS t1 LEFT JOIN `pedidos` AS t2 on t1.ID_cuenta=t2.ID_cuenta '
        . '    inner join productos as t3 on t2.id_producto=t3.id_producto ' 
        . '    inner join usuarios as t4 on t1.ID_mesero = t4.ID_usuarios '
        . '     inner join historial as t5 on t1.ID_cuenta = t5.ID_cuenta and t2.ID_pedido= t5.ID_pedido '
        . '    WHERE fechahora_pedido  BETWEEN "'.$periodo_inicio.'" AND "'.$periodo_final.'"  '
        . '    AND flag_anulado=0 AND t2.precio_grabado!= t2.precio_original   and t5.grupo!="ordenes" '
        . '    ORDER BY t1.ID_cuenta DESC ';


$r = db_consultar($c);
while ($f = db_fetch($r))
{
    $json['aux']['ctasprodpreciosdiff'][] = $f;
}
            
?>