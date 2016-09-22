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

$app->match('/pedidos_adicionales/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'ID_pedido', 
		'ID_adicional', 
		'precio_grabado', 
		'precio_original', 
		'tipo', 
		'ID_pedido_adicional', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'decimal(6,2)', 
		'decimal(6,2)', 
		'enum(\'quitar\',\'poner\',\'poquito\',\'separar\')', 
		'int(11)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `pedidos_adicionales`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `pedidos_adicionales`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/pedidos_adicionales/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . pedidos_adicionales . " WHERE ".$idfldname." = ?";
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



$app->match('/pedidos_adicionales', function () use ($app) {
    
	$table_columns = array(
		'ID_pedido', 
		'ID_adicional', 
		'precio_grabado', 
		'precio_original', 
		'tipo', 
		'ID_pedido_adicional', 

    );

    $primary_key = "ID_pedido_adicional";	

    return $app['twig']->render('pedidos_adicionales/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('pedidos_adicionales_list');



$app->match('/pedidos_adicionales/create', function () use ($app) {
    
    $initial_data = array(
		'ID_pedido' => '', 
		'ID_adicional' => '', 
		'precio_grabado' => '', 
		'precio_original' => '', 
		'tipo' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('ID_pedido', 'text', array('required' => true));
	$form = $form->add('ID_adicional', 'text', array('required' => true));
	$form = $form->add('precio_grabado', 'text', array('required' => true));
	$form = $form->add('precio_original', 'text', array('required' => true));
	$form = $form->add('tipo', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `pedidos_adicionales` (`ID_pedido`, `ID_adicional`, `precio_grabado`, `precio_original`, `tipo`) VALUES (?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['ID_pedido'], $data['ID_adicional'], $data['precio_grabado'], $data['precio_original'], $data['tipo']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'pedidos_adicionales created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('pedidos_adicionales_list'));

        }
    }

    return $app['twig']->render('pedidos_adicionales/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('pedidos_adicionales_create');



$app->match('/pedidos_adicionales/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `pedidos_adicionales` WHERE `ID_pedido_adicional` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('pedidos_adicionales_list'));
    }

    
    $initial_data = array(
		'ID_pedido' => $row_sql['ID_pedido'], 
		'ID_adicional' => $row_sql['ID_adicional'], 
		'precio_grabado' => $row_sql['precio_grabado'], 
		'precio_original' => $row_sql['precio_original'], 
		'tipo' => $row_sql['tipo'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('ID_pedido', 'text', array('required' => true));
	$form = $form->add('ID_adicional', 'text', array('required' => true));
	$form = $form->add('precio_grabado', 'text', array('required' => true));
	$form = $form->add('precio_original', 'text', array('required' => true));
	$form = $form->add('tipo', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `pedidos_adicionales` SET `ID_pedido` = ?, `ID_adicional` = ?, `precio_grabado` = ?, `precio_original` = ?, `tipo` = ? WHERE `ID_pedido_adicional` = ?";
            $app['db']->executeUpdate($update_query, array($data['ID_pedido'], $data['ID_adicional'], $data['precio_grabado'], $data['precio_original'], $data['tipo'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'pedidos_adicionales edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('pedidos_adicionales_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('pedidos_adicionales/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('pedidos_adicionales_edit');



$app->match('/pedidos_adicionales/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `pedidos_adicionales` WHERE `ID_pedido_adicional` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `pedidos_adicionales` WHERE `ID_pedido_adicional` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'pedidos_adicionales deleted!',
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

    return $app->redirect($app['url_generator']->generate('pedidos_adicionales_list'));

})
->bind('pedidos_adicionales_delete');






