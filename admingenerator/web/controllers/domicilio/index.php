<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/domicilio/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    $start = 0;
    $vars = $request->query->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
		'ID_domicilio', 
		'telefono', 
		'direccion', 
		'nombre', 
		'tarjeta', 
		'expiracion', 
		'vuelto', 
		'notas', 
		'metodo_pago', 
		'documento_fiscal', 
		'detalle_facturacion', 
		'facturacion_nombre', 
		'facturacion__dui', 
		'facturacion_nit', 
		'facturacion_nrc', 
		'facturacion_giro', 
		'facturacion_direccion', 
		'flag_en_transito', 
		'fechahora_transito', 
		'flag_ack', 
		'flag_tarjeta_cobrada', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(10)', 
		'longtext', 
		'varchar(250)', 
		'varchar(20)', 
		'varchar(10)', 
		'decimal(10,2)', 
		'varchar(250)', 
		'enum(\'efectivo\',\'tarjeta\')', 
		'enum(\'credito_fiscal\',\'consumidor_final\')', 
		'enum(\'detalle\',\'consumo\')', 
		'varchar(250)', 
		'varchar(20)', 
		'varchar(20)', 
		'varchar(20)', 
		'varchar(250)', 
		'varchar(250)', 
		'bit(1)', 
		'datetime', 
		'int(11)', 
		'tinyint(1)', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `domicilio`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `domicilio`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		if( $table_columns_type[$i] != "blob") {
				$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
		} else {				if( !$row_sql[$table_columns[$i]] ) {
						$rows[$row_key][$table_columns[$i]] = "0 Kb.";
				} else {
						$rows[$row_key][$table_columns[$i]] = " <a target='__blank' href='menu/download?id=" . $row_sql[$table_columns[0]];
						$rows[$row_key][$table_columns[$i]] .= "&fldname=" . $table_columns[$i];
						$rows[$row_key][$table_columns[$i]] .= "&idfld=" . $table_columns[0];
						$rows[$row_key][$table_columns[$i]] .= "'>";
						$rows[$row_key][$table_columns[$i]] .= number_format(strlen($row_sql[$table_columns[$i]]) / 1024, 2) . " Kb.";
						$rows[$row_key][$table_columns[$i]] .= "</a>";
				}
		}

        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});




/* Download blob img */
$app->match('/domicilio/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . domicilio . " WHERE ".$idfldname." = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($rowid));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('menu_list'));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/jpeg');
    header("Content-length: ".strlen( $row_sql[$fieldname] ));
    header('Expires: 0');
    header('Cache-Control: public');
    header('Pragma: public');
    ob_clean();    
    echo $row_sql[$fieldname];
    exit();
   
    
});



