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

$app->match('/pedidos/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'ID_producto', 
		'precio_grabado', 
		'precio_original', 
		'flag_cancelado', 
		'tmpID', 
		'fechahora_pedido', 
		'fechahora_elaborado', 
		'fechahora_despachado', 
		'fechahora_activacion', 
		'flag_elaborado', 
		'flag_despachado', 
		'flag_pausa', 
		'prioridad', 
		'nodo', 
		'grupo', 
		'ID_cuenta', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(8)', 
		'decimal(6,2)', 
		'decimal(6,2)', 
		'bit(1)', 
		'tinyint(4)', 
		'datetime', 
		'datetime', 
		'datetime', 
		'datetime', 
		'bit(1)', 
		'bit(1)', 
		'bit(1)', 
		'enum(\'baja\',\'normal\',\'alta\')', 
		'varchar(20)', 
		'char(13)', 
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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `pedidos`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `pedidos`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/pedidos/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . pedidos . " WHERE ".$idfldname." = ?";
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



$app->match('/pedidos', function () use ($app) {
    
	$table_columns = array(
		'ID_pedido', 
		'ID_producto', 
		'precio_grabado', 
		'precio_original', 
		'flag_cancelado', 
		'tmpID', 
		'fechahora_pedido', 
		'fechahora_elaborado', 
		'fechahora_despachado', 
		'fechahora_activacion', 
		'flag_elaborado', 
		'flag_despachado', 
		'flag_pausa', 
		'prioridad', 
		'nodo', 
		'grupo', 
		'ID_cuenta', 

    );

    $primary_key = "ID_pedido";	

    return $app['twig']->render('pedidos/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('pedidos_list');



$app->match('/pedidos/create', function () use ($app) {
    
    $initial_data = array(
		'ID_producto' => '', 
		'precio_grabado' => '', 
		'precio_original' => '', 
		'flag_cancelado' => '', 
		'tmpID' => '', 
		'fechahora_pedido' => '', 
		'fechahora_elaborado' => '', 
		'fechahora_despachado' => '', 
		'fechahora_activacion' => '', 
		'flag_elaborado' => '', 
		'flag_despachado' => '', 
		'flag_pausa' => '', 
		'prioridad' => '', 
		'nodo' => '', 
		'grupo' => '', 
		'ID_cuenta' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('ID_producto', 'text', array('required' => true));
	$form = $form->add('precio_grabado', 'text', array('required' => true));
	$form = $form->add('precio_original', 'text', array('required' => true));
	$form = $form->add('flag_cancelado', 'text', array('required' => true));
	$form = $form->add('tmpID', 'text', array('required' => true));
	$form = $form->add('fechahora_pedido', 'text', array('required' => true));
	$form = $form->add('fechahora_elaborado', 'text', array('required' => true));
	$form = $form->add('fechahora_despachado', 'text', array('required' => true));
	$form = $form->add('fechahora_activacion', 'text', array('required' => true));
	$form = $form->add('flag_elaborado', 'text', array('required' => true));
	$form = $form->add('flag_despachado', 'text', array('required' => true));
	$form = $form->add('flag_pausa', 'text', array('required' => true));
	$form = $form->add('prioridad', 'text', array('required' => true));
	$form = $form->add('nodo', 'text', array('required' => true));
	$form = $form->add('grupo', 'text', array('required' => true));
	$form = $form->add('ID_cuenta', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `pedidos` (`ID_producto`, `precio_grabado`, `precio_original`, `flag_cancelado`, `tmpID`, `fechahora_pedido`, `fechahora_elaborado`, `fechahora_despachado`, `fechahora_activacion`, `flag_elaborado`, `flag_despachado`, `flag_pausa`, `prioridad`, `nodo`, `grupo`, `ID_cuenta`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['ID_producto'], $data['precio_grabado'], $data['precio_original'], $data['flag_cancelado'], $data['tmpID'], $data['fechahora_pedido'], $data['fechahora_elaborado'], $data['fechahora_despachado'], $data['fechahora_activacion'], $data['flag_elaborado'], $data['flag_despachado'], $data['flag_pausa'], $data['prioridad'], $data['nodo'], $data['grupo'], $data['ID_cuenta']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'pedidos created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('pedidos_list'));

        }
    }

    return $app['twig']->render('pedidos/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('pedidos_create');



$app->match('/pedidos/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `pedidos` WHERE `ID_pedido` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('pedidos_list'));
    }

    
    $initial_data = array(
		'ID_producto' => $row_sql['ID_producto'], 
		'precio_grabado' => $row_sql['precio_grabado'], 
		'precio_original' => $row_sql['precio_original'], 
		'flag_cancelado' => $row_sql['flag_cancelado'], 
		'tmpID' => $row_sql['tmpID'], 
		'fechahora_pedido' => $row_sql['fechahora_pedido'], 
		'fechahora_elaborado' => $row_sql['fechahora_elaborado'], 
		'fechahora_despachado' => $row_sql['fechahora_despachado'], 
		'fechahora_activacion' => $row_sql['fechahora_activacion'], 
		'flag_elaborado' => $row_sql['flag_elaborado'], 
		'flag_despachado' => $row_sql['flag_despachado'], 
		'flag_pausa' => $row_sql['flag_pausa'], 
		'prioridad' => $row_sql['prioridad'], 
		'nodo' => $row_sql['nodo'], 
		'grupo' => $row_sql['grupo'], 
		'ID_cuenta' => $row_sql['ID_cuenta'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('ID_producto', 'text', array('required' => true));
	$form = $form->add('precio_grabado', 'text', array('required' => true));
	$form = $form->add('precio_original', 'text', array('required' => true));
	$form = $form->add('flag_cancelado', 'text', array('required' => true));
	$form = $form->add('tmpID', 'text', array('required' => true));
	$form = $form->add('fechahora_pedido', 'text', array('required' => true));
	$form = $form->add('fechahora_elaborado', 'text', array('required' => true));
	$form = $form->add('fechahora_despachado', 'text', array('required' => true));
	$form = $form->add('fechahora_activacion', 'text', array('required' => true));
	$form = $form->add('flag_elaborado', 'text', array('required' => true));
	$form = $form->add('flag_despachado', 'text', array('required' => true));
	$form = $form->add('flag_pausa', 'text', array('required' => true));
	$form = $form->add('prioridad', 'text', array('required' => true));
	$form = $form->add('nodo', 'text', array('required' => true));
	$form = $form->add('grupo', 'text', array('required' => true));
	$form = $form->add('ID_cuenta', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `pedidos` SET `ID_producto` = ?, `precio_grabado` = ?, `precio_original` = ?, `flag_cancelado` = ?, `tmpID` = ?, `fechahora_pedido` = ?, `fechahora_elaborado` = ?, `fechahora_despachado` = ?, `fechahora_activacion` = ?, `flag_elaborado` = ?, `flag_despachado` = ?, `flag_pausa` = ?, `prioridad` = ?, `nodo` = ?, `grupo` = ?, `ID_cuenta` = ? WHERE `ID_pedido` = ?";
            $app['db']->executeUpdate($update_query, array($data['ID_producto'], $data['precio_grabado'], $data['precio_original'], $data['flag_cancelado'], $data['tmpID'], $data['fechahora_pedido'], $data['fechahora_elaborado'], $data['fechahora_despachado'], $data['fechahora_activacion'], $data['flag_elaborado'], $data['flag_despachado'], $data['flag_pausa'], $data['prioridad'], $data['nodo'], $data['grupo'], $data['ID_cuenta'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'pedidos edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('pedidos_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('pedidos/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('pedidos_edit');



$app->match('/pedidos/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `pedidos` WHERE `ID_pedido` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `pedidos` WHERE `ID_pedido` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'pedidos deleted!',
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

    return $app->redirect($app['url_generator']->generate('pedidos_list'));

})
->bind('pedidos_delete');






