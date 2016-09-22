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

$app->match('/historial/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'ID_historial', 
		'ID_pedido', 
		'fechahora', 
		'nota', 
		'grupo', 
		'accion', 
		'ID_cuenta', 
		'ID_mesa', 
		'flag_importante', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'datetime', 
		'varchar(250)', 
		'varchar(250)', 
		'varchar(250)', 
		'int(11)', 
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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `historial`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `historial`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/historial/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . historial . " WHERE ".$idfldname." = ?";
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



$app->match('/historial', function () use ($app) {
    
	$table_columns = array(
		'ID_historial', 
		'ID_pedido', 
		'fechahora', 
		'nota', 
		'grupo', 
		'accion', 
		'ID_cuenta', 
		'ID_mesa', 
		'flag_importante', 

    );

    $primary_key = "ID_historial";	

    return $app['twig']->render('historial/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('historial_list');



$app->match('/historial/create', function () use ($app) {
    
    $initial_data = array(
		'ID_pedido' => '', 
		'fechahora' => '', 
		'nota' => '', 
		'grupo' => '', 
		'accion' => '', 
		'ID_cuenta' => '', 
		'ID_mesa' => '', 
		'flag_importante' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('ID_pedido', 'text', array('required' => true));
	$form = $form->add('fechahora', 'text', array('required' => true));
	$form = $form->add('nota', 'text', array('required' => true));
	$form = $form->add('grupo', 'text', array('required' => true));
	$form = $form->add('accion', 'text', array('required' => true));
	$form = $form->add('ID_cuenta', 'text', array('required' => true));
	$form = $form->add('ID_mesa', 'text', array('required' => true));
	$form = $form->add('flag_importante', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `historial` (`ID_pedido`, `fechahora`, `nota`, `grupo`, `accion`, `ID_cuenta`, `ID_mesa`, `flag_importante`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['ID_pedido'], $data['fechahora'], $data['nota'], $data['grupo'], $data['accion'], $data['ID_cuenta'], $data['ID_mesa'], $data['flag_importante']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'historial created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('historial_list'));

        }
    }

    return $app['twig']->render('historial/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('historial_create');



$app->match('/historial/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `historial` WHERE `ID_historial` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('historial_list'));
    }

    
    $initial_data = array(
		'ID_pedido' => $row_sql['ID_pedido'], 
		'fechahora' => $row_sql['fechahora'], 
		'nota' => $row_sql['nota'], 
		'grupo' => $row_sql['grupo'], 
		'accion' => $row_sql['accion'], 
		'ID_cuenta' => $row_sql['ID_cuenta'], 
		'ID_mesa' => $row_sql['ID_mesa'], 
		'flag_importante' => $row_sql['flag_importante'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('ID_pedido', 'text', array('required' => true));
	$form = $form->add('fechahora', 'text', array('required' => true));
	$form = $form->add('nota', 'text', array('required' => true));
	$form = $form->add('grupo', 'text', array('required' => true));
	$form = $form->add('accion', 'text', array('required' => true));
	$form = $form->add('ID_cuenta', 'text', array('required' => true));
	$form = $form->add('ID_mesa', 'text', array('required' => true));
	$form = $form->add('flag_importante', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `historial` SET `ID_pedido` = ?, `fechahora` = ?, `nota` = ?, `grupo` = ?, `accion` = ?, `ID_cuenta` = ?, `ID_mesa` = ?, `flag_importante` = ? WHERE `ID_historial` = ?";
            $app['db']->executeUpdate($update_query, array($data['ID_pedido'], $data['fechahora'], $data['nota'], $data['grupo'], $data['accion'], $data['ID_cuenta'], $data['ID_mesa'], $data['flag_importante'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'historial edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('historial_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('historial/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('historial_edit');



$app->match('/historial/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `historial` WHERE `ID_historial` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `historial` WHERE `ID_historial` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'historial deleted!',
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

    return $app->redirect($app['url_generator']->generate('historial_list'));

})
->bind('historial_delete');






