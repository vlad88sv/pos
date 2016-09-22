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

$app->match('/cortez/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'ID_cortez', 
		'fechahora_recibido', 
		'fechahora_remesado', 
		'total_a_cuadrar', 
		'total_descuentos', 
		'total_diferencia', 
		'total_efectivo', 
		'total_pos', 
		'total_compras', 
		'total_caja', 
		'inventario', 
		'ID_usuario', 
		'fechatiempo', 
		'estado', 
		'remesa', 
		'impreso', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'datetime', 
		'datetime', 
		'decimal(10,2)', 
		'decimal(10,2)', 
		'decimal(10,2)', 
		'decimal(10,2)', 
		'decimal(10,2)', 
		'decimal(10,2)', 
		'decimal(10,2)', 
		'text', 
		'int(1)', 
		'datetime', 
		'enum(\'pendiente\',\'recibido\',\'remesado\')', 
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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `cortez`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `cortez`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/cortez/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . cortez . " WHERE ".$idfldname." = ?";
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



$app->match('/cortez', function () use ($app) {
    
	$table_columns = array(
		'ID_cortez', 
		'fechahora_recibido', 
		'fechahora_remesado', 
		'total_a_cuadrar', 
		'total_descuentos', 
		'total_diferencia', 
		'total_efectivo', 
		'total_pos', 
		'total_compras', 
		'total_caja', 
		'inventario', 
		'ID_usuario', 
		'fechatiempo', 
		'estado', 
		'remesa', 
		'impreso', 

    );

    $primary_key = "ID_cortez";	

    return $app['twig']->render('cortez/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('cortez_list');



$app->match('/cortez/create', function () use ($app) {
    
    $initial_data = array(
		'fechahora_recibido' => '', 
		'fechahora_remesado' => '', 
		'total_a_cuadrar' => '', 
		'total_descuentos' => '', 
		'total_diferencia' => '', 
		'total_efectivo' => '', 
		'total_pos' => '', 
		'total_compras' => '', 
		'total_caja' => '', 
		'inventario' => '', 
		'ID_usuario' => '', 
		'fechatiempo' => '', 
		'estado' => '', 
		'remesa' => '', 
		'impreso' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('fechahora_recibido', 'text', array('required' => true));
	$form = $form->add('fechahora_remesado', 'text', array('required' => true));
	$form = $form->add('total_a_cuadrar', 'text', array('required' => true));
	$form = $form->add('total_descuentos', 'text', array('required' => true));
	$form = $form->add('total_diferencia', 'text', array('required' => true));
	$form = $form->add('total_efectivo', 'text', array('required' => true));
	$form = $form->add('total_pos', 'text', array('required' => true));
	$form = $form->add('total_compras', 'text', array('required' => true));
	$form = $form->add('total_caja', 'text', array('required' => true));
	$form = $form->add('inventario', 'textarea', array('required' => true));
	$form = $form->add('ID_usuario', 'text', array('required' => true));
	$form = $form->add('fechatiempo', 'text', array('required' => true));
	$form = $form->add('estado', 'text', array('required' => true));
	$form = $form->add('remesa', 'text', array('required' => true));
	$form = $form->add('impreso', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `cortez` (`fechahora_recibido`, `fechahora_remesado`, `total_a_cuadrar`, `total_descuentos`, `total_diferencia`, `total_efectivo`, `total_pos`, `total_compras`, `total_caja`, `inventario`, `ID_usuario`, `fechatiempo`, `estado`, `remesa`, `impreso`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['fechahora_recibido'], $data['fechahora_remesado'], $data['total_a_cuadrar'], $data['total_descuentos'], $data['total_diferencia'], $data['total_efectivo'], $data['total_pos'], $data['total_compras'], $data['total_caja'], $data['inventario'], $data['ID_usuario'], $data['fechatiempo'], $data['estado'], $data['remesa'], $data['impreso']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'cortez created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('cortez_list'));

        }
    }

    return $app['twig']->render('cortez/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('cortez_create');



$app->match('/cortez/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `cortez` WHERE `ID_cortez` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('cortez_list'));
    }

    
    $initial_data = array(
		'fechahora_recibido' => $row_sql['fechahora_recibido'], 
		'fechahora_remesado' => $row_sql['fechahora_remesado'], 
		'total_a_cuadrar' => $row_sql['total_a_cuadrar'], 
		'total_descuentos' => $row_sql['total_descuentos'], 
		'total_diferencia' => $row_sql['total_diferencia'], 
		'total_efectivo' => $row_sql['total_efectivo'], 
		'total_pos' => $row_sql['total_pos'], 
		'total_compras' => $row_sql['total_compras'], 
		'total_caja' => $row_sql['total_caja'], 
		'inventario' => $row_sql['inventario'], 
		'ID_usuario' => $row_sql['ID_usuario'], 
		'fechatiempo' => $row_sql['fechatiempo'], 
		'estado' => $row_sql['estado'], 
		'remesa' => $row_sql['remesa'], 
		'impreso' => $row_sql['impreso'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('fechahora_recibido', 'text', array('required' => true));
	$form = $form->add('fechahora_remesado', 'text', array('required' => true));
	$form = $form->add('total_a_cuadrar', 'text', array('required' => true));
	$form = $form->add('total_descuentos', 'text', array('required' => true));
	$form = $form->add('total_diferencia', 'text', array('required' => true));
	$form = $form->add('total_efectivo', 'text', array('required' => true));
	$form = $form->add('total_pos', 'text', array('required' => true));
	$form = $form->add('total_compras', 'text', array('required' => true));
	$form = $form->add('total_caja', 'text', array('required' => true));
	$form = $form->add('inventario', 'textarea', array('required' => true));
	$form = $form->add('ID_usuario', 'text', array('required' => true));
	$form = $form->add('fechatiempo', 'text', array('required' => true));
	$form = $form->add('estado', 'text', array('required' => true));
	$form = $form->add('remesa', 'text', array('required' => true));
	$form = $form->add('impreso', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `cortez` SET `fechahora_recibido` = ?, `fechahora_remesado` = ?, `total_a_cuadrar` = ?, `total_descuentos` = ?, `total_diferencia` = ?, `total_efectivo` = ?, `total_pos` = ?, `total_compras` = ?, `total_caja` = ?, `inventario` = ?, `ID_usuario` = ?, `fechatiempo` = ?, `estado` = ?, `remesa` = ?, `impreso` = ? WHERE `ID_cortez` = ?";
            $app['db']->executeUpdate($update_query, array($data['fechahora_recibido'], $data['fechahora_remesado'], $data['total_a_cuadrar'], $data['total_descuentos'], $data['total_diferencia'], $data['total_efectivo'], $data['total_pos'], $data['total_compras'], $data['total_caja'], $data['inventario'], $data['ID_usuario'], $data['fechatiempo'], $data['estado'], $data['remesa'], $data['impreso'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'cortez edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('cortez_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('cortez/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('cortez_edit');



$app->match('/cortez/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `cortez` WHERE `ID_cortez` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `cortez` WHERE `ID_cortez` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'cortez deleted!',
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

    return $app->redirect($app['url_generator']->generate('cortez_list'));

})
->bind('cortez_delete');