$app->match('/domicilio', function () use ($app) {
    
	$table_columns = array(
		'ID_domicilio', 
		'telefono', 
		'direccion', 
		'nombre', 
		'tarjeta', 
		'expiracion', 
		'vuelto', 
		'notas', 
		'metodo_pago', 
		'documento_fiscal', 
		'detalle_facturacion', 
		'facturacion_nombre', 
		'facturacion__dui', 
		'facturacion_nit', 
		'facturacion_nrc', 
		'facturacion_giro', 
		'facturacion_direccion', 
		'flag_en_transito', 
		'fechahora_transito', 
		'flag_ack', 
		'flag_tarjeta_cobrada', 

    );

    $primary_key = "ID_domicilio";	

    return $app['twig']->render('domicilio/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('domicilio_list');



$app->match('/domicilio/create', function () use ($app) {
    
    $initial_data = array(
		'telefono' => '', 
		'direccion' => '', 
		'nombre' => '', 
		'tarjeta' => '', 
		'expiracion' => '', 
		'vuelto' => '', 
		'notas' => '', 
		'metodo_pago' => '', 
		'documento_fiscal' => '', 
		'detalle_facturacion' => '', 
		'facturacion_nombre' => '', 
		'facturacion__dui' => '', 
		'facturacion_nit' => '', 
		'facturacion_nrc' => '', 
		'facturacion_giro' => '', 
		'facturacion_direccion' => '', 
		'flag_en_transito' => '', 
		'fechahora_transito' => '', 
		'flag_ack' => '', 
		'flag_tarjeta_cobrada' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('telefono', 'text', array('required' => true));
	$form = $form->add('direccion', 'textarea', array('required' => true));
	$form = $form->add('nombre', 'text', array('required' => true));
	$form = $form->add('tarjeta', 'text', array('required' => true));
	$form = $form->add('expiracion', 'text', array('required' => true));
	$form = $form->add('vuelto', 'text', array('required' => true));
	$form = $form->add('notas', 'text', array('required' => true));
	$form = $form->add('metodo_pago', 'text', array('required' => true));
	$form = $form->add('documento_fiscal', 'text', array('required' => true));
	$form = $form->add('detalle_facturacion', 'text', array('required' => true));
	$form = $form->add('facturacion_nombre', 'text', array('required' => true));
	$form = $form->add('facturacion__dui', 'text', array('required' => true));
	$form = $form->add('facturacion_nit', 'text', array('required' => true));
	$form = $form->add('facturacion_nrc', 'text', array('required' => true));
	$form = $form->add('facturacion_giro', 'text', array('required' => true));
	$form = $form->add('facturacion_direccion', 'text', array('required' => true));
	$form = $form->add('flag_en_transito', 'text', array('required' => true));
	$form = $form->add('fechahora_transito', 'text', array('required' => true));
	$form = $form->add('flag_ack', 'text', array('required' => true));
	$form = $form->add('flag_tarjeta_cobrada', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `domicilio` (`telefono`, `direccion`, `nombre`, `tarjeta`, `expiracion`, `vuelto`, `notas`, `metodo_pago`, `documento_fiscal`, `detalle_facturacion`, `facturacion_nombre`, `facturacion__dui`, `facturacion_nit`, `facturacion_nrc`, `facturacion_giro`, `facturacion_direccion`, `flag_en_transito`, `fechahora_transito`, `flag_ack`, `flag_tarjeta_cobrada`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['telefono'], $data['direccion'], $data['nombre'], $data['tarjeta'], $data['expiracion'], $data['vuelto'], $data['notas'], $data['metodo_pago'], $data['documento_fiscal'], $data['detalle_facturacion'], $data['facturacion_nombre'], $data['facturacion__dui'], $data['facturacion_nit'], $data['facturacion_nrc'], $data['facturacion_giro'], $data['facturacion_direccion'], $data['flag_en_transito'], $data['fechahora_transito'], $data['flag_ack'], $data['flag_tarjeta_cobrada']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'domicilio created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('domicilio_list'));

        }
    }

    return $app['twig']->render('domicilio/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('domicilio_create');



$app->match('/domicilio/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `domicilio` WHERE `ID_domicilio` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('domicilio_list'));
    }

    
    $initial_data = array(
		'telefono' => $row_sql['telefono'], 
		'direccion' => $row_sql['direccion'], 
		'nombre' => $row_sql['nombre'], 
		'tarjeta' => $row_sql['tarjeta'], 
		'expiracion' => $row_sql['expiracion'], 
		'vuelto' => $row_sql['vuelto'], 
		'notas' => $row_sql['notas'], 
		'metodo_pago' => $row_sql['metodo_pago'], 
		'documento_fiscal' => $row_sql['documento_fiscal'], 
		'detalle_facturacion' => $row_sql['detalle_facturacion'], 
		'facturacion_nombre' => $row_sql['facturacion_nombre'], 
		'facturacion__dui' => $row_sql['facturacion__dui'], 
		'facturacion_nit' => $row_sql['facturacion_nit'], 
		'facturacion_nrc' => $row_sql['facturacion_nrc'], 
		'facturacion_giro' => $row_sql['facturacion_giro'], 
		'facturacion_direccion' => $row_sql['facturacion_direccion'], 
		'flag_en_transito' => $row_sql['flag_en_transito'], 
		'fechahora_transito' => $row_sql['fechahora_transito'], 
		'flag_ack' => $row_sql['flag_ack'], 
		'flag_tarjeta_cobrada' => $row_sql['flag_tarjeta_cobrada'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('telefono', 'text', array('required' => true));
	$form = $form->add('direccion', 'textarea', array('required' => true));
	$form = $form->add('nombre', 'text', array('required' => true));
	$form = $form->add('tarjeta', 'text', array('required' => true));
	$form = $form->add('expiracion', 'text', array('required' => true));
	$form = $form->add('vuelto', 'text', array('required' => true));
	$form = $form->add('notas', 'text', array('required' => true));
	$form = $form->add('metodo_pago', 'text', array('required' => true));
	$form = $form->add('documento_fiscal', 'text', array('required' => true));
	$form = $form->add('detalle_facturacion', 'text', array('required' => true));
	$form = $form->add('facturacion_nombre', 'text', array('required' => true));
	$form = $form->add('facturacion__dui', 'text', array('required' => true));
	$form = $form->add('facturacion_nit', 'text', array('required' => true));
	$form = $form->add('facturacion_nrc', 'text', array('required' => true));
	$form = $form->add('facturacion_giro', 'text', array('required' => true));
	$form = $form->add('facturacion_direccion', 'text', array('required' => true));
	$form = $form->add('flag_en_transito', 'text', array('required' => true));
	$form = $form->add('fechahora_transito', 'text', array('required' => true));
	$form = $form->add('flag_ack', 'text', array('required' => true));
	$form = $form->add('flag_tarjeta_cobrada', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `domicilio` SET `telefono` = ?, `direccion` = ?, `nombre` = ?, `tarjeta` = ?, `expiracion` = ?, `vuelto` = ?, `notas` = ?, `metodo_pago` = ?, `documento_fiscal` = ?, `detalle_facturacion` = ?, `facturacion_nombre` = ?, `facturacion__dui` = ?, `facturacion_nit` = ?, `facturacion_nrc` = ?, `facturacion_giro` = ?, `facturacion_direccion` = ?, `flag_en_transito` = ?, `fechahora_transito` = ?, `flag_ack` = ?, `flag_tarjeta_cobrada` = ? WHERE `ID_domicilio` = ?";
            $app['db']->executeUpdate($update_query, array($data['telefono'], $data['direccion'], $data['nombre'], $data['tarjeta'], $data['expiracion'], $data['vuelto'], $data['notas'], $data['metodo_pago'], $data['documento_fiscal'], $data['detalle_facturacion'], $data['facturacion_nombre'], $data['facturacion__dui'], $data['facturacion_nit'], $data['facturacion_nrc'], $data['facturacion_giro'], $data['facturacion_direccion'], $data['flag_en_transito'], $data['fechahora_transito'], $data['flag_ack'], $data['flag_tarjeta_cobrada'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'domicilio edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('domicilio_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('domicilio/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('domicilio_edit');



$app->match('/domicilio/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `domicilio` WHERE `ID_domicilio` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `domicilio` WHERE `ID_domicilio` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'domicilio deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('domicilio_list'));

})
->bind('domicilio_delete');






